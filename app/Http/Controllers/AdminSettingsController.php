<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use PDO;
use Throwable;

class AdminSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $tabs = config('admin_settings.tabs', []);
        $activeTab = (string) $request->query('tab', array_key_first($tabs));
        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = (string) array_key_first($tabs);
        }

        $settings = $this->loadSettings($tabs);

        return view('panel.settings', [
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tabs = config('admin_settings.tabs', []);
        $tab = (string) $request->input('tab', '');
        if (! array_key_exists($tab, $tabs)) {
            return redirect()->route('panel.settings')->withErrors(['settings' => 'Invalid settings tab.']);
        }

        $rules = [];
        foreach ($tabs[$tab]['fields'] ?? [] as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($field['type'] ?? 'text');
            $rules["settings.{$key}"] = match ($type) {
                'number' => ['nullable', 'integer'],
                'boolean' => ['nullable', 'boolean'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $validated = $request->validate($rules);
        $incoming = $validated['settings'] ?? [];

        $stored = Cache::get('admin_settings.values', []);

        foreach ($tabs[$tab]['fields'] ?? [] as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $type = (string) ($field['type'] ?? 'text');
            if ($type === 'boolean') {
                $stored[$key] = $request->boolean("settings.{$key}");
                continue;
            }

            if (array_key_exists($key, $incoming)) {
                $stored[$key] = $incoming[$key];
            }
        }

        Cache::forever('admin_settings.values', $stored);

        return redirect()
            ->route('panel.settings', ['tab' => $tab])
            ->with('success', 'Settings updated successfully.');
    }

    public function testDb(Request $request)
    {
        $validated = $request->validate([
            'db_type' => ['required', 'string', 'in:postgres,mysql,sqlite,central_db'],
            'settings' => ['required', 'array'],
        ]);

        $dbType = (string) $validated['db_type'];
        $payload = (array) $validated['settings'];

        try {
            match ($dbType) {
                'postgres' => $this->testPostgres($payload),
                'mysql' => $this->testMysql($payload),
                'sqlite' => $this->testSqlite($payload),
                'central_db' => $this->testMysql($payload),
            };

            return response()->json([
                'ok' => true,
                'message' => ucfirst(str_replace('_', ' ', $dbType)).' connection successful.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @param  array<string, mixed>  $tabs
     * @return array<string, mixed>
     */
    private function loadSettings(array $tabs): array
    {
        $stored = Cache::get('admin_settings.values', []);
        $out = [];

        foreach ($tabs as $tab) {
            foreach (($tab['fields'] ?? []) as $field) {
                $key = (string) ($field['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $out[$key] = $stored[$key] ?? ($field['default'] ?? null);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function testMysql(array $payload): void
    {
        $host = (string) ($payload['host'] ?? '127.0.0.1');
        $port = (int) ($payload['port'] ?? 3306);
        $database = (string) ($payload['database'] ?? '');
        $username = (string) ($payload['username'] ?? '');
        $password = (string) ($payload['password'] ?? '');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function testPostgres(array $payload): void
    {
        $host = (string) ($payload['host'] ?? '127.0.0.1');
        $port = (int) ($payload['port'] ?? 5432);
        $database = (string) ($payload['database'] ?? '');
        $username = (string) ($payload['username'] ?? '');
        $password = (string) ($payload['password'] ?? '');

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function testSqlite(array $payload): void
    {
        $database = (string) ($payload['database'] ?? '');
        if ($database === '') {
            throw new \RuntimeException('SQLite file path is required.');
        }

        $dsn = "sqlite:{$database}";
        new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
}
