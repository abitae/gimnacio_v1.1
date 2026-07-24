<?php

namespace App\Models\Concerns;

use App\Models\System\Sucursal;
use App\Services\SucursalContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSucursal
{
    public static function bootBelongsToSucursal(): void
    {
        static::addGlobalScope('active_sucursal', function (Builder $builder): void {
            $context = app(SucursalContext::class);
            $sucursalId = $context->getSucursalId();

            if ($sucursalId === null) {
                if (auth()->check()) {
                    $builder->whereRaw('1 = 0');
                }

                return;
            }

            $builder->where($builder->qualifyColumn('sucursal_id'), $sucursalId);
        });

        static::creating(function (Model $model): void {
            if (! empty($model->getAttribute('sucursal_id'))) {
                return;
            }

            $sucursalId = app(SucursalContext::class)->getFallbackSucursalId();

            if ($sucursalId !== null) {
                $model->setAttribute('sucursal_id', $sucursalId);
            }
        });
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
