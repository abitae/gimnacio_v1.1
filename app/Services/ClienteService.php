<?php

namespace App\Services;

use App\Models\Core\Cliente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClienteService
{
    /** Primer código numérico autoasignado por sucursal (siguientes: 10001, 10002, …). */
    private const CODIGO_CLIENTE_INICIAL = 10000;

    public function __construct(
        protected SucursalContext $sucursalContext,
        protected ClientEnrollmentService $clientEnrollmentService,
    ) {}

    /**
     * Obtener todos los clientes con paginación
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Cliente::query()
            ->orderByRaw("CASE estado_cliente WHEN 'activo' THEN 0 WHEN 'inactivo' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Buscar clientes por término de búsqueda
     *
     * @param  string|null  $codigo  Filtro adicional AND por columna codigo (coincidencia exacta).
     */
    public function search(string $search, ?string $estado = null, int $perPage = 15, ?string $codigo = null): LengthAwarePaginator
    {
        $query = Cliente::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $codigoTrim = $codigo !== null ? trim($codigo) : '';
        if ($codigoTrim !== '') {
            $query->where('codigo', $codigoTrim);
        }

        if ($estado) {
            $query->where('estado_cliente', $estado);
        }

        return $query
            ->orderByRaw("CASE estado_cliente WHEN 'activo' THEN 0 WHEN 'inactivo' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Búsqueda rápida para autocompletado (optimizada)
     * Busca por: documento, código interno, nombre completo, email
     */
    public function quickSearch(string $search, int $limit = 10, ?int $responsableId = null): Collection
    {
        $searchTerm = trim($search);

        if (strlen($searchTerm) < 2) {
            return collect([]);
        }

        return Cliente::query()
            ->select(['id', 'codigo', 'tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'telefono', 'email', 'estado_cliente'])
            ->when($responsableId, function ($query) use ($responsableId) {
                $query->where(function ($q) use ($responsableId) {
                    $q->where('created_by', $responsableId)
                        ->orWhereHas('clienteMatriculas', fn ($matriculas) => $matriculas->where('asesor_id', $responsableId));
                });
            })
            ->where(function ($q) use ($searchTerm) {
                // Prioridad 1: Documento o código que empiecen con el término
                $q->where('numero_documento', 'like', "{$searchTerm}%")
                    ->orWhere('codigo', 'like', "{$searchTerm}%")
                    ->orWhere('telefono', 'like', "{$searchTerm}%")
                    // Prioridad 2: Nombres o apellidos que empiecen con el término
                    ->orWhere(function ($subQ) use ($searchTerm) {
                        $subQ->where('nombres', 'like', "{$searchTerm}%")
                            ->orWhere('apellidos', 'like', "{$searchTerm}%")
                            ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["{$searchTerm}%"]);
                    })
                    // Prioridad 3: Coincidencias parciales en nombres, apellidos, código o email
                    ->orWhere(function ($subQ) use ($searchTerm) {
                        $subQ->where('nombres', 'like', "%{$searchTerm}%")
                            ->orWhere('apellidos', 'like', "%{$searchTerm}%")
                            ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$searchTerm}%"])
                            ->orWhere('codigo', 'like', "%{$searchTerm}%")
                            ->orWhere('telefono', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%");
                    });
            })
            ->orderByRaw('
                CASE
                    WHEN numero_documento = ? THEN 1
                    WHEN codigo = ? THEN 1
                    WHEN telefono = ? THEN 1
                    WHEN numero_documento LIKE ? THEN 2
                    WHEN codigo LIKE ? THEN 2
                    WHEN telefono LIKE ? THEN 2
                    WHEN nombres LIKE ? OR apellidos LIKE ? OR CONCAT(nombres, \' \', apellidos) LIKE ? THEN 3
                    ELSE 4
                END
            ', [
                $searchTerm,
                $searchTerm,
                $searchTerm,
                "{$searchTerm}%",
                "{$searchTerm}%",
                "{$searchTerm}%",
                "{$searchTerm}%",
                "{$searchTerm}%",
                "{$searchTerm}%",
            ])
            ->limit($limit)
            ->get();
    }

    /**
     * Autocompletado solo por código interno (coincidencia exacta con el texto buscado).
     */
    public function quickSearchByCodigo(string $search, int $limit = 10, ?int $responsableId = null): Collection
    {
        $searchTerm = trim($search);

        if ($searchTerm === '') {
            return collect([]);
        }

        return Cliente::query()
            ->select(['id', 'codigo', 'tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'telefono', 'email', 'estado_cliente'])
            ->when($responsableId, function ($query) use ($responsableId) {
                $query->where(function ($q) use ($responsableId) {
                    $q->where('created_by', $responsableId)
                        ->orWhereHas('clienteMatriculas', fn ($matriculas) => $matriculas->where('asesor_id', $responsableId));
                });
            })
            ->where('codigo', $searchTerm)
            ->orderBy('codigo')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener un cliente por ID
     */
    public function find(int $id): ?Cliente
    {
        return Cliente::with('healthRecord')->find($id);
    }

    /**
     * Crear un nuevo cliente
     */
    public function create(array $data): Cliente
    {
        $validated = $this->validate($data);
        $validated['created_by'] = $validated['created_by'] ?? auth()->id();
        $validated['sucursal_id'] = $validated['sucursal_id'] ?? $this->sucursalContext->getFallbackSucursalId();

        return DB::transaction(function () use ($validated) {
            $sucursalId = (int) $validated['sucursal_id'];
            if ($sucursalId <= 0) {
                throw new \InvalidArgumentException('sucursal_id es obligatorio para crear un cliente.');
            }

            // Código único por sucursal, solo asignado por el sistema (no se acepta valor manual).
            $validated['codigo'] = $this->normalizeCodigo($validated['codigo'] ?? null)
                ?? $this->nextCodigoNumericoParaSucursal($sucursalId);

            return Cliente::create($validated);
        });
    }

    /**
     * Siguiente código numérico único en la sucursal (solo considera códigos compuestos solo por dígitos).
     */
    public function nextCodigoNumericoParaSucursal(int $sucursalId): string
    {
        $driver = Schema::getConnection()->getDriverName();

        $query = Cliente::query()->where('sucursal_id', $sucursalId);

        if ($driver === 'mysql') {
            $query->whereRaw("codigo REGEXP '^[0-9]+$'");
            $orderExpr = 'CAST(codigo AS UNSIGNED)';
        } else {
            $query->whereRaw("codigo GLOB '[0-9]*'")->where('codigo', '!=', '');
            $orderExpr = 'CAST(codigo AS INTEGER)';
        }

        $lastCodigo = $query->orderByRaw($orderExpr.' DESC')->lockForUpdate()->value('codigo');

        $ultimo = $lastCodigo !== null && $lastCodigo !== ''
            ? (int) $lastCodigo
            : (self::CODIGO_CLIENTE_INICIAL - 1);

        $siguiente = max(self::CODIGO_CLIENTE_INICIAL, $ultimo + 1);

        return (string) $siguiente;
    }

    /**
     * Actualizar un cliente
     */
    public function update(int $id, array $data): Cliente
    {
        $cliente = $this->find($id);

        if (! $cliente) {
            throw new \Exception('Cliente no encontrado');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($cliente, $validated) {
            if (array_key_exists('codigo', $validated)) {
                $validated['codigo'] = $this->normalizeCodigo($validated['codigo']);
            }
            $validated['updated_by'] = auth()->id();
            $validated['sucursal_id'] = $cliente->sucursal_id;
            $cliente->update($validated);

            return $cliente->fresh();
        });
    }

    /**
     * Eliminar un cliente
     */
    public function delete(int $id): bool
    {
        $cliente = $this->find($id);

        if (! $cliente) {
            throw new \Exception('Cliente no encontrado');
        }

        // Verificar si tiene relaciones
        $this->checkRelations($cliente);

        return DB::transaction(function () use ($cliente) {
            return $cliente->delete();
        });
    }

    /**
     * Validar datos del cliente
     */
    protected function validate(array $data, ?int $id = null): array
    {
        // En actualizaciones, solo validar campos que están presentes
        $isUpdate = $id !== null;

        $rules = [
            'codigo' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($data, $id) {
                    $codigo = $this->normalizeCodigo($value);
                    if ($codigo === null) {
                        return;
                    }

                    $sucursalId = $this->resolveSucursalIdForCodigoValidation($data, $id);
                    if ($sucursalId <= 0) {
                        return;
                    }

                    $exists = Cliente::query()
                        ->where('sucursal_id', $sucursalId)
                        ->where('codigo', $codigo)
                        ->when($id, fn ($q) => $q->where('id', '!=', $id))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un cliente con este codigo en la sucursal.');
                    }
                },
            ],
            'tipo_documento' => [$isUpdate ? 'sometimes' : 'required', 'in:DNI,CE'],
            'numero_documento' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($data) {
                    $tipoDocumento = strtoupper((string) ($data['tipo_documento'] ?? ''));
                    $numeroDocumento = preg_replace('/\s+/', '', (string) $value);

                    if ($tipoDocumento === 'DNI' && ! preg_match('/^\d{8}$/', $numeroDocumento)) {
                        $fail('El DNI debe tener exactamente 8 dígitos.');
                    }

                    if ($tipoDocumento === 'CE' && ! preg_match('/^[A-Za-z0-9]{9,20}$/', $numeroDocumento)) {
                        $fail('El CE debe tener entre 9 y 20 caracteres alfanuméricos.');
                    }
                },
                function ($attribute, $value, $fail) use ($data, $id) {
                    if (! isset($data['tipo_documento']) || ! isset($value)) {
                        return;
                    }
                    $exists = Cliente::where('tipo_documento', $data['tipo_documento'])
                        ->where('numero_documento', $value)
                        ->where('sucursal_id', $data['sucursal_id'] ?? $this->sucursalContext->getFallbackSucursalId())
                        ->when($id, fn ($q) => $q->where('id', '!=', $id))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un cliente con este tipo y número de documento.');
                    }
                },
            ],
            'nombres' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'apellidos' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('clientes', 'email')->ignore($id)],
            'direccion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'ocupacion' => ['nullable', 'string', 'max:80'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'lugar_nacimiento' => ['nullable', 'string', 'max:120'],
            'estado_civil' => ['nullable', 'string', 'in:soltero,casado,conviviente,divorciado,viudo'],
            'numero_hijos' => ['nullable', 'integer', 'min:0', 'max:20'],
            'placa_carro' => ['nullable', 'string', 'max:20'],
            'sexo' => ['nullable', 'string', 'in:masculino,femenino'],
            'estado_cliente' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:activo,inactivo,suspendido'],
            'foto' => ['nullable', 'string'],
            'datos_salud' => ['nullable', 'array'],
            'datos_emergencia' => ['nullable', 'array'],
            'datos_emergencia.nombre_contacto' => ['nullable', 'string', 'max:100'],
            'datos_emergencia.telefono_contacto' => ['nullable', 'string', 'max:20'],
            'datos_emergencia.relacion' => ['nullable', 'string', 'max:60'],
            'consentimientos' => ['nullable', 'array'],
            'consentimientos.uso_imagen' => ['nullable', 'boolean'],
            'consentimientos.tratamiento_datos' => ['nullable', 'boolean'],
            'consentimientos.fecha_consentimiento' => ['nullable', 'date'],
            'created_by' => ['nullable', 'exists:users,id'],
            'updated_by' => ['nullable', 'exists:users,id'],
            'trainer_user_id' => ['nullable', 'exists:users,id'],
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    private function normalizeCodigo(mixed $codigo): ?string
    {
        if ($codigo === null) {
            return null;
        }

        $codigo = trim((string) $codigo);

        return $codigo !== '' ? $codigo : null;
    }

    private function resolveSucursalIdForCodigoValidation(array $data, ?int $id): int
    {
        if (! empty($data['sucursal_id'])) {
            return (int) $data['sucursal_id'];
        }

        if ($id !== null) {
            return (int) (Cliente::query()->whereKey($id)->value('sucursal_id') ?? 0);
        }

        return (int) ($this->sucursalContext->getFallbackSucursalId() ?? 0);
    }

    /**
     * Si el cliente tiene una membresía vigente, asegura que su estado sea activo.
     */
    public function syncEstadoDesdeMembresiaActiva(int $clienteId): void
    {
        if ($this->clientEnrollmentService->resolveActiveEnrollmentModel($clienteId) === null) {
            return;
        }

        Cliente::query()
            ->whereKey($clienteId)
            ->where('estado_cliente', '!=', 'activo')
            ->update(['estado_cliente' => 'activo']);
    }

    /**
     * Verificar relaciones antes de eliminar
     */
    protected function checkRelations(Cliente $cliente): void
    {
        $hasMembresias = $cliente->clienteMembresias()->exists();
        $hasMatriculas = $cliente->clienteMatriculas()->exists();
        $hasPagos = $cliente->pagos()->exists();
        $hasAsistencias = $cliente->asistencias()->exists();
        $hasHealthRecord = $cliente->healthRecord()->exists();
        $hasCitas = $cliente->citas()->exists();
        $hasSeguimientos = $cliente->seguimientosNutricion()->exists();
        $hasEvaluacionesFisicas = Schema::hasTable('evaluacion_fisicas')
            && $cliente->evaluacionesFisicas()->exists();
        $hasEvaluacionesNutricion = $cliente->evaluacionesMedidasNutricion()->exists();
        $hasRutinas = $cliente->clientRoutines()->exists();
        $hasMetas = $cliente->nutritionGoals()->exists();
        $hasEtiquetasCrm = $cliente->crmTags()->exists();
        $hasTareasCrm = $cliente->crmTasks()->exists();
        $hasActividadesCrm = $cliente->crmActivities()->exists();
        $hasLeadsCrm = $cliente->crmLeads()->exists();
        $hasAlquileres = $cliente->rentals()->exists();

        if (
            $hasMembresias
            || $hasMatriculas
            || $hasPagos
            || $hasAsistencias
            || $hasHealthRecord
            || $hasCitas
            || $hasSeguimientos
            || $hasEvaluacionesFisicas
            || $hasEvaluacionesNutricion
            || $hasRutinas
            || $hasMetas
            || $hasEtiquetasCrm
            || $hasTareasCrm
            || $hasActividadesCrm
            || $hasLeadsCrm
            || $hasAlquileres
        ) {
            throw new \Exception('No se puede eliminar el cliente porque tiene historial u operaciones asociadas. Cambia su estado en lugar de eliminarlo.');
        }
    }
}
