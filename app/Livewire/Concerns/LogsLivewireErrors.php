<?php

namespace App\Livewire\Concerns;

use App\Services\SucursalContext;
use Illuminate\Support\Facades\Log;
use Throwable;

trait LogsLivewireErrors
{
    protected function reportLivewireError(Throwable $e, string $context, ?string $userMessage = null): void
    {
        Log::channel('operations')->error($context, [
            'component' => static::class,
            'user_id' => auth()->id(),
            'sucursal_id' => app(SucursalContext::class)->getSucursalId(),
            'message' => $e->getMessage(),
            'exception' => $e::class,
        ]);

        $this->flashToast('error', $userMessage ?? $e->getMessage());
    }
}
