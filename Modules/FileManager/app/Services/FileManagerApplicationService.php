<?php

namespace Modules\FileManager\Services;

use App\Models\Hosting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Modules\FileManager\Jobs\CompressItemJob;
use Modules\FileManager\Jobs\ExtractArchiveJob;

class FileManagerApplicationService
{
    private const BULK_PATHS_MAX = 5000;

    public function __construct(
        private readonly HostFolderBrowser $browser,
        private readonly HostFilesystemService $filesystem
    ) {
    }

    public function listDirectory(Hosting $hosting, string $path): array
    {
        return $this->browser->listDirectory($hosting, $path);
    }

    public function listEntries(Hosting $hosting, string $path, string $workspaceRoot): array
    {
        if (! $this->isPathUnderWorkspace($path, $workspaceRoot)) {
            return [
                'ok' => false,
                'message' => 'Path is outside the selected workspace folder.',
            ];
        }

        $listing = $this->browser->listDirectory($hosting, $path);
        if (! $listing['ok']) {
            return [
                'ok' => false,
                'message' => (string) ($listing['error'] ?? 'Folder not found.'),
            ];
        }

        return [
            'ok' => true,
            'path' => (string) $listing['relativePath'],
            'parentRelativePath' => $listing['parentRelativePath'],
            'entries' => $listing['entries'],
        ];
    }

    public function resolveCodeEditorWorkspace(Hosting $hosting, string $workspaceRoot): array
    {
        $check = $this->browser->listDirectory($hosting, $workspaceRoot);
        if (! $check['ok']) {
            return [
                'ok' => false,
                'message' => (string) ($check['error'] ?? 'Invalid folder.'),
            ];
        }

        $relativePath = (string) $check['relativePath'];

        return [
            'ok' => true,
            'workspacePath' => $relativePath !== $workspaceRoot ? $relativePath : $workspaceRoot,
        ];
    }

    public function createDirectory(Hosting $hosting, string $path, string $name): void
    {
        $this->filesystem->createDirectory($hosting, $path, trim($name));
    }

    public function createFile(Hosting $hosting, string $path, string $name): void
    {
        $this->filesystem->createFile($hosting, $path, trim($name));
    }

    /**
     * @param mixed $uploaded
     * @return array<int, UploadedFile>
     */
    public function normalizeUploadedFiles(mixed $uploaded): array
    {
        $files = is_array($uploaded) ? array_values(array_filter($uploaded)) : ($uploaded ? [$uploaded] : []);

        return array_values(array_filter($files, static fn ($one): bool => $one instanceof UploadedFile));
    }

    /**
     * @param array<int, UploadedFile> $files
     */
    public function uploadFiles(Hosting $hosting, string $path, array $files): void
    {
        foreach ($files as $file) {
            $this->filesystem->upload($hosting, $path, $file);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @return array<int, string>
     */
    public function resolveBulkItemPaths(mixed $itemsJson, mixed $items): array
    {
        if (is_string($itemsJson) && $itemsJson !== '') {
            try {
                $decoded = json_decode($itemsJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new InvalidArgumentException('Invalid item list (JSON).');
            }
            if (! is_array($decoded)) {
                throw new InvalidArgumentException('Invalid item list format.');
            }

            return $this->normalizePathList($decoded);
        }

        if (! is_array($items) || $items === []) {
            return [];
        }

        return $this->normalizePathList($items);
    }

    /**
     * @param array<int|string, mixed> $raw
     * @return array<int, string>
     */
    private function normalizePathList(array $raw): array
    {
        if (count($raw) > self::BULK_PATHS_MAX) {
            throw new InvalidArgumentException('Too many items selected (max '.self::BULK_PATHS_MAX.').');
        }

        $out = [];
        foreach ($raw as $one) {
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

    /**
     * @param array<int, string> $items
     */
    public function deleteItems(Hosting $hosting, array $items): void
    {
        $this->filesystem->deleteItems($hosting, $items);
    }

    /**
     * @param array<int, string> $items
     */
    public function moveItems(Hosting $hosting, array $items, string $destination): void
    {
        $this->filesystem->moveItems($hosting, $items, trim($destination));
    }

    public function openFileAbsolutePath(Hosting $hosting, string $path): ?string
    {
        return $this->filesystem->fileAbsolutePath($hosting, $path);
    }

    public function readEditableTextFile(Hosting $hosting, string $path): string
    {
        return $this->filesystem->readTextFile($hosting, $path);
    }

    public function writeEditableTextFile(Hosting $hosting, string $path, string $content): void
    {
        $this->filesystem->writeTextFile($hosting, $path, $content);
    }

    public function duplicateFile(Hosting $hosting, string $from): void
    {
        $this->filesystem->duplicateFile($hosting, $from);
    }

    public function queueCompress(Hosting $hosting, string $from): array
    {
        $token = (string) Str::uuid();
        Cache::put('file_manager_job:'.$token, [
            'status' => 'pending',
            'message' => 'Compress queued.',
        ], now()->addMinutes(15));

        CompressItemJob::dispatch($hosting->id, $from, $token);

        return [
            'token' => $token,
            'message' => 'Compress queued.',
        ];
    }

    public function queueExtract(Hosting $hosting, string $from): array
    {
        $token = (string) Str::uuid();
        Cache::put('file_manager_job:'.$token, [
            'status' => 'pending',
            'message' => 'Extract queued.',
        ], now()->addMinutes(15));

        ExtractArchiveJob::dispatch($hosting->id, $from, $token);

        return [
            'token' => $token,
            'message' => 'Extract queued.',
        ];
    }

    public function queueStatus(string $token): array
    {
        if ($token === '') {
            throw new InvalidArgumentException('Missing token.');
        }

        $data = Cache::get('file_manager_job:'.$token);
        if (! is_array($data)) {
            return [
                'ok' => true,
                'status' => 'unknown',
                'done' => true,
                'message' => 'No status found.',
            ];
        }

        $status = (string) ($data['status'] ?? 'pending');

        return [
            'ok' => true,
            'status' => $status,
            'done' => in_array($status, ['done', 'failed', 'unknown'], true),
            'message' => (string) ($data['message'] ?? ''),
        ];
    }

    public function renameItem(Hosting $hosting, string $from, string $name): array
    {
        $newRelative = $this->filesystem->renameItem($hosting, $from, trim($name));
        $newName = basename(str_replace('\\', '/', $newRelative));

        return [
            'relative' => $newRelative,
            'name' => $newName,
            'editable' => HostFilesystemService::isEditableFilename($newName),
        ];
    }

    /**
     * @return array{hosting: Hosting, path?: string}
     */
    public function listingParams(Hosting $hosting, string $path): array
    {
        $params = ['hosting' => $hosting];
        if ($path !== '') {
            $params['path'] = $path;
        }

        return $params;
    }

    public function parentRelative(string $relativePath): string
    {
        $p = str_replace('\\', '/', $relativePath);
        if (! str_contains($p, '/')) {
            return '';
        }

        return dirname($p);
    }

    public function isPathUnderWorkspace(string $path, string $root): bool
    {
        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', $root);
        if ($root === '') {
            return true;
        }
        if ($path === $root) {
            return true;
        }

        return str_starts_with($path, rtrim($root, '/').'/');
    }
}
