<?php

namespace Modules\FileManager\Services;

use App\Models\Hosting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

class HostFilesystemService
{
    public function createDirectory(Hosting $hosting, string $parentRelative, string $name): void
    {
        if (! HostPathGuard::isSafeNewName($name)) {
            throw new InvalidArgumentException('Invalid folder name.');
        }

        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        $parentAbs = HostPathGuard::walkDirectory($root, HostPathGuard::splitRelativePath($parentRelative));
        if ($parentAbs === null) {
            throw new RuntimeException('Parent folder not found.');
        }

        $target = $parentAbs.DIRECTORY_SEPARATOR.$name;
        if (file_exists($target)) {
            throw new RuntimeException('A file or folder with that name already exists.');
        }

        File::makeDirectory($target, 0755, true, true);
    }

    public function createFile(Hosting $hosting, string $parentRelative, string $name): void
    {
        if (! HostPathGuard::isSafeNewName($name)) {
            throw new InvalidArgumentException('Invalid file name.');
        }

        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        $parentAbs = HostPathGuard::walkDirectory($root, HostPathGuard::splitRelativePath($parentRelative));
        if ($parentAbs === null) {
            throw new RuntimeException('Parent folder not found.');
        }

        $target = $parentAbs.DIRECTORY_SEPARATOR.$name;
        if (file_exists($target)) {
            throw new RuntimeException('A file or folder with that name already exists.');
        }

        File::put($target, '');
    }

    /**
     * @param  list<string>  $relativePaths
     */
    public function deleteItems(Hosting $hosting, array $relativePaths): void
    {
        if ($relativePaths === []) {
            throw new InvalidArgumentException('Select at least one item to delete.');
        }

        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        foreach ($relativePaths as $relative) {
            $segments = HostPathGuard::splitRelativePath((string) $relative);
            if ($segments === []) {
                throw new InvalidArgumentException('Cannot delete the host root.');
            }

            $abs = HostPathGuard::itemRealPath($root, $segments);
            if ($abs === null || $abs === $root) {
                throw new RuntimeException('Invalid path: '.(string) $relative);
            }

            if (is_dir($abs)) {
                File::deleteDirectory($abs);
            } else {
                File::delete($abs);
            }
        }
    }

    /**
     * @param  list<string>  $relativePaths
     */
    public function moveItems(Hosting $hosting, array $relativePaths, string $destinationDirRelative): void
    {
        if ($relativePaths === []) {
            throw new InvalidArgumentException('Select at least one item to move.');
        }

        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        $destAbs = HostPathGuard::walkDirectory($root, HostPathGuard::splitRelativePath($destinationDirRelative));
        if ($destAbs === null) {
            throw new RuntimeException('Destination folder not found.');
        }

        foreach ($relativePaths as $relative) {
            $segments = HostPathGuard::splitRelativePath((string) $relative);
            if ($segments === []) {
                throw new InvalidArgumentException('Cannot move the host root.');
            }

            $fromAbs = HostPathGuard::itemRealPath($root, $segments);
            if ($fromAbs === null) {
                throw new RuntimeException('Invalid path: '.(string) $relative);
            }

            $baseName = $segments[array_key_last($segments)];
            $toAbs = $destAbs.DIRECTORY_SEPARATOR.$baseName;

            $destNorm = str_replace('\\', '/', $destAbs);
            $fromNorm = str_replace('\\', '/', $fromAbs);
            if ($fromNorm === $destNorm || str_starts_with($destNorm, $fromNorm.'/')) {
                throw new RuntimeException('Cannot move a folder into itself.');
            }

            $fromReal = realpath($fromAbs);
            if (file_exists($toAbs)) {
                $toReal = realpath($toAbs);
                if ($fromReal !== false && $toReal !== false && $fromReal === $toReal) {
                    // Already at destination (e.g. "move" to current parent) — not an error
                    continue;
                }
                throw new RuntimeException('Destination already contains: '.$baseName);
            }

            if (! $this->renameOrRelocate($fromAbs, $toAbs)) {
                $why = (string) (error_get_last()['message'] ?? '');
                $msg = 'Could not move: '.$baseName.($why !== '' ? ' ('.$why.')' : '');

                throw new RuntimeException($msg);
            }
        }
    }

