<?php

namespace Modules\ManageDb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\ManageDb\Models\HostingMysqlUserSecret;
use Modules\ManageDb\Services\ManageDbService;
use Throwable;

class ManageDbController extends Controller
{
    public function index(Hosting $hosting, ManageDbService $db): View
    {
        $selectedCard = (string) request()->query('db', '');
        $nonMysqlCards = ['postgres', 'sqlite', 'central_db'];
        if (! in_array($selectedCard, $nonMysqlCards, true)) {
            $selectedCard = '';
        }

        return view('managedb::index', [
            'hosting' => $hosting,
            'prefix' => $db->prefixForHosting($hosting),
            'dbCards' => $this->dbCards(),
            'activeDbCard' => $selectedCard,
        ]);
    }

    public function mysql(Hosting $hosting, ManageDbService $db): View
    {
        $mysqlState = $this->loadMysqlState($hosting, $db);
        $tab = (string) request()->query('tab', 'overview');
        $allowedTabs = ['overview', 'users', 'databases'];
        $activeTab = in_array($tab, $allowedTabs, true) ? $tab : 'overview';
        $adminerOverride = trim((string) config('manage_db.adminer_url', ''));
        $publicAdminer = is_file(public_path('adminer.php'));
        $adminerAutologinUrl = ($adminerOverride !== '' || $publicAdminer)
            ? route('hosts.db.adminer-login', $hosting)
            : null;

        return view('managedb::mysql', [
            'hosting' => $hosting,
            'prefix' => $db->prefixForHosting($hosting),
            'dbCards' => $this->dbCards(),
            'activeDbCard' => 'mysql',
            'activeTab' => $activeTab,
            'adminerAutologinUrl' => $adminerAutologinUrl,
            ...$mysqlState,
        ]);
    }

