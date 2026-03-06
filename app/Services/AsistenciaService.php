<?php

namespace App\Services;

use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\ClienteMatricula;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AsistenciaService
{
    /**
     * Buscar cliente por documento o nombre (un solo resultado)
     */
    public function buscarCliente(string $termino): ?Cliente
    {
        return Cliente::where(function ($query) use ($termino) {
            $query->where('numero_documento', 'like', "%{$termino}%")
                ->orWhere('nombres', 'like', "%{$termino}%")
                ->orWhere('apellidos', 'like', "%{$termino}%")
                ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$termino}%"]);
        })
        ->where('estado_cliente', 'activo')
        ->first();
    }

    /**
     * Buscar múltiples clientes por documento o nombre
     */
    public function buscarClientes(string $termino, int $limite = 10)
    {
        return Cliente::where(function ($query) use ($termino) {
            $query->where('numero_documento', 'like', "%{$termino}%")
                ->orWhere('nombres', 'like', "%{$termino}%")
                ->orWhere('apellidos', 'like', "%{$termino}%")
                ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$termino}%"]);
        })
        ->where('estado_cliente', 'activo')
        ->orderBy('nombres')
        ->limit($limite)
        ->get();
    }

    /**
     * Obtener membresía activa del cliente (solo tabla cliente_membresias)
     */
    public function obtenerMembresiaActiva(int $clienteId): ?ClienteMembresia
    {
        $hoy = today();
        return ClienteMembresia::where('cliente_id', $clienteId)
            ->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $hoy)
            ->where(function ($query) use ($hoy) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $hoy);
            })
            ->with(['membresia', 'cliente'])
            ->orderBy('fecha_inicio', 'desc')
            ->first();
    }

    /**
     * Obtener membresía activa del cliente (ClienteMatricula o ClienteMembresia)
     * Prioriza matrícula tipo membresía; si no hay, usa cliente_membresias.
     */
    public function obtenerMembresiaActivaParaIngreso(int $clienteId): ClienteMembresia|ClienteMatricula|null
    {
        $hoy = today();

        $matriculaActiva = ClienteMatricula::where('cliente_id', $clienteId)
            ->where('tipo', 'membresia')
            ->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $hoy)
            ->where(function ($query) use ($hoy) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $hoy);
            })
            ->with(['membresia', 'cliente'])
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if ($matriculaActiva) {
            return $matriculaActiva;
        }

        return $this->obtenerMembresiaActiva($clienteId);
    }

    /**
     * Obtener ingreso en curso del cliente (asistencia sin salida registrada)
     * Si tiene uno, el próximo registro debe ser salida.
     */
    public function obtenerIngresoEnCurso(int $clienteId): ?Asistencia
    {
        return Asistencia::where('cliente_id', $clienteId)
            ->whereNull('fecha_hora_salida')
            ->orderBy('fecha_hora_ingreso', 'desc')
            ->first();
    }

    /**
     * Validar si el cliente puede ingresar
     */
    public function validarIngreso(int $clienteId): array
    {
        $cliente = Cliente::find($clienteId);
        
        if (!$cliente) {
            return [
                'valido' => false,
                'mensaje' => 'Cliente no encontrado.',
            ];
        }

        if ($cliente->estado_cliente !== 'activo') {
            return [
                'valido' => false,
                'mensaje' => 'El cliente no está activo.',
            ];
        }

        $membresia = $this->obtenerMembresiaActivaParaIngreso($clienteId);

        if (!$membresia) {
            return [
                'valido' => false,
                'mensaje' => 'El cliente no tiene una membresía activa.',
            ];
        }

        // Verificar si ya tiene un ingreso en curso (sin salida registrada)
        $ingresoPendiente = $this->obtenerIngresoEnCurso($clienteId);

        if ($ingresoPendiente) {
            return [
                'valido' => false,
                'mensaje' => 'El cliente ya tiene un ingreso registrado hoy sin salida.',
                'asistencia' => $ingresoPendiente,
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'Cliente puede ingresar.',
            'cliente' => $cliente,
            'membresia' => $membresia,
        ];
    }

    /**
     * Registrar ingreso del cliente
     */
    public function registrarIngreso(int $clienteId, ?int $usuarioId = null): Asistencia
    {
        $validacion = $this->validarIngreso($clienteId);

        if (!$validacion['valido']) {
            throw new \Exception($validacion['mensaje']);
        }

        $membresia = $validacion['membresia'];

        $atributos = [
            'cliente_id' => $clienteId,
            'fecha_hora_ingreso' => now(),
            'fecha_hora_salida' => null,
            'origen' => 'manual',
            'valido_por_membresia' => true,
            'registrada_por' => $usuarioId ?? auth()->id(),
        ];

        if ($membresia instanceof ClienteMatricula) {
            $atributos['cliente_matricula_id'] = $membresia->id;
            $atributos['cliente_membresia_id'] = null;
        } else {
            $atributos['cliente_membresia_id'] = $membresia->id;
            $atributos['cliente_matricula_id'] = null;
        }

        return DB::transaction(function () use ($atributos) {
            $asistencia = Asistencia::create($atributos);
            if (!empty($atributos['cliente_matricula_id'])) {
                $this->marcarMatriculaCompletadaSiCorresponde((int) $atributos['cliente_matricula_id']);
            }
            return $asistencia;
        });
    }

    /**
     * Si la matrícula tiene sesiones_totales y el número de asistencias la iguala, pasar estado a completada
     */
    protected function marcarMatriculaCompletadaSiCorresponde(int $clienteMatriculaId): void
    {
        $matricula = ClienteMatricula::find($clienteMatriculaId);
        if (!$matricula || $matricula->estado === 'completada') {
            return;
        }
        $sesionesTotales = $matricula->sesiones_totales;
        if ($sesionesTotales === null || $sesionesTotales < 1) {
            return;
        }
        $count = Asistencia::where('cliente_matricula_id', $clienteMatriculaId)->count();
        if ($count >= (int) $sesionesTotales) {
            $matricula->update(['estado' => 'completada']);
        }
    }

    /**
     * Registrar salida del cliente
     */
    public function registrarSalida(int $asistenciaId): Asistencia
    {
        $asistencia = Asistencia::findOrFail($asistenciaId);

        if ($asistencia->fecha_hora_salida) {
            throw new \Exception('La asistencia ya tiene una salida registrada.');
        }

        return DB::transaction(function () use ($asistencia) {
            $asistencia->fecha_hora_salida = now();
            $asistencia->save();
            return $asistencia->fresh();
        });
    }

    /**
     * Obtener asistencias recientes del cliente
     */
    public function obtenerAsistenciasRecientes(int $clienteId, int $limite = 10)
    {
        return Asistencia::where('cliente_id', $clienteId)
            ->with(['clienteMembresia.membresia', 'clienteMatricula.membresia', 'registradaPor'])
            ->orderBy('fecha_hora_ingreso', 'desc')
            ->limit($limite)
            ->get();
    }

    /**
     * Obtener estadísticas de asistencia del cliente
     * Acepta ID de ClienteMembresia o ClienteMatricula
     */
    public function obtenerEstadisticasAsistencia(int $clienteId, ?int $matriculaId = null): array
    {
        $query = Asistencia::where('cliente_id', $clienteId);
        
        if ($matriculaId) {
            // Buscar en ambas columnas (compatibilidad)
            $query->where(function ($q) use ($matriculaId) {
                $q->where('cliente_membresia_id', $matriculaId)
                    ->orWhere('cliente_matricula_id', $matriculaId);
            });
        }

        $totalAsistencias = $query->count();
        $asistenciasCompletas = $query->whereNotNull('fecha_hora_salida')->count();
        $asistenciasPendientes = $query->whereNull('fecha_hora_salida')->count();

        // Obtener total de sesiones de la membresía si está disponible
        $totalSesiones = null;
        if ($matriculaId) {
            // Intentar obtener desde ClienteMatricula primero
            $matricula = \App\Models\Core\ClienteMatricula::with(['membresia', 'clase'])->find($matriculaId);
            if ($matricula) {
                if ($matricula->tipo === 'membresia' && $matricula->membresia) {
                    // Calcular días de membresía
                    $diasMembresia = $matricula->fecha_inicio->diffInDays($matricula->fecha_fin ?? now());
                    $totalSesiones = $diasMembresia;
                } elseif ($matricula->tipo === 'clase' && $matricula->sesiones_totales) {
                    $totalSesiones = $matricula->sesiones_totales;
                }
            } else {
                // Intentar desde ClienteMembresia (compatibilidad)
                $membresia = ClienteMembresia::with('membresia')->find($matriculaId);
                if ($membresia && $membresia->membresia) {
                    $diasMembresia = $membresia->fecha_inicio->diffInDays($membresia->fecha_fin ?? now());
                    $totalSesiones = $diasMembresia;
                }
            }
        }

        $porcentajeEfectividad = $totalSesiones > 0 
            ? round(($totalAsistencias / $totalSesiones) * 100, 2) 
            : null;

        return [
            'total_asistencias' => $totalAsistencias,
            'asistencias_completas' => $asistenciasCompletas,
            'asistencias_pendientes' => $asistenciasPendientes,
            'total_sesiones' => $totalSesiones,
            'porcentaje_efectividad' => $porcentajeEfectividad,
        ];
    }

    /**
     * Validar acceso por horario (validación básica - puede extenderse)
     * Acepta tanto ClienteMembresia como ClienteMatricula
     */
    public function validarAccesoPorHorario($membresia): array
    {
        if (!$membresia) {
            return [
                'tiene_acceso' => false,
                'mensaje' => 'No hay membresía activa.',
            ];
        }

        // Verificar que la membresía esté activa
        if ($membresia->estado !== 'activa') {
            return [
                'tiene_acceso' => false,
                'mensaje' => 'La membresía no está activa.',
            ];
        }

        // Verificar que esté vigente (fecha inicio <= hoy y fecha fin >= hoy o null)
        $hoy = now();
        if ($membresia->fecha_inicio > $hoy) {
            return [
                'tiene_acceso' => false,
                'mensaje' => 'La membresía aún no ha iniciado.',
            ];
        }

        if ($membresia->fecha_fin && $membresia->fecha_fin < $hoy) {
            return [
                'tiene_acceso' => false,
                'mensaje' => 'La membresía ha vencido.',
            ];
        }

        // Las membresías no tienen restricción por horario: si está activa y vigente, tiene acceso.
        return [
            'tiene_acceso' => true,
            'mensaje' => 'Acceso permitido.',
        ];
    }
}
