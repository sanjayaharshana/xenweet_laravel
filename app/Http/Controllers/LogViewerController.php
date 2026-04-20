<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class LogViewerController extends Controller
{
    private const MAX_BYTES = 524_288;

    public function __invoke(Request $request): View
    {
        $logsDir = storage_path('logs');
        File::ensureDirectoryExists($logsDir);

        $files = collect(File::files($logsDir))
            ->map(fn (\SplFileInfo $f) => $f->getFilename())
            ->filter(fn (string $n) => str_ends_with($n, '.log'))
            ->sort()
            ->values()
            ->all();

        if ($files === []) {
            return view('panel.logs', [
                'logFiles' => [],
                'currentFile' => null,
                'content' => 'No .log files in storage/logs yet.',
                'truncated' => false,
                'maxBytes' => self::MAX_BYTES,
            ]);
        }

        $requested = (string) $request->query('file', 'laravel.log');
        if (! preg_match('/^[a-zA-Z0-9._-]+\.log$/', $requested)) {
            abort(400, 'Invalid log file name.');
        }

        if (! in_array($requested, $files, true)) {
            $requested = $files[0];
        }

        $path = $logsDir.DIRECTORY_SEPARATOR.$requested;

        $content = $this->readLogTail($path);
        $truncated = is_readable($path) && (filesize($path) ?: 0) > self::MAX_BYTES;

        return view('panel.logs', [
            'logFiles' => $files,
            'currentFile' => $requested,
            'content' => $content,
            'truncated' => $truncated,
            'maxBytes' => self::MAX_BYTES,
        ]);
    }

    private function readLogTail(string $path): string
    {
        if (! is_readable($path)) {
            return 'This log file is not readable by the web server. Check file permissions.';
        }

        $size = filesize($path);
        if ($size === false) {
            return 'Could not read log file size.';
        }

        if ($size <= self::MAX_BYTES) {
            return (string) File::get($path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return 'Could not open log file.';
        }

        try {
            fseek($handle, -self::MAX_BYTES, SEEK_END);
            $chunk = fread($handle, self::MAX_BYTES);
        } finally {
            fclose($handle);
        }

        return "[Showing last ".number_format(self::MAX_BYTES)." bytes of ".number_format($size)." total]\n\n".($chunk ?: '');
    }
}
