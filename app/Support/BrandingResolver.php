<?php

namespace App\Support;

use App\Models\System\GymSetting;
use App\Models\System\Sucursal;
use App\Services\SucursalContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BrandingResolver
{
    public const DEFAULT_NAME = 'Firnetness';

    public function resolve(): array
    {
        $brandName = self::DEFAULT_NAME;
        $logoPath = null;

        if (Schema::hasTable('gym_settings')) {
            $settings = GymSetting::query()->first();
            $brandName = trim((string) ($settings?->nombre_gimnasio ?: $brandName));
            $logoPath = $settings?->logo ?: null;
        }

        $sucursal = app(SucursalContext::class)->sucursal();
        if ($sucursal instanceof Sucursal && filled($sucursal->logo)) {
            $logoPath = $sucursal->logo;
        }

        $logoUrl = $this->logoUrl($logoPath);

        return [
            'name' => $brandName !== '' ? $brandName : self::DEFAULT_NAME,
            'logo_path' => $logoPath,
            'logo_url' => $logoUrl,
            'has_logo' => $logoUrl !== null,
        ];
    }

    private function logoUrl(?string $logoPath): ?string
    {
        if (! $logoPath) {
            return null;
        }

        if (filter_var($logoPath, FILTER_VALIDATE_URL)) {
            return $logoPath;
        }

        if (Storage::disk('public')->exists($logoPath)) {
            return Storage::disk('public')->url($logoPath);
        }

        return asset($logoPath);
    }
}
