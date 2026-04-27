<?php

namespace Modules\FileManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\FileManager\Services\FileManagerApplicationService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FileManagerController extends Controller
{
    public function index(Request $request, Hosting $hosting, FileManagerApplicationService $service): View
    {
        $path = (string) $request->query('path', '');
        $listing = $service->listDirectory($hosting, $path);

        return view('filemanager::index', [
            'hosting' => $hosting,
            'listing' => $listing,
        ]);
    }

    /**
     * JSON listing of a single directory (code editor file browser).
     */
    public function entries(Request $request, Hosting $hosting, FileManagerApplicationService $service): JsonResponse
    {
        $path = (string) $request->query('path', '');
        $workspaceRoot = (string) $request->query('root', '');
        $listing = $service->listEntries($hosting, $path, $workspaceRoot);
        if (! $listing['ok']) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($listing['message'] ?? 'Folder not found.'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'path' => $listing['relativePath'],
            'parentRelativePath' => $listing['parentRelativePath'],
            'entries' => $listing['entries'],
        ]);
    }

    public function codeEditor(Request $request, Hosting $hosting, FileManagerApplicationService $service): View|RedirectResponse
    {
        $workspaceRoot = (string) $request->query('path', '');
        $workspace = $service->resolveCodeEditorWorkspace($hosting, $workspaceRoot);
        if (! $workspace['ok']) {
            return redirect()
                ->route('hosts.files.index', ['hosting' => $hosting])
                ->withErrors(['action' => (string) ($workspace['message'] ?? 'Invalid folder.')]);
        }

        return view('filemanager::code-editor', [
            'hosting' => $hosting,
            'workspacePath' => (string) ($workspace['workspacePath'] ?? ''),
        ]);
    }

    public function mkdir(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'name' => 'required|string|max:255',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $service->createDirectory($hosting, $path, (string) $validated['name']);
        } catch (Throwable $e) {
            return $this->actionError($service, $request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($service, $request, $hosting, $path, 'Folder created.');
    }

    public function touch(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'name' => 'required|string|max:255',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $service->createFile($hosting, $path, (string) $validated['name']);
        } catch (Throwable $e) {
            return $this->actionError($service, $request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($service, $request, $hosting, $path, 'File created.');
    }

    public function destroy(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'items_json' => 'nullable|string|max:1048576',
            'items' => 'nullable|array',
            'items.*' => 'nullable|string|max:4096',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $items = $service->resolveBulkItemPaths($request->input('items_json'), $request->input('items', []));
        } catch (InvalidArgumentException $e) {
            return $this->actionError($service, $request, $hosting, $path, $e->getMessage());
        }

        if ($items === []) {
            return $this->actionError($service, $request, $hosting, $path, 'Select at least one item.');
        }

        try {
            $service->deleteItems($hosting, $items);
        } catch (Throwable $e) {
            return $this->actionError($service, $request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($service, $request, $hosting, $path, 'Deleted selected items.');
    }

    public function move(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'items_json' => 'nullable|string|max:1048576',
            'items' => 'nullable|array',
            'items.*' => 'nullable|string|max:4096',
            'destination' => 'required|string|max:4096',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $items = $service->resolveBulkItemPaths($request->input('items_json'), $request->input('items', []));
        } catch (InvalidArgumentException $e) {
            return $this->actionError($service, $request, $hosting, $path, $e->getMessage());
        }

        if ($items === []) {
            return $this->actionError($service, $request, $hosting, $path, 'Select at least one item.');
        }

        try {
            $service->moveItems($hosting, $items, (string) $validated['destination']);
        } catch (Throwable $e) {
            return $this->actionError($service, $request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($service, $request, $hosting, $path, 'Moved selected items.');
    }

    public function upload(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'file' => 'required',
            'file.*' => 'file|max:51200',
        ]);

        $files = $service->normalizeUploadedFiles($request->file('file'));
        if ($files === []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No file uploaded.',
                ], 422);
            }

            return $this->redirectBack($service, $hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => 'No file uploaded.']);
        }

        try {
            $service->uploadFiles($hosting, (string) ($validated['path'] ?? ''), $files);
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return $this->redirectBack($service, $hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => count($files) > 1 ? 'Files uploaded.' : 'File uploaded.',
            ]);
        }

        return $this->redirectBack($service, $hosting, (string) ($validated['path'] ?? ''))
            ->with('success', 'File uploaded.');
    }

    public function openFile(Request $request, Hosting $hosting, FileManagerApplicationService $service): BinaryFileResponse
    {
        $path = (string) $request->query('path', '');
        $abs = $service->openFileAbsolutePath($hosting, $path);
        abort_if($abs === null, 404);

        return response()->file($abs, [
            'Content-Disposition' => 'inline; filename="'.basename($abs).'"',
        ]);
    }

    public function edit(Request $request, Hosting $hosting, FileManagerApplicationService $service): View|RedirectResponse|JsonResponse
    {
        $path = (string) $request->query('path', '');

        try {
            $content = $service->readEditableTextFile($hosting, $path);
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('hosts.files.index', $service->listingParams($hosting, $service->parentRelative($path)))
                ->withErrors(['action' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'path' => $path,
                'content' => $content,
            ]);
        }

        return view('filemanager::edit', [
            'hosting' => $hosting,
            'relativePath' => $path,
            'parentPath' => $service->parentRelative($path),
            'content' => $content,
        ]);
    }

    public function update(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $max = (int) config('file_manager.max_edit_bytes', 2 * 1024 * 1024);
        $validated = $request->validate([
            'path' => 'required|string|max:4096',
            'content' => 'nullable|string|max:'.$max,
        ]);

        try {
            $service->writeEditableTextFile($hosting, (string) $validated['path'], (string) ($validated['content'] ?? ''));
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('hosts.files.edit', ['hosting' => $hosting, 'path' => $validated['path']])
                ->withErrors(['action' => $e->getMessage()])
                ->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'File saved.',
            ]);
        }

        return redirect()
            ->route('hosts.files.edit', ['hosting' => $hosting, 'path' => $validated['path']])
            ->with('success', 'File saved.');
    }

    public function duplicate(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'path' => 'nullable|string|max:4096',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $service->duplicateFile($hosting, (string) $validated['from']);
        } catch (Throwable $e) {
            return $this->actionError($service, $request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($service, $request, $hosting, $path, 'File copied.');
    }

    public function compress(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'path' => 'nullable|string|max:4096',
        ]);

        $job = $service->queueCompress($hosting, (string) $validated['from']);

        $path = (string) ($validated['path'] ?? '');
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $job['message'],
                'queue_token' => $job['token'],
                'queue_status_url' => route('hosts.files.queue-status', $hosting),
                'reload_url' => route('hosts.files.index', $service->listingParams($hosting, $path)),
            ]);
        }

        return redirect()
            ->route('hosts.files.index', $service->listingParams($hosting, $path))
            ->with('success', $job['message'])
            ->with('fm_queue_token', $job['token']);
    }

    public function extract(Request $request, Hosting $hosting, FileManagerApplicationService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'path' => 'nullable|string|max:4096',
        ]);

        $job = $service->queueExtract($hosting, (string) $validated['from']);

        $path = (string) ($validated['path'] ?? '');
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $job['message'],
                'queue_token' => $job['token'],
                'queue_status_url' => route('hosts.files.queue-status', $hosting),
                'reload_url' => route('hosts.files.index', $service->listingParams($hosting, $path)),
            ]);
        }

        return redirect()
            ->route('hosts.files.index', $service->listingParams($hosting, $path))
            ->with('success', $job['message'])
            ->with('fm_queue_token', $job['token']);
    }

    public function queueStatus(Request $request, Hosting $hosting, FileManagerApplicationService $service): JsonResponse
    {
        try {
            return response()->json($service->queueStatus((string) $request->query('token', '')));
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function rename(Request $request, Hosting $hosting, FileManagerApplicationService $service): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'name' => 'required|string|max:255',
        ]);

        try {
            $renamed = $service->renameItem($hosting, (string) $validated['from'], (string) $validated['name']);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'relative' => $renamed['relative'],
            'name' => $renamed['name'],
            'editable' => $renamed['editable'],
        ]);
    }

    private function redirectBack(FileManagerApplicationService $service, Hosting $hosting, string $path): RedirectResponse
    {
        return redirect()->route('hosts.files.index', $service->listingParams($hosting, $path));
    }

    private function actionSuccess(FileManagerApplicationService $service, Request $request, Hosting $hosting, string $path, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'reload_url' => route('hosts.files.index', $service->listingParams($hosting, $path)),
            ]);
        }

        return $this->redirectBack($service, $hosting, $path)->with('success', $message);
    }

    private function actionError(FileManagerApplicationService $service, Request $request, Hosting $hosting, string $path, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 422);
        }

        return $this->redirectBack($service, $hosting, $path)->withErrors(['action' => $message]);
    }
}
