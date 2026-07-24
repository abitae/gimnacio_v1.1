<?php

namespace App\Data\Reporte;

use App\Models\System\Sucursal;
use App\Services\SucursalContext;
use Illuminate\Http\Request;

class ReporteSucursalFilter
{
    public const MODE_ACTIVE = 'active';

    public const MODE_SPECIFIC = 'specific';

    public const MODE_CONSOLIDATED = 'consolidated';

    public function __construct(
        public string $mode = self::MODE_ACTIVE,
        public ?int $specificSucursalId = null,
    ) {}

    public static function active(): self
    {
        return new self(self::MODE_ACTIVE);
    }

    public static function fromLivewire(string $mode, mixed $sucursalId): self
    {
        return self::fromArray([
            'reporte_modo_sucursal' => $mode,
            'reporte_sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $mode = (string) ($input['reporte_modo_sucursal'] ?? self::MODE_ACTIVE);

        if (! in_array($mode, [self::MODE_ACTIVE, self::MODE_SPECIFIC, self::MODE_CONSOLIDATED], true)) {
            $mode = self::MODE_ACTIVE;
        }

        $sucursalId = $input['reporte_sucursal_id'] ?? null;

        return new self(
            $mode,
            $sucursalId !== null && $sucursalId !== '' ? (int) $sucursalId : null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->query());
    }

    public function isActive(): bool
    {
        return $this->mode === self::MODE_ACTIVE;
    }

    public function isConsolidated(): bool
    {
        return $this->mode === self::MODE_CONSOLIDATED;
    }

    public function isSpecific(): bool
    {
        return $this->mode === self::MODE_SPECIFIC;
    }

    public function specificSucursalId(): ?int
    {
        return $this->isSpecific() ? $this->specificSucursalId : null;
    }

    /**
     * @return array<string, string>
     */
    public function toQueryParams(): array
    {
        $params = ['reporte_modo_sucursal' => $this->mode];

        if ($this->isSpecific() && $this->specificSucursalId !== null) {
            $params['reporte_sucursal_id'] = (string) $this->specificSucursalId;
        }

        return $params;
    }

    public function etiqueta(SucursalContext $context): string
    {
        if ($this->isConsolidated()) {
            $count = $context->availableForUser(auth()->user())->count();

            return "Consolidado ({$count} sedes)";
        }

        if ($this->isSpecific() && $this->specificSucursalId !== null) {
            $nombre = Sucursal::query()->whereKey($this->specificSucursalId)->value('nombre');

            return $nombre ?: 'Sucursal #'.$this->specificSucursalId;
        }

        return $context->getSucursalNombre() ?? 'Sucursal activa';
    }
}
