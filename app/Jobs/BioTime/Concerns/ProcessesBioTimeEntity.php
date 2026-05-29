<?php

declare(strict_types=1);

namespace App\Jobs\BioTime\Concerns;

use App\Services\BioTime\BioTimeSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class ProcessesBioTimeEntity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public string $timestamp,
        public array $records,
        public string $batchId,
    ) {}

    abstract protected function entity(): string;

    public function handle(BioTimeSyncService $syncService): void
    {
        $syncService->process(
            entity: $this->entity(),
            timestamp: $this->timestamp,
            records: $this->records,
            batchId: $this->batchId,
        );
    }
}
