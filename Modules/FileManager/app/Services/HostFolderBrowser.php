<?php

namespace Modules\FileManager\Services;

use App\Models\Hosting;
use FilesystemIterator;
use InvalidArgumentException;

class HostFolderBrowser
{
    private const TREE_MAX_DEPTH = 14;

    private const TREE_MAX_NODES = 1000;

    /**
     * List files and directories under the hosting account root.
     *
     * @return array{
     *     ok: bool,
     *     error: string|null,
     *     relativePath: string,
     *     parentRelativePath: string|null,
     *     breadcrumbs: list<array{label: string, path: string}>,
     *     entries: list<array{name: string, is_dir: bool, size: int|null, mtime: int|null, relative: string}>,
     *     tree: list<array{name: string, relative: string, open: bool, children: list, truncated: bool}>,
     *     tree_truncated: bool
     * }
     */
    public function listDirectory(Hosting $hosting, string $relativePath): array
    {
        $rootReal = HostPathGuard::hostRootReal($hosting);
        if ($rootReal === null) {
            $hostRoot = trim((string) $hosting->host_root_path);

            return $this->emptyState($hostRoot === ''
                ? 'No host folder is set for this account yet. Run provisioning or set host paths.'
                : 'Host folder does not exist on this server: '.$hostRoot);
        }

        try {
            $segments = HostPathGuard::splitRelativePath($relativePath);
        } catch (InvalidArgumentException) {
            return $this->emptyState('Invalid path.');
        }

        $absolute = HostPathGuard::walkDirectory($rootReal, $segments);
        if ($absolute === null) {
            return $this->emptyState('Folder not found or not inside this host account.');
        }

        $relativeNormalized = HostPathGuard::joinRelative($segments);

        $breadcrumbs = $this->buildBreadcrumbs($segments);

        $parentSegments = array_slice($segments, 0, -1);
        $parentRelativePath = count($parentSegments) === 0
            ? null
            : HostPathGuard::joinRelative($parentSegments);

        $entries = [];
        $iterator = new FilesystemIterator($absolute, FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $fileInfo) {
            $name = $fileInfo->getFilename();
            $fullPath = $absolute.DIRECTORY_SEPARATOR.$name;
            $resolved = realpath($fullPath);
            if ($resolved === false || ! HostPathGuard::isUnderRoot($rootReal, $resolved)) {
                continue;
            }

            $isDir = $fileInfo->isDir();
            $childSegments = array_merge($segments, [$name]);
            $childRelative = HostPathGuard::joinRelative($childSegments);

            $mtime = $fileInfo->getMTime();
            $size = $isDir ? null : $fileInfo->getSize();

            $entries[] = [
                'name' => $name,
                'is_dir' => $isDir,
                'size' => $size,
                'mtime' => $mtime !== false ? $mtime : null,
                'relative' => $childRelative,
            ];
        }

        usort($entries, function (array $a, array $b): int {
            if ($a['is_dir'] !== $b['is_dir']) {
                return $a['is_dir'] ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        $nodeCount = 0;
        $tree = $this->buildFolderTreeNodes(
            $rootReal,
            $rootReal,
            [],
            $relativeNormalized,
            0,
            self::TREE_MAX_DEPTH,
            self::TREE_MAX_NODES,
            $nodeCount
        );

        return [
            'ok' => true,
            'error' => null,
            'relativePath' => $relativeNormalized,
            'parentRelativePath' => $parentRelativePath,
            'breadcrumbs' => $breadcrumbs,
            'entries' => $entries,
            'tree' => $tree,
            'tree_truncated' => $nodeCount >= self::TREE_MAX_NODES,
        ];
    }

    /**
     * @param  list<string>  $segments
     * @return list<array{name: string, relative: string, open: bool, children: list, truncated: bool}>
     */
    private function buildFolderTreeNodes(
        string $rootReal,
        string $absoluteDir,
        array $segments,
        string $currentRelativePath,
        int $depth,
        int $maxDepth,
        int $maxNodes,
        int &$nodeCount
    ): array {
        if ($depth >= $maxDepth || $nodeCount >= $maxNodes) {
            return [];
        }

        $dirs = [];
        $iterator = new FilesystemIterator($absoluteDir, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isDir()) {
                continue;
            }
            $name = $fileInfo->getFilename();
            $fullPath = $absoluteDir.DIRECTORY_SEPARATOR.$name;
            $resolved = realpath($fullPath);
            if ($resolved === false || ! HostPathGuard::isUnderRoot($rootReal, $resolved)) {
                continue;
            }
            $dirs[] = ['name' => $name, 'path' => $resolved];
        }

        usort($dirs, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        $nodes = [];
        foreach ($dirs as $dir) {
            $nodeCount++;
            if ($nodeCount > $maxNodes) {
                break;
            }

            $name = $dir['name'];
            $childSegments = array_merge($segments, [$name]);
            $relative = HostPathGuard::joinRelative($childSegments);
            $open = $currentRelativePath === $relative || str_starts_with($currentRelativePath, $relative.'/');

            $childNodes = $this->buildFolderTreeNodes(
                $rootReal,
                $dir['path'],
                $childSegments,
                $currentRelativePath,
                $depth + 1,
                $maxDepth,
                $maxNodes,
                $nodeCount
            );

            $truncated = $depth + 1 >= $maxDepth && $this->directoryHasSubdirectoriesUnderRoot($rootReal, $dir['path']);

            $nodes[] = [
                'name' => $name,
                'relative' => $relative,
                'open' => $open,
                'children' => $childNodes,
                'truncated' => $truncated,
            ];
        }

        return $nodes;
    }

    private function directoryHasSubdirectoriesUnderRoot(string $rootReal, string $absoluteDir): bool
    {
        if (! is_dir($absoluteDir)) {
            return false;
        }

        $iterator = new FilesystemIterator($absoluteDir, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isDir()) {
                continue;
            }
            $fullPath = $absoluteDir.DIRECTORY_SEPARATOR.$fileInfo->getFilename();
            $resolved = realpath($fullPath);

            if ($resolved !== false && HostPathGuard::isUnderRoot($rootReal, $resolved)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $segments
     * @return list<array{label: string, path: string}>
     */
    private function buildBreadcrumbs(array $segments): array
    {
        $crumbs = [];
        $acc = [];
        foreach ($segments as $segment) {
            $acc[] = $segment;
            $crumbs[] = [
                'label' => $segment,
                'path' => HostPathGuard::joinRelative($acc),
            ];
        }

        return $crumbs;
    }

    /**
     * @return array{
     *     ok: bool,
     *     error: string,
     *     relativePath: string,
     *     parentRelativePath: null,
     *     breadcrumbs: list<array{label: string, path: string}>,
     *     entries: list<array{name: string, is_dir: bool, size: int|null, mtime: int|null, relative: string}>,
     *     tree: list<array{name: string, relative: string, open: bool, children: list, truncated: bool}>,
     *     tree_truncated: bool
     * }
     */
    private function emptyState(string $message): array
    {
        return [
            'ok' => false,
            'error' => $message,
            'relativePath' => '',
            'parentRelativePath' => null,
            'breadcrumbs' => [],
            'entries' => [],
            'tree' => [],
            'tree_truncated' => false,
        ];
    }
}
