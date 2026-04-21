<?php

namespace Modules\FileManager\Jobs;

use App\Models\Hosting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\FileManager\Services\HostFilesystemService;
use Throwable;

class ExtractArchiveJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $hostingId,
        public string $relativePath,
        public ?string $trackerToken = null
    ) {}

    public function handle(HostFilesystemService $fs): void
    {
        $hosting = Hosting::find($this->hostingId);
        if (! $hosting) {
            return;
        }

        try {
            $fs->extractArchive($hosting, $this->relativePath);
            $this->setStatus('done', 'Extract completed.');
        } catch (Throwable $e) {
            $this->setStatus('failed', $e->getMessage());
            Log::warning('file_manager_extract_failed', [
                'hosting_id' => $this->hostingId,
                'path' => $this->relativePath,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function setStatus(string $status, string $message): void
    {
        if ($this->trackerToken === null || $this->trackerToken === '') {
            return;
        }

        Cache::put('file_manager_job:'.$this->trackerToken, [
            'status' => $status,
            'message' => $message,
        ], now()->addMinutes(15));
    }
}
