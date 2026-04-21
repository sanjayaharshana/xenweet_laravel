<?php

namespace Modules\ManageDb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ManageDb\Services\ManageDbService;
use Throwable;

class ManageDbController extends Controller
{
    public function index(Hosting $hosting, ManageDbService $db): View
    {
        $databases = [];
        $users = [];
        $loadDbError = null;
        $loadUsersError = null;

        try {
            $databases = $db->listDatabases($hosting);
        } catch (Throwable $e) {
            $loadDbError = $e->getMessage();
        }

        try {
            $users = $db->listUsers($hosting);
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (str_contains($message, "for table 'user'") || str_contains($message, '1142')) {
                $loadUsersError = 'Current MySQL account cannot read the global MySQL user table. You can still manage databases, and user creation may still work if CREATE USER privilege is granted.';
            } else {
                $loadUsersError = $message;
            }
        }

        return view('managedb::index', [
            'hosting' => $hosting,
            'prefix' => $db->prefixForHosting($hosting),
            'databases' => $databases,
            'users' => $users,
            'loadDbError' => $loadDbError,
            'loadUsersError' => $loadUsersError,
        ]);
    }

    public function createDatabase(Request $request, Hosting $hosting, ManageDbService $db): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        try {
            $created = $db->createDatabase($hosting, $validated['name']);

            return redirect()->route('hosts.db.manage', $hosting)->with('success', "Database created: {$created}");
        } catch (Throwable $e) {
            return redirect()->route('hosts.db.manage', $hosting)->withErrors(['db_create' => $e->getMessage()]);
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

            return redirect()->route('hosts.db.manage', $hosting)->with('success', "MySQL user created: {$created}");
        } catch (Throwable $e) {
            return redirect()->route('hosts.db.manage', $hosting)->withErrors(['user_create' => $e->getMessage()]);
        }
    }
}
