<?php

namespace Modules\FileManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use JsonException;
use Modules\FileManager\Jobs\CompressItemJob;
use Modules\FileManager\Jobs\ExtractArchiveJob;
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

    /**
     * JSON listing of a single directory (code editor file browser).
     */
    public function entries(Request $request, Hosting $hosting, HostFolderBrowser $browser): JsonResponse
    {
        $path = (string) $request->query('path', '');
        $workspaceRoot = (string) $request->query('root', '');

        if (! $this->isPathUnderWorkspace($path, $workspaceRoot)) {
            return response()->json([
                'ok' => false,
                'message' => 'Path is outside the selected workspace folder.',
            ], 422);
        }

        $listing = $browser->listDirectory($hosting, $path);
        if (! $listing['ok']) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($listing['error'] ?? 'Folder not found.'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'path' => $listing['relativePath'],
            'parentRelativePath' => $listing['parentRelativePath'],
            'entries' => $listing['entries'],
        ]);
    }

    public function codeEditor(Request $request, Hosting $hosting, HostFolderBrowser $browser): View|RedirectResponse
    {
        $workspaceRoot = (string) $request->query('path', '');

        $check = $browser->listDirectory($hosting, $workspaceRoot);
        if (! $check['ok']) {
            return redirect()
                ->route('hosts.files.index', ['hosting' => $hosting])
                ->withErrors(['action' => (string) ($check['error'] ?? 'Invalid folder.')]);
        }

        $relativePath = (string) $check['relativePath'];
        if ($relativePath !== $workspaceRoot) {
            $workspaceRoot = $relativePath;
        }

        return view('filemanager::code-editor', [
            'hosting' => $hosting,
            'workspacePath' => $workspaceRoot,
        ]);
    }

    public function mkdir(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'name' => 'required|string|max:255',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $fs->createDirectory($hosting, $path, trim($validated['name']));
        } catch (Throwable $e) {
            return $this->actionError($request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($request, $hosting, $path, 'Folder created.');
    }

    public function touch(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'name' => 'required|string|max:255',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $fs->createFile($hosting, $path, trim($validated['name']));
        } catch (Throwable $e) {
            return $this->actionError($request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($request, $hosting, $path, 'File created.');
    }

    public function destroy(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'items_json' => 'nullable|string|max:1048576',
            'items' => 'nullable|array',
            'items.*' => 'nullable|string|max:4096',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $items = $this->resolveBulkItemPaths($request);
        } catch (InvalidArgumentException $e) {
            return $this->actionError($request, $hosting, $path, $e->getMessage());
        }

        if ($items === []) {
            return $this->actionError($request, $hosting, $path, 'Select at least one item.');
        }

        try {
            $fs->deleteItems($hosting, $items);
        } catch (Throwable $e) {
            return $this->actionError($request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($request, $hosting, $path, 'Deleted selected items.');
    }

    public function move(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse|JsonResponse
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
            $items = $this->resolveBulkItemPaths($request);
        } catch (InvalidArgumentException $e) {
            return $this->actionError($request, $hosting, $path, $e->getMessage());
        }

        if ($items === []) {
            return $this->actionError($request, $hosting, $path, 'Select at least one item.');
        }

        try {
            $fs->moveItems($hosting, $items, trim($validated['destination']));
        } catch (Throwable $e) {
            return $this->actionError($request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($request, $hosting, $path, 'Moved selected items.');
    }

    public function upload(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'path' => 'nullable|string|max:4096',
            'file' => 'required',
            'file.*' => 'file|max:51200',
        ]);

        $uploaded = $request->file('file');
        $files = is_array($uploaded) ? array_values(array_filter($uploaded)) : ($uploaded ? [$uploaded] : []);
        if ($files === []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No file uploaded.',
                ], 422);
            }

            return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => 'No file uploaded.']);
        }

        try {
            foreach ($files as $file) {
                $fs->upload($hosting, (string) ($validated['path'] ?? ''), $file);
            }
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return $this->redirectBack($hosting, (string) ($validated['path'] ?? ''))
                ->withErrors(['action' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => count($files) > 1 ? 'Files uploaded.' : 'File uploaded.',
            ]);
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

    public function edit(Request $request, Hosting $hosting, HostFilesystemService $fs): View|RedirectResponse|JsonResponse
    {
        $path = (string) $request->query('path', '');

        try {
            $content = $fs->readTextFile($hosting, $path);
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('hosts.files.index', $this->listingParams($hosting, $this->parentRelative($path)))
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
            'parentPath' => $this->parentRelative($path),
            'content' => $content,
        ]);
    }

    public function update(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse|JsonResponse
    {
        $max = (int) config('file_manager.max_edit_bytes', 2 * 1024 * 1024);
        $validated = $request->validate([
            'path' => 'required|string|max:4096',
            'content' => 'nullable|string|max:'.$max,
        ]);

        try {
            $fs->writeTextFile($hosting, $validated['path'], (string) ($validated['content'] ?? ''));
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

    public function duplicate(Request $request, Hosting $hosting, HostFilesystemService $fs): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'path' => 'nullable|string|max:4096',
        ]);

        $path = (string) ($validated['path'] ?? '');

        try {
            $fs->duplicateFile($hosting, $validated['from']);
        } catch (Throwable $e) {
            return $this->actionError($request, $hosting, $path, $e->getMessage());
        }

        return $this->actionSuccess($request, $hosting, $path, 'File copied.');
    }

    public function compress(Request $request, Hosting $hosting): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'path' => 'nullable|string|max:4096',
        ]);

        $token = (string) Str::uuid();
        Cache::put('file_manager_job:'.$token, [
            'status' => 'pending',
            'message' => 'Compress queued.',
        ], now()->addMinutes(15));

        CompressItemJob::dispatch($hosting->id, $validated['from'], $token);

        $path = (string) ($validated['path'] ?? '');
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Compress queued.',
                'queue_token' => $token,
                'queue_status_url' => route('hosts.files.queue-status', $hosting),
                'reload_url' => route('hosts.files.index', $this->listingParams($hosting, $path)),
            ]);
        }

        return redirect()
            ->route('hosts.files.index', $this->listingParams($hosting, $path))
            ->with('success', 'Compress queued.')
            ->with('fm_queue_token', $token);
    }

    public function extract(Request $request, Hosting $hosting): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|string|max:4096',
            'path' => 'nullable|string|max:4096',
        ]);

        $token = (string) Str::uuid();
        Cache::put('file_manager_job:'.$token, [
            'status' => 'pending',
            'message' => 'Extract queued.',
        ], now()->addMinutes(15));

        ExtractArchiveJob::dispatch($hosting->id, $validated['from'], $token);

        $path = (string) ($validated['path'] ?? '');
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Extract queued.',
                'queue_token' => $token,
                'queue_status_url' => route('hosts.files.queue-status', $hosting),
                'reload_url' => route('hosts.files.index', $this->listingParams($hosting, $path)),
            ]);
        }

        return redirect()
            ->route('hosts.files.index', $this->listingParams($hosting, $path))
            ->with('success', 'Extract queued.')
            ->with('fm_queue_token', $token);
    }

    public function queueStatus(Request $request, Hosting $hosting): JsonResponse
    {
        $token = (string) $request->query('token', '');
        if ($token === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Missing token.',
            ], 422);
        }

        $data = Cache::get('file_manager_job:'.$token);
        if (! is_array($data)) {
            return response()->json([
                'ok' => true,
                'status' => 'unknown',
                'done' => true,
                'message' => 'No status found.',
            ]);
        }

        $status = (string) ($data['status'] ?? 'pending');

        return response()->json([
            'ok' => true,
            'status' => $status,
            'done' => in_array($status, ['done', 'failed', 'unknown'], true),
            'message' => (string) ($data['message'] ?? ''),
        ]);
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

    private function actionSuccess(Request $request, Hosting $hosting, string $path, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'reload_url' => route('hosts.files.index', $this->listingParams($hosting, $path)),
            ]);
        }

        return $this->redirectBack($hosting, $path)->with('success', $message);
    }

    private function actionError(Request $request, Hosting $hosting, string $path, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 422);
        }

        return $this->redirectBack($hosting, $path)->withErrors(['action' => $message]);
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

    /**
     * Whether a relative path is the workspace root or a descendant (code editor path guard).
     */
    private function isPathUnderWorkspace(string $path, string $root): bool
    {
        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', $root);
        if ($root === '') {
            return true;
        }
        if ($path === $root) {
            return true;
        }
        $prefix = rtrim($root, '/').'/';

        return str_starts_with($path, $prefix);
    }

    private const BULK_PATHS_MAX = 5000;

    /**
     * Paths from a single `items_json` (preferred) or legacy `items` array, avoiding many POST vars
     * that are truncated by PHP’s max_input_vars.
     *
     * @return array<int, string>
     */
    private function resolveBulkItemPaths(Request $request): array
    {
        $rawJson = $request->input('items_json');
        if (is_string($rawJson) && $rawJson !== '') {
            try {
                $decoded = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new InvalidArgumentException('Invalid item list (JSON).');
            }
            if (! is_array($decoded)) {
                throw new InvalidArgumentException('Invalid item list format.');
            }
            if (count($decoded) > self::BULK_PATHS_MAX) {
                throw new InvalidArgumentException('Too many items selected (max '.self::BULK_PATHS_MAX.').');
            }
            $out = [];
            foreach ($decoded as $one) {
                if (! is_string($one) && ! is_int($one) && ! is_float($one)) {
                    continue;
                }
                $p = trim((string) $one);
                if ($p === '' || strlen($p) > 4096) {
                    throw new InvalidArgumentException('Each path must be 1 to 4,096 characters.');
                }
                $out[] = $p;
            }

            return $out;
        }

        $items = $request->input('items', []);
        if (! is_array($items) || $items === []) {
            return [];
        }
        if (count($items) > self::BULK_PATHS_MAX) {
            throw new InvalidArgumentException('Too many items selected (max '.self::BULK_PATHS_MAX.').');
        }
        $out = [];
        foreach ($items as $one) {
            if (! is_string($one) && ! is_int($one) && ! is_float($one)) {
                continue;
            }
            $p = trim((string) $one);
            if ($p === '' || strlen($p) > 4096) {
                throw new InvalidArgumentException('Each path must be 1 to 4,096 characters.');
            }
            $out[] = $p;
        }

        return $out;
    }
}
