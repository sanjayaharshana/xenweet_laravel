<?php

namespace Modules\FileManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\FileManager\Services\HostFilesystemService;
use Modules\FileManager\Services\HostFolderBrowser;
use Throwable;

class FileManagerController extends Controller
{
    public function index(Request $request, Hosting $hosting, HostFolderBrowser $browser): View
    {
        $path = (string) $request->query('path', '');

        $listing = $browser->listDirectory($hosting, $path);

        return view('filemanager::index', [
            'hosting' => $hosting,
            'listing' => $listing,
        ]);
    }

    public function mkdir(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'name' => 'required|string|max:255',
        ]);

        try {
            $fs->createDirectory($hosting, (string) ($validated['path'] ?? ''), trim($validated['name']));
        } catch (Throwable $e) {
            return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => $e->getMessage()]);
        }

        return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
            ->with('success', 'Folder created.');
    }

    public function touch(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'name' => 'required|string|max:255',
        ]);

        try {
            $fs->createFile($hosting, (string) ($validated['path'] ?? ''), trim($validated['name']));
        } catch (Throwable $e) {
            return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => $e->getMessage()]);
        }

        return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
            ->with('success', 'File created.');
    }

    public function destroy(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'items' => 'required|array|min:1',
            'items.*' => 'required|string|max:4096',
        ]);

        try {
            $fs->deleteItems($hosting, $validated['items']);
        } catch (Throwable $e) {
            return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => $e->getMessage()]);
        }

        return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
            ->with('success', 'Deleted selected items.');
    }

    public function move(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'items' => 'required|array|min:1',
            'items.*' => 'required|string|max:4096',
            'destination' => 'required|string|max:4096',
        ]);

        try {
            $fs->moveItems($hosting, $validated['items'], trim($validated['destination']));
        } catch (Throwable $e) {
            return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => $e->getMessage()]);
        }

        return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
            ->with('success', 'Moved selected items.');
    }

    public function upload(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'file' => 'required|file|max:51200',
        ]);

        $file = $request->file('file');
        if ($file === null) {
            return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => 'No file uploaded.']);
        }

        try {
            $fs->upload($hosting, (string) ($validated['path'] ?? ''), $file);
        } catch (Throwable $e) {
            return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => $e->getMessage()]);
        }

        return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
            ->with('success', 'File uploaded.');
    }

    public function rename(Request $request, Hosting $hosting, HostFilesystemService $fs): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'name' => 'required|string|max:255',
        ]);

        try {
            $newRelative = $fs->renameItem($hosting, $validated['from'], trim($validated['name']));
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'relative' => $newRelative,
            'name' => basename(str_replace('\\', '/', $newRelative)),
        ]);
    }

    private function redirectBack(Hosting $hosting, string $path): RedirectResponse
    {
        $params = ['hosting' => $hosting];
        if ($path !== '') {
            $params['path'] = $path;
        }

        return redirect()->route('hosts.files.index', $params);
    }
}
