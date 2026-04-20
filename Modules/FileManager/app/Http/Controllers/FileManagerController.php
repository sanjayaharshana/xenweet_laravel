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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    public function openFile(Request $request, Hosting $hosting, HostFilesystemService $fs): BinaryFileResponse
    {
        $path = (string) $request->query('path', '');
        $abs = $fs->fileAbsolutePath($hosting, $path);
        abort_if($abs === null, 404);

        return response()->file($abs, [
            'Content-Disposition' => 'inline; filename="'.basename($abs).'"',
        ]);
    }

    public function edit(Request $request, Hosting $hosting, HostFilesystemService $fs): View|RedirectResponse
    {
        $path = (string) $request->query('path', '');

        try {
            $content = $fs->readTextFile($hosting, $path);
        } catch (Throwable $e) {
            return redirect()
                ->route('hosts.files.index', $this->listingParams($hosting, $this->parentRelative($path)))
                ->withErrors(['action' => $e->getMessage()]);
        }

        return view('filemanager::edit', [
            'hosting' => $hosting,
            'relativePath' => $path,
            'parentPath' => $this->parentRelative($path),
            'content' => $content,
        ]);
    }

    public function update(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse
    {
        $max = (int) config('file_manager.max_edit_bytes', 2 * 1024 * 1024);
        $validated = $request->validate([
            'path' => 'required|string|max:4096',
            'content' => 'nullable|string|max:'.$max,
        ]);

        try {
            $fs->writeTextFile($hosting, $validated['path'], (string) ($validated['content'] ?? ''));
        } catch (Throwable $e) {
            return redirect()
                ->route('hosts.files.edit', ['hosting' => $hosting, 'path' => $validated['path']])
                ->withErrors(['action' => $e->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('hosts.files.edit', ['hosting' => $hosting, 'path' => $validated['path']])
            ->with('success', 'File saved.');
    }

    public function duplicate(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'path' => 'nullable|string|max:4096',
        ]);

        try {
            $fs->duplicateFile($hosting, $validated['from']);
        } catch (Throwable $e) {
            return redirect()
                ->route('hosts.files.index', $this->listingParams($hosting, (string) ($validated['path'] ?? '')))
                ->withErrors(['action' => $e->getMessage()]);
        }

        return redirect()
            ->route('hosts.files.index', $this->listingParams($hosting, (string) ($validated['path'] ?? '')))
            ->with('success', 'File copied.');
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

        $name = basename(str_replace('\\', '/', $newRelative));

        return response()->json([
            'ok' => true,
            'relative' => $newRelative,
            'name' => $name,
            'editable' => HostFilesystemService::isEditableFilename($name),
        ]);
    }

    private function redirectBack(Hosting $hosting, string $path): RedirectResponse
    {
        return redirect()->route('hosts.files.index', $this->listingParams($hosting, $path));
    }

    /**
     * @return array{hosting: Hosting, path?: string}
     */
    private function listingParams(Hosting $hosting, string $path): array
    {
        $params = ['hosting' => $hosting];
        if ($path !== '') {
            $params['path'] = $path;
        }

        return $params;
    }

    private function parentRelative(string $relativePath): string
    {
        $p = str_replace('\\', '/', $relativePath);
        if (! str_contains($p, '/')) {
            return '';
        }

        return dirname($p);
    }
}
