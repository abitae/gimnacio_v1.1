<?php

namespace App\Services;

use App\Models\Core\Caja;
use App\Models\Core\Cliente;
use App\Models\Core\RentableSpace;
use App\Models\Core\Rental;
use App\Models\Core\RentalPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RentalService
{
    public function __construct(
        protected SucursalContext $sucursalContext
    ) {}

    public function createBooking(array $data): Rental
    {
        return $this->create($data);
    }

    public function updateBooking(Rental $rental, array $data): Rental
    {
        return $this->update($rental->id, array_merge($data, [
            'cliente_id' => $data['cliente_id'] ?? $rental->cliente_id,
        ]));
    }

    public function cancelBooking(Rental $rental, ?string $reason = null): Rental
    {
        return $this->update($rental->id, [
            'estado' => 'cancelado',
            'observaciones' => trim(($rental->observaciones ?? '').($reason ? "\nCancelado: {$reason}" : '')),
        ]);
    }

    public function listForCliente(int $clienteId): \Illuminate\Support\Collection
    {
        return Rental::query()
            ->with('rentableSpace')
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->get();
    }

    public function listForDate(\Carbon\Carbon $date, ?int $sucursalId = null): \Illuminate\Support\Collection
    {
        return Rental::query()
            ->with(['rentableSpace', 'cliente'])
            ->whereDate('fecha', $date->toDateString())
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->whereNotIn('estado', ['cancelado'])
            ->orderBy('hora_inicio')
            ->get();
    }

    public function find(int $id): ?Rental
    {
        return Rental::query()->with(['rentableSpace', 'cliente'])->find($id);
    }

    public function create(array $data): Rental
    {
        $validated = $this->validateRental($data);

        return DB::transaction(function () use ($validated) {
            $space = $this->resolveSpace((int) $validated['rentable_space_id']);
            $this->assertClienteSucursal($validated['cliente_id'] ?? null, (int) $space->sucursal_id);
            $this->assertNoOverlap($validated, null);

            return Rental::query()->create(array_merge($validated, [
                'registrado_por' => $validated['registrado_por'] ?? Auth::id(),
                'sucursal_id' => $space->sucursal_id,
            ]));
        });
    }

    public function update(int $id, array $data): Rental
    {
        $rental = $this->find($id);

        if (! $rental) {
            throw new \InvalidArgumentException('Reserva no encontrada.');
        }

        $validated = $this->validateRental($data, $id);

        return DB::transaction(function () use ($rental, $validated) {
            $space = $this->resolveSpace((int) ($validated['rentable_space_id'] ?? $rental->rentable_space_id));
            $this->assertClienteSucursal($validated['cliente_id'] ?? $rental->cliente_id, (int) $space->sucursal_id);
            $payload = array_merge($rental->only([
                'rentable_space_id', 'cliente_id', 'nombre_externo', 'documento_externo', 'fecha',
                'hora_inicio', 'hora_fin', 'precio', 'estado', 'observaciones',
            ]), $validated);
            $this->assertNoOverlap($payload, $rental->id);

            $rental->update(array_merge($validated, [
                'sucursal_id' => $rental->sucursal_id,
                'registrado_por' => $rental->registrado_por ?? Auth::id(),
            ]));

            return $rental->fresh(['rentableSpace', 'cliente']);
        });
    }

    public function validateRentalPaymentSucursal(RentalPayment $payment): void
    {
        $rental = $payment->rental()->with('rentableSpace')->first();
        if (! $rental || ! $rental->sucursal_id) {
            throw new \InvalidArgumentException('El pago de alquiler no tiene una sucursal valida.');
        }

        if ($payment->caja_id) {
            $caja = Caja::query()->findOrFail($payment->caja_id);
            if ((int) $caja->sucursal_id !== (int) $rental->sucursal_id) {
                throw new \InvalidArgumentException('La caja del pago de alquiler no pertenece a la misma sucursal.');
            }
        }
    }

    protected function validateRental(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;

        $validator = Validator::make($data, [
            'rentable_space_id' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'cliente_id' => ['nullable', 'integer'],
            'nombre_externo' => ['nullable', 'string', 'max:120'],
            'documento_externo' => ['nullable', 'string', 'max:30'],
            'fecha' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'hora_inicio' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'hora_fin' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'precio' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'estado' => [$isUpdate ? 'sometimes' : 'required', 'in:reservado,confirmado,pagado,cancelado,finalizado'],
            'observaciones' => ['nullable', 'string'],
            'registrado_por' => ['nullable', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function assertNoOverlap(array $data, ?int $ignoreRentalId): void
    {
        $horaInicio = Carbon::parse($data['fecha'].' '.$data['hora_inicio']);
        $horaFin = Carbon::parse($data['fecha'].' '.$data['hora_fin']);

        if ($horaFin <= $horaInicio) {
            throw new \InvalidArgumentException('La hora fin debe ser posterior a la hora de inicio.');
        }

        $exists = Rental::query()
            ->where('rentable_space_id', (int) $data['rentable_space_id'])
            ->whereDate('fecha', $data['fecha'])
            ->whereNotIn('estado', ['cancelado'])
            ->when($ignoreRentalId, fn ($q) => $q->where('id', '!=', $ignoreRentalId))
            ->where('hora_inicio', '<', $horaFin->format('H:i:s'))
            ->where('hora_fin', '>', $horaInicio->format('H:i:s'))
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('El horario se solapa con otra reserva.');
        }
    }

    protected function resolveSpace(int $spaceId): RentableSpace
    {
        $space = RentableSpace::query()->find($spaceId);
        if (! $space) {
            throw new \InvalidArgumentException('El espacio seleccionado no pertenece a la sucursal activa.');
        }

        return $space;
    }

    protected function assertClienteSucursal(?int $clienteId, int $sucursalId): void
    {
        if (! $clienteId) {
            return;
        }

        $cliente = Cliente::query()->find($clienteId);
        if (! $cliente || (int) $cliente->sucursal_id !== $sucursalId) {
            throw new \InvalidArgumentException('El cliente seleccionado no pertenece a la misma sucursal del espacio.');
        }
    }
}
