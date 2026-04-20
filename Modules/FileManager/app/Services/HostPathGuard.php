<?php

namespace Modules\FileManager\Services;

use App\Models\Hosting;
use InvalidArgumentException;

final class HostPathGuard
{
    public static function hostRootReal(Hosting $hosting): ?string
    {
        $hostRoot = trim((string) $hosting->host_root_path);
        if ($hostRoot === '' || ! is_dir($hostRoot)) {
            return null;
        }

        $rootReal = realpath($hostRoot);

        return ($rootReal !== false && is_dir($rootReal)) ? $rootReal : null;
    }

    /**
     * @return list<string>
     */
    public static function splitRelativePath(string $relativePath): array
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return [];
        }

        $parts = explode('/', $relativePath);
        $out = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                throw new InvalidArgumentException('Path traversal');
            }
            if (str_contains($part, "\0")) {
                throw new InvalidArgumentException('Invalid path');
            }
            $out[] = $part;
        }

        return $out;
    }

    /**
     * @param  list<string>  $segments
     */
    public static function joinRelative(array $segments): string
    {
        return implode('/', $segments);
    }

    /**
     * @param  list<string>  $segments
     */
    public static function walkDirectory(string $rootReal, array $segments): ?string
    {
        $path = $rootReal;
        foreach ($segments as $segment) {
            $path .= DIRECTORY_SEPARATOR.$segment;
            if (! is_dir($path)) {
                return null;
            }
            $real = realpath($path);
            if ($real === false || ! self::isUnderRoot($rootReal, $real)) {
                return null;
            }
            $path = $real;
        }

        return $path;
    }

    /**
     * Resolve an existing file or directory under the host root.
     *
     * @param  list<string>  $segments
     */
    public static function itemRealPath(string $rootReal, array $segments): ?string
    {
        if ($segments === []) {
            return $rootReal;
        }

        $path = $rootReal;
        $lastIndex = count($segments) - 1;
        foreach ($segments as $i => $segment) {
            $path .= DIRECTORY_SEPARATOR.$segment;
            if ($i < $lastIndex) {
                if (! is_dir($path)) {
                    return null;
                }
                $real = realpath($path);
                if ($real === false || ! self::isUnderRoot($rootReal, $real)) {
                    return null;
                }
                $path = $real;
            } else {
                $real = realpath($path);
                if ($real === false || ! self::isUnderRoot($rootReal, $real)) {
                    return null;
                }

                return $real;
            }
        }

        return null;
    }

    public static function isUnderRoot(string $rootReal, string $candidateReal): bool
    {
        $root = str_replace('\\', '/', $rootReal);
        $candidate = str_replace('\\', '/', $candidateReal);
        $root = rtrim($root, '/');
        $candidate = rtrim($candidate, '/');

        return $candidate === $root || str_starts_with($candidate, $root.'/');
    }

    public static function isSafeNewName(string $name): bool
    {
        $name = trim($name);
        if ($name === '' || $name === '.' || $name === '..') {
            return false;
        }
        if (str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0")) {
            return false;
        }

        return strlen($name) <= 255;
    }
}
