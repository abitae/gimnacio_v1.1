<?php

declare(strict_types=1);

namespace Database\Factories\BioTime;

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\Core\Cliente;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BioTimeAccessCommand>
 */
class BioTimeAccessCommandFactory extends Factory
{
    protected $model = BioTimeAccessCommand::class;

    public function definition(): array
    {
        return [
            'sucursal_id' => fn () => $this->makeSucursal()->id,
            'cliente_id' => fn (array $attributes) => Cliente::factory()->create([
                'sucursal_id' => $attributes['sucursal_id'],
                'created_by' => User::factory(),
                'codigo' => 'C'.fake()->unique()->numerify('######'),
            ])->id,
            'emp_code' => fn (array $attributes) => (string) Cliente::query()->findOrFail($attributes['cliente_id'])->numero_documento,
            'action' => BioTimeAccessCommand::ACTION_ACTIVATE,
            'desired_area_biotime_id' => 2,
            'status' => BioTimeAccessCommand::STATUS_PENDING,
            'attempts' => 0,
            'last_error' => null,
            'acked_at' => null,
        ];
    }

    public function deactivate(): static
    {
        return $this->state(fn () => [
            'action' => BioTimeAccessCommand::ACTION_DEACTIVATE,
            'desired_area_biotime_id' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => BioTimeAccessCommand::STATUS_PENDING,
            'acked_at' => null,
        ]);
    }

    public function acked(): static
    {
        return $this->state(fn () => [
            'status' => BioTimeAccessCommand::STATUS_ACKED,
            'acked_at' => now(),
        ]);
    }

    private function makeSucursal(): Sucursal
    {
        $empresa = Empresa::query()->create([
            'nombre' => 'Empresa Cmd '.fake()->unique()->numerify('###'),
            'estado' => 'activa',
        ]);

        return Sucursal::query()->create([
            'empresa_id' => $empresa->id,
            'codigo' => 'cmd-'.fake()->unique()->bothify('??##'),
            'nombre' => 'Sucursal Cmd',
            'estado' => 'activa',
            'es_principal' => true,
        ]);
    }
}
