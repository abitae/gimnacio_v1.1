<?php

namespace App\Services\Imports;

use App\Models\Core\Cliente;
use App\Models\Core\Membresia;
use App\Models\User;
use Illuminate\Support\Collection;

class ImportRelationResolverService
{
    /** @var Collection<int, User>|null Cache por petición: evita N× User::get() en importaciones largas. */
    private static ?Collection $usersForNameMatchCache = null;

    /**
     * Prioridad: codigo + sucursal, luego tipo_documento + numero_documento + sucursal.
     */
    public function resolverClientePorCodigoODocumento(
        ?string $codigo,
        ?string $numeroDocumento,
        int $sucursalId,
        string $tipoDocumento = 'DNI'
    ): ?Cliente {
        $codigo = $codigo !== null ? trim((string) $codigo) : '';
        if ($codigo !== '') {
            $byCodigo = Cliente::query()
                ->where('sucursal_id', $sucursalId)
                ->where('codigo', $codigo)
                ->first();
            if ($byCodigo) {
                return $byCodigo;
            }
        }

        $numeroDocumento = $numeroDocumento !== null ? trim((string) $numeroDocumento) : '';
        if ($numeroDocumento === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $numeroDocumento) ?? '';
        if ($digits === '') {
            return null;
        }

        return Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->where('tipo_documento', $tipoDocumento)
            ->where('numero_documento', $digits)
            ->first();
    }

    public function resolverMembresiaPorNombre(string $nombre, ?int $sucursalId = null): ?Membresia
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return null;
        }

        $normalized = mb_strtolower($nombre);

        $base = Membresia::query()
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [$normalized]);

        if ($sucursalId !== null) {
            $bySucursal = (clone $base)->where('sucursal_id', $sucursalId)->first();
            if ($bySucursal) {
                return $bySucursal;
            }

            $global = (clone $base)->whereNull('sucursal_id')->first();
            if ($global) {
                return $global;
            }
        }

        return $base->first();
    }

    /**
     * Crea una membresía de catálogo para la sucursal cuando el Excel legacy trae un PAQUETE que aún no existe.
     */
    public function crearMembresiaDesdeImportLegacy(string $nombre, int $sucursalId, int $duracionDias, float $precioBase): Membresia
    {
        $nombre = trim($nombre);
        $nombre = mb_substr($nombre, 0, 100);

        return Membresia::query()->create([
            'nombre' => $nombre,
            'descripcion' => 'Creada automáticamente por importación Excel legacy (paquete no existente en catálogo).',
            'duracion_dias' => max(1, $duracionDias),
            'precio_base' => max(0, $precioBase),
            'estado' => 'activa',
            'sucursal_id' => $sucursalId,
            'permite_cuotas' => false,
            'permite_congelacion' => false,
            'tipo_acceso' => null,
        ]);
    }

    public function resolverUsuarioPorNombreVendedor(?string $nombre): ?User
    {
        if ($nombre === null || trim($nombre) === '') {
            return null;
        }

        $normalized = \App\DataTransferObjects\Imports\SocioActivoRowData::normalizeComparable($nombre);
        if ($normalized === '') {
            return null;
        }

        return $this->usersForNameMatch()
            ->first(fn (User $user) => \App\DataTransferObjects\Imports\SocioActivoRowData::normalizeComparable($user->name) === $normalized);
    }

    /**
     * @return Collection<int, User>
     */
    private function usersForNameMatch(): Collection
    {
        return self::$usersForNameMatchCache ??= User::query()
            ->select(['id', 'name'])
            ->get();
    }
}
