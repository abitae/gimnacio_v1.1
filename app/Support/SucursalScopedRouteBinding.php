<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class SucursalScopedRouteBinding
{
    /** @var array<string, class-string<Model>> */
    protected array $bindings = [
        'cliente' => \App\Models\Core\Cliente::class,
        'lead' => \App\Models\Crm\Lead::class,
        'coupon' => \App\Models\Core\DiscountCoupon::class,
        'deal' => \App\Models\Crm\Deal::class,
    ];

    public function register(): void
    {
        foreach ($this->bindings as $parameter => $modelClass) {
            Route::bind($parameter, function (string $value) use ($modelClass) {
                /** @var Model|null $model */
                $model = $modelClass::query()->whereKey($value)->first();

                if ($model === null) {
                    abort(404);
                }

                if (auth()->check() && method_exists($model, 'getAttribute') && $model->getAttribute('sucursal_id')) {
                    app(SucursalScope::class)->assertRecordBelongsToActiveSucursal($model);
                }

                return $model;
            });
        }
    }
}