    /**
     * Same-volume rename, or copy+delete when rename fails (e.g. EXDEV across mounts / Docker volumes).
     */
    private function renameOrRelocate(string $fromAbs, string $toAbs): bool
    {
        if (@rename($fromAbs, $toAbs)) {
            return true;
        }
        if (is_dir($fromAbs)) {
            if (File::copyDirectory($fromAbs, $toAbs) && File::deleteDirectory($fromAbs)) {
                return true;
            }

            if (is_dir($toAbs)) {
                File::deleteDirectory($toAbs);
            }

            return false;
        }
        if (is_file($fromAbs)) {
            if (File::copy($fromAbs, $toAbs) && @unlink($fromAbs)) {
                return true;
            }
            if (is_file($toAbs)) {
                @unlink($toAbs);
            }

            return false;
        }

        return false;
    }

    public function upload(Hosting $hosting, string $targetDirRelative, UploadedFile $file): void
    {
        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        $dirAbs = HostPathGuard::walkDirectory($root, HostPathGuard::splitRelativePath($targetDirRelative));
        if ($dirAbs === null) {
            throw new RuntimeException('Target folder not found.');
        }

        $name = $file->getClientOriginalName();
        if (! HostPathGuard::isSafeNewName($name)) {
            throw new InvalidArgumentException('Invalid file name.');
        }

        $target = $dirAbs.DIRECTORY_SEPARATOR.$name;
        if (file_exists($target)) {
            throw new RuntimeException('A file with that name already exists.');
        }

        $file->move($dirAbs, $name);
    }

    /**
     * Rename a file or folder under the host root (same parent directory; new basename only).
     */
    public function renameItem(Hosting $hosting, string $fromRelative, string $newName): string
    {
        $newName = trim($newName);
        if (! HostPathGuard::isSafeNewName($newName)) {
            throw new InvalidArgumentException('Invalid name.');
        }

        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        try {
            $segments = HostPathGuard::splitRelativePath($fromRelative);
        } catch (Throwable) {
            throw new InvalidArgumentException('Invalid path.');
        }

        if ($segments === []) {
            throw new InvalidArgumentException('Cannot rename the host root.');
        }

        $fromAbs = HostPathGuard::itemRealPath($root, $segments);
        if ($fromAbs === null) {
            throw new RuntimeException('Item not found.');
        }

        $parentSegments = array_slice($segments, 0, -1);
        $parentAbs = $parentSegments === []
            ? $root
            : HostPathGuard::walkDirectory($root, $parentSegments);
        if ($parentAbs === null) {
            throw new RuntimeException('Parent folder not found.');
        }

        $toAbs = $parentAbs.DIRECTORY_SEPARATOR.$newName;
        if (file_exists($toAbs)) {
            throw new RuntimeException('A file or folder with that name already exists.');
        }

        if (! @rename($fromAbs, $toAbs)) {
            throw new RuntimeException('Could not rename item.');
        }

        return HostPathGuard::joinRelative(array_merge($parentSegments, [$newName]));
    }

