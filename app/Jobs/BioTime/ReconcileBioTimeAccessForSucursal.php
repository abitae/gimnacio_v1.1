<?php

declare(strict_types=1);

namespace App\Jobs\BioTime;

use App\Services\BioTime\BioTimeAccessCommandService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReconcileBioTimeAccessForSucursal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $sucursalId,
    ) {}

    public function handle(BioTimeAccessCommandService $commands): void
    {
        $commands->reconcileSucursal($this->sucursalId);
    }
}