    /**
     * Returns a minimal page that auto-posts the Adminer 4.x login form (new tab) using the
     * same MySQL host as panel DB tools. Adminer defaults to public/adminer.php, or
     * MANAGE_DB_ADMINER_URL / manage_db.adminer_url to override.
     */
    public function adminerLogin(Request $request, Hosting $hosting, ManageDbService $db): Response|RedirectResponse
    {
        $override = trim((string) config('manage_db.adminer_url', ''));
        if ($override === '' && ! is_file(public_path('adminer.php'))) {
            return redirect()
                ->route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'overview'])
                ->withErrors(['adminer' => 'Add public/adminer.php or set MANAGE_DB_ADMINER_URL to a full Adminer URL.']);
        }
        $action = $this->adminerFormActionUrl();

        $prefix = $db->prefixForHosting($hosting).'_';
        $mysqlUser = strtolower(trim((string) $request->query('mysql_user', '')));
        if ($mysqlUser === '' || ! str_starts_with($mysqlUser, $prefix) || $mysqlUser === $prefix) {
            abort(403, 'Invalid MySQL user for this host.');
        }
        if (! preg_match('/^[a-z0-9_]{1,64}$/', $mysqlUser)) {
            abort(403, 'Invalid MySQL user for this host.');
        }

        $userList = [];
        $userListFailed = false;
        try {
            $userList = $db->listUsers($hosting);
        } catch (Throwable) {
            $userListFailed = true;
        }

        if (! $userListFailed) {
            if (! in_array($mysqlUser, $userList, true)) {
                abort(403, 'This MySQL user is not in the server list for this host.');
            }
        } else {
            if (! HostingMysqlUserSecret::query()
                ->where('hosting_id', $hosting->id)
                ->where('mysql_username', $mysqlUser)
                ->exists()) {
                abort(403, 'This MySQL user is not available for Adminer from the panel. Ensure mysql.user can be listed or a credential record exists for this user.');
            }
        }

        $dbName = strtolower(trim((string) $request->query('db', '')));
        if ($dbName !== '' && ! str_starts_with($dbName, $prefix)) {
            abort(403, 'Invalid database name for this host.');
        }
        if ($dbName !== '' && ! preg_match('/^[a-z0-9_]{1,64}$/', $dbName)) {
            abort(403, 'Invalid database name.');
        }

        $map = $db->getStoredUserPasswords($hosting, [strtolower($mysqlUser)]);
        $password = $map[strtolower($mysqlUser)] ?? '';
        $conn = $db->mysqlConnectionDisplayInfo();
        $hostStr = (string) $conn['host'];
        $port = (int) $conn['port'];
        $serverField = $port > 0 && $port !== 3306 ? $hostStr.':'.$port : $hostStr;

        $auth = [
            'driver' => 'server',
            'server' => $serverField,
            'username' => $mysqlUser,
            'password' => $password,
            'db' => $dbName,
        ];

        return response()
            ->view('managedb::adminer-autologin', [
                'action' => $action,
                'auth' => $auth,
            ], 200)
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function createDatabase(Request $request, Hosting $hosting, ManageDbService $db): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        try {
            $created = $db->createDatabase($hosting, $validated['name']);

            return redirect()->route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'overview'])
                ->with('success', "Database created: {$created}");
        } catch (Throwable $e) {
            return redirect()->route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'overview'])
                ->withErrors(['db_create' => $e->getMessage()]);
        }
    }

    public function createUser(Request $request, Hosting $hosting, ManageDbService $db): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'database' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $created = $db->createUser(
                $hosting,
                $validated['name'],
                $validated['password'],
                $validated['database'] ?? null
            );

            return redirect()->route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'overview'])
                ->with('success', "MySQL user created: {$created}");
        } catch (Throwable $e) {
            return redirect()->route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'overview'])
                ->withErrors(['user_create' => $e->getMessage()]);
        }
    }

    public function applyAccessGraph(Request $request, Hosting $hosting, ManageDbService $db): RedirectResponse
    {
        $validated = $request->validate([
            'graph_payload' => ['required', 'string'],
        ]);

        try {
            $decoded = json_decode($validated['graph_payload'], true, 512, JSON_THROW_ON_ERROR);
            $nodes = is_array($decoded['nodes'] ?? null) ? $decoded['nodes'] : [];
            $edges = is_array($decoded['edges'] ?? null) ? $decoded['edges'] : [];

            $result = $db->applyAccessGraph($hosting, $nodes, $edges);
            $parts = ["Access graph applied successfully."];
            $parts[] = "Total users in graph: {$result['users']}";
            $parts[] = "Granted access links: {$result['grants']}";
            $parts[] = "Created databases: {$result['created_databases']}";
            $parts[] = "Created users: {$result['created_users']}";

            if (! empty($result['created_database_names'])) {
                $parts[] = 'Database names: '.implode(', ', $result['created_database_names']);
            }
            if (! empty($result['created_user_credentials'])) {
                $parts[] = 'User credentials: '.implode(', ', $result['created_user_credentials']);
            }
            if (! empty($result['granted_pairs'])) {
                $parts[] = 'Access map: '.implode(', ', $result['granted_pairs']);
            }
            if (! empty($result['dropped_user_names'] ?? [])) {
                $parts[] = 'Removed MySQL users (not in graph): '.implode(', ', $result['dropped_user_names']);
            }

            return redirect()->route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'overview'])->with(
                'success',
                implode(PHP_EOL, $parts)
            );
        } catch (Throwable $e) {
            return redirect()->route('hosts.db.manage.mysql', ['hosting' => $hosting, 'tab' => 'overview'])
                ->withErrors(['access_graph' => $e->getMessage()]);
        }
    }

    /**
     * @return array<int, array{
     *   key:string,
     *   label:string,
     *   icon:string,
     *   enabled:bool,
     *   description:string,
     *   feature:string,
     *   cta:string
     * }>
     */
    private function dbCards(): array
    {
        $settings = (array) Cache::get('admin_settings.values', []);

        return [
            [
                'key' => 'mysql',
                'label' => 'MySQL',
                'icon' => 'fa fa-server',
                'enabled' => (bool) ($settings['mysql_enabled'] ?? true),
                'description' => 'Full management tools with create wizard, users, and prefixed database lists.',
                'feature' => 'Production host DB management',
                'cta' => 'Open MySQL tools',
            ],
            [
                'key' => 'postgres',
                'label' => 'PostgreSQL',
                'icon' => 'fa fa-database',
                'enabled' => (bool) ($settings['postgres_enabled'] ?? false),
                'description' => 'Dedicated PostgreSQL management area with settings-ready integration.',
                'feature' => 'Connection profile via settings tab',
                'cta' => 'Open PostgreSQL section',
            ],
            [
                'key' => 'sqlite',
                'label' => 'SQLite',
                'icon' => 'fa fa-file-text-o',
                'enabled' => (bool) ($settings['sqlite_enabled'] ?? false),
                'description' => 'Lightweight single-file database option for local and utility workloads.',
                'feature' => 'Best for compact app storage',
                'cta' => 'Open SQLite section',
            ],
            [
                'key' => 'central_db',
                'label' => 'Central DB',
                'icon' => 'fa fa-sitemap',
                'enabled' => (bool) ($settings['central_db_enabled'] ?? false),
                'description' => 'Centralized database profile for shared platform-level services.',
                'feature' => 'Cross-host shared data layer',
                'cta' => 'Open Central DB section',
            ],
        ];
    }

    /**
     * @return array{
     *   databases: array<int, string>,
     *   users: array<int, string>,
     *   userPasswords: array<string, string>,
     *   grants: array<int, array{user:string, database:string}>,
     *   loadDbError: ?string,
     *   loadUsersError: ?string,
     *   loadGrantsError: ?string,
     *   usersListRestricted: bool,
     *   mysqlInfo: array{host: string, port: int, user_host: string}
     * }
     */
    private function loadMysqlState(Hosting $hosting, ManageDbService $db): array
    {
        $databases = [];
        $users = [];
        $userPasswords = [];
        $grants = [];
        $loadDbError = null;
        $loadUsersError = null;
        $loadGrantsError = null;
        $usersListRestricted = false;

        try {
            $databases = $db->listDatabases($hosting);
        } catch (Throwable $e) {
            $loadDbError = $e->getMessage();
        }

        try {
            $users = $db->listUsers($hosting);
            $userPasswords = $db->getStoredUserPasswords($hosting, $users);
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (str_contains($message, "for table 'user'") || str_contains($message, '1142')) {
                $usersListRestricted = true;
            } else {
                $loadUsersError = $message;
            }
        }

        try {
            $grants = $db->listAccessGraphEdges($hosting);
        } catch (Throwable $e) {
            $loadGrantsError = $e->getMessage();
        }

        return [
            'databases' => $databases,
            'users' => $users,
            'userPasswords' => $userPasswords,
            'grants' => $grants,
            'loadDbError' => $loadDbError,
            'loadUsersError' => $loadUsersError,
            'loadGrantsError' => $loadGrantsError,
            'usersListRestricted' => $usersListRestricted,
            'mysqlInfo' => $db->mysqlConnectionDisplayInfo(),
        ];
    }

    private function adminerFormActionUrl(): string
    {
        $override = trim((string) config('manage_db.adminer_url', ''));

        return $override !== '' ? $override : url('adminer.php');
    }
}