    /**
     * Whether the file can be opened in the text editor (by extension).
     */
    public static function isEditableFilename(string $filename): bool
    {
        $lower = strtolower($filename);
        if ($lower === '.env' || str_ends_with($lower, '.blade.php')) {
            return true;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($ext, config('file_manager.editable_extensions', []), true);
    }

    public function readTextFile(Hosting $hosting, string $relativePath): string
    {
        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        try {
            $segments = HostPathGuard::splitRelativePath($relativePath);
        } catch (Throwable) {
            throw new InvalidArgumentException('Invalid path.');
        }

        if ($segments === []) {
            throw new InvalidArgumentException('Invalid path.');
        }

        $abs = HostPathGuard::itemRealPath($root, $segments);
        if ($abs === null || ! is_file($abs)) {
            throw new RuntimeException('File not found.');
        }

        $name = basename($abs);
        if (! self::isEditableFilename($name)) {
            throw new InvalidArgumentException('This file type cannot be edited in the text editor.');
        }

        $max = (int) config('file_manager.max_edit_bytes', 2 * 1024 * 1024);
        $size = filesize($abs);
        if ($size === false || $size > $max) {
            throw new RuntimeException('File is too large to edit.');
        }

        $raw = File::get($abs);
        if (! mb_check_encoding($raw, 'UTF-8')) {
            throw new RuntimeException('File is not valid UTF-8 text.');
        }

        return $raw;
    }

    public function writeTextFile(Hosting $hosting, string $relativePath, string $content): void
    {
        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        try {
            $segments = HostPathGuard::splitRelativePath($relativePath);
        } catch (Throwable) {
            throw new InvalidArgumentException('Invalid path.');
        }

        if ($segments === []) {
            throw new InvalidArgumentException('Invalid path.');
        }

        $abs = HostPathGuard::itemRealPath($root, $segments);
        if ($abs === null || ! is_file($abs)) {
            throw new RuntimeException('File not found.');
        }

        $name = basename($abs);
        if (! self::isEditableFilename($name)) {
            throw new InvalidArgumentException('This file type cannot be edited in the text editor.');
        }

        $max = (int) config('file_manager.max_edit_bytes', 2 * 1024 * 1024);
        if (strlen($content) > $max) {
            throw new RuntimeException('Content is too large to save.');
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            throw new InvalidArgumentException('Content must be valid UTF-8 text.');
        }

        File::put($abs, $content);
    }

    /**
     * Duplicate a file in the same directory (e.g. "name - Copy.ext").
     */
    public function duplicateFile(Hosting $hosting, string $fromRelative): string
    {
        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        try {
            $segments = HostPathGuard::splitRelativePath($fromRelative);
        } catch (Throwable) {
            throw new InvalidArgumentException('Invalid path.');
        }

        if ($segments === []) {
            throw new InvalidArgumentException('Invalid path.');
        }

        $fromAbs = HostPathGuard::itemRealPath($root, $segments);
        if ($fromAbs === null || ! is_file($fromAbs)) {
            throw new RuntimeException('File not found.');
        }

        $parentSegments = array_slice($segments, 0, -1);
        $parentAbs = $parentSegments === []
            ? $root
            : HostPathGuard::walkDirectory($root, $parentSegments);
        if ($parentAbs === null) {
            throw new RuntimeException('Parent folder not found.');
        }

        $base = basename($fromAbs);

        $copyName = $base.' - Copy';
        $n = 1;
        while (file_exists($parentAbs.DIRECTORY_SEPARATOR.$copyName)) {
            $n++;
            $copyName = $base.' - Copy ('.$n.')';
        }

        $toAbs = $parentAbs.DIRECTORY_SEPARATOR.$copyName;
        if (! File::copy($fromAbs, $toAbs)) {
            throw new RuntimeException('Could not copy file.');
        }

        return HostPathGuard::joinRelative(array_merge($parentSegments, [$copyName]));
    }

    /**
     * Absolute path to a file under the host root (must be a regular file).
     */
    public function fileAbsolutePath(Hosting $hosting, string $relativePath): ?string
    {
        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            return null;
        }

        try {
            $segments = HostPathGuard::splitRelativePath($relativePath);
        } catch (Throwable) {
            return null;
        }

        if ($segments === []) {
            return null;
        }

        $abs = HostPathGuard::itemRealPath($root, $segments);

        return ($abs !== null && is_file($abs)) ? $abs : null;
    }

    public function compressItem(Hosting $hosting, string $fromRelative): string
    {
        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        try {
            $segments = HostPathGuard::splitRelativePath($fromRelative);
        } catch (Throwable) {
            throw new InvalidArgumentException('Invalid path.');
        }

        if ($segments === []) {
            throw new InvalidArgumentException('Invalid path.');
        }

        $fromAbs = HostPathGuard::itemRealPath($root, $segments);
        if ($fromAbs === null || (! is_file($fromAbs) && ! is_dir($fromAbs))) {
            throw new RuntimeException('Item not found.');
        }

        $parentSegments = array_slice($segments, 0, -1);
        $parentAbs = $parentSegments === []
            ? $root
            : HostPathGuard::walkDirectory($root, $parentSegments);
        if ($parentAbs === null) {
            throw new RuntimeException('Parent folder not found.');
        }

        $baseName = basename(str_replace('\\', '/', $fromAbs));
        $zipName = $this->nextAvailableName($parentAbs, $baseName.'.zip');
        $zipAbs = $parentAbs.DIRECTORY_SEPARATOR.$zipName;

        $zip = new ZipArchive;
        if ($zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create archive.');
        }

        if (is_dir($fromAbs)) {
            $this->addDirectoryToZip($zip, $fromAbs, $baseName);
        } else {
            $zip->addFile($fromAbs, $baseName);
        }

        $zip->close();

        return HostPathGuard::joinRelative(array_merge($parentSegments, [$zipName]));
    }

