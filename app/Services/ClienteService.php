<?php

namespace App\Services;

use App\Models\Core\Cliente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClienteService
{
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
     */
    public function search(string $search, ?string $estado = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Cliente::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
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
     * Busca por: código (numero_documento), nombre completo, email
     */
    public function quickSearch(string $search, int $limit = 10): Collection
    {
        $searchTerm = trim($search);
        
        if (strlen($searchTerm) < 2) {
            return collect([]);
        }
        
        return Cliente::query()
            ->select(['id', 'tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'email', 'estado_cliente'])
            ->where(function ($q) use ($searchTerm) {
                // Prioridad 1: Coincidencia exacta o que empiece con el documento
                $q->where('numero_documento', 'like', "{$searchTerm}%")
                    // Prioridad 2: Nombres o apellidos que empiecen con el término
                    ->orWhere(function ($subQ) use ($searchTerm) {
                        $subQ->where('nombres', 'like', "{$searchTerm}%")
                            ->orWhere('apellidos', 'like', "{$searchTerm}%")
                            ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["{$searchTerm}%"]);
                    })
                    // Prioridad 3: Coincidencias parciales en nombres, apellidos o email
                    ->orWhere(function ($subQ) use ($searchTerm) {
                        $subQ->where('nombres', 'like', "%{$searchTerm}%")
                            ->orWhere('apellidos', 'like', "%{$searchTerm}%")
                            ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$searchTerm}%"])
                            ->orWhere('email', 'like', "%{$searchTerm}%");
                    });
            })
            ->orderByRaw("
                CASE 
                    WHEN numero_documento = ? THEN 1
                    WHEN numero_documento LIKE ? THEN 2
                    WHEN nombres LIKE ? OR apellidos LIKE ? OR CONCAT(nombres, ' ', apellidos) LIKE ? THEN 3
                    ELSE 4
                END
            ", [$searchTerm, "{$searchTerm}%", "{$searchTerm}%", "{$searchTerm}%", "{$searchTerm}%"])
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

        return DB::transaction(function () use ($validated) {
            return Cliente::create($validated);
        });
    }

    /**
     * Actualizar un cliente
     */
    public function update(int $id, array $data): Cliente
    {
        $cliente = $this->find($id);

        if (!$cliente) {
            throw new \Exception('Cliente no encontrado');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($cliente, $validated) {
            $validated['biotime_update'] = true;
            $validated['updated_by'] = auth()->id();
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

        if (!$cliente) {
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
            'tipo_documento' => [$isUpdate ? 'sometimes' : 'required', 'in:DNI,CE'],
            'numero_documento' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($data, $id) {
                    if (!isset($data['tipo_documento']) || !isset($value)) {
                        return;
                    }
                    $exists = Cliente::where('tipo_documento', $data['tipo_documento'])
                        ->where('numero_documento', $value)
                        ->when($id, fn($q) => $q->where('id', '!=', $id))
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
            'ocupacion' => ['nullable', 'string', 'max:80'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'lugar_nacimiento' => ['nullable', 'string', 'max:120'],
            'estado_civil' => ['nullable', 'string', 'in:soltero,casado,conviviente,divorciado,viudo'],
            'numero_hijos' => ['nullable', 'integer', 'min:0', 'max:20'],
            'placa_carro' => ['nullable', 'string', 'max:20'],
            'sexo' => ['nullable', 'string', 'in:masculino,femenino'],
            'estado_cliente' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:activo,inactivo,suspendido'],
            'biotime_state' => ['nullable', 'boolean'],
            'biotime_update' => ['nullable', 'boolean'],
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
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
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
        $hasEvaluacionesFisicas = $cliente->evaluacionesFisicas()->exists();
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