    public function extractArchive(Hosting $hosting, string $archiveRelative): string
    {
        $root = HostPathGuard::hostRootReal($hosting);
        if ($root === null) {
            throw new RuntimeException('Host root is not available.');
        }

        try {
            $segments = HostPathGuard::splitRelativePath($archiveRelative);
        } catch (Throwable) {
            throw new InvalidArgumentException('Invalid path.');
        }

        if ($segments === []) {
            throw new InvalidArgumentException('Invalid path.');
        }

        $archiveAbs = HostPathGuard::itemRealPath($root, $segments);
        if ($archiveAbs === null || ! is_file($archiveAbs)) {
            throw new RuntimeException('Archive not found.');
        }
        if (strtolower((string) pathinfo($archiveAbs, PATHINFO_EXTENSION)) !== 'zip') {
            throw new InvalidArgumentException('Only .zip archives can be extracted.');
        }

        $parentSegments = array_slice($segments, 0, -1);
        $parentAbs = $parentSegments === []
            ? $root
            : HostPathGuard::walkDirectory($root, $parentSegments);
        if ($parentAbs === null) {
            throw new RuntimeException('Parent folder not found.');
        }

        $baseFolder = (string) pathinfo(basename($archiveAbs), PATHINFO_FILENAME);
        if ($baseFolder === '') {
            $baseFolder = 'extracted';
        }
        $targetFolder = $this->nextAvailableName($parentAbs, $baseFolder);
        $targetAbs = $parentAbs.DIRECTORY_SEPARATOR.$targetFolder;

        File::makeDirectory($targetAbs, 0755, true, true);

        $zip = new ZipArchive;
        if ($zip->open($archiveAbs) !== true) {
            throw new RuntimeException('Could not open archive.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);
            if (! $this->isSafeZipEntryPath($entry)) {
                $zip->close();
                File::deleteDirectory($targetAbs);
                throw new RuntimeException('Archive contains unsafe paths.');
            }
        }

        $ok = $zip->extractTo($targetAbs);
        $zip->close();

        if (! $ok) {
            File::deleteDirectory($targetAbs);
            throw new RuntimeException('Could not extract archive.');
        }

        return HostPathGuard::joinRelative(array_merge($parentSegments, [$targetFolder]));
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourceAbs, string $zipRootName): void
    {
        $zip->addEmptyDir($zipRootName);

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceAbs, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $item) {
            $itemPath = (string) $item->getPathname();
            $relPath = substr($itemPath, strlen($sourceAbs) + 1);
            if ($relPath === false || $relPath === '') {
                continue;
            }
            $zipPath = $zipRootName.'/'.str_replace('\\', '/', $relPath);

            if ($item->isDir()) {
                $zip->addEmptyDir($zipPath);
            } else {
                $zip->addFile($itemPath, $zipPath);
            }
        }
    }

    private function isSafeZipEntryPath(string $entry): bool
    {
        $normalized = str_replace('\\', '/', $entry);
        if ($normalized === '' || str_starts_with($normalized, '/')) {
            return false;
        }

        foreach (explode('/', $normalized) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..' || str_contains($part, "\0")) {
                return false;
            }
        }

        return true;
    }

    private function nextAvailableName(string $parentAbs, string $name): string
    {
        $candidate = $name;
        $n = 1;

        while (file_exists($parentAbs.DIRECTORY_SEPARATOR.$candidate)) {
            $n++;
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $stem = $ext !== '' ? substr($name, 0, -1 * (strlen($ext) + 1)) : $name;
            $candidate = $ext !== ''
                ? $stem.' ('.$n.').'.$ext
                : $stem.' ('.$n.')';
        }

        return $candidate;
    }
}
