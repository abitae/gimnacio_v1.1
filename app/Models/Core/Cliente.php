<?php

namespace App\Models\Core;

use App\Models\Integration\BiotimeAccessLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'telefono',
        'email',
        'direccion',
        'ocupacion',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'estado_civil',
        'numero_hijos',
        'placa_carro',
        'estado_cliente',
        'foto',
        'sexo',
        'datos_salud',
        'datos_emergencia',
        'consentimientos',
        'created_by',
        'updated_by',
        'biotime_state',
        'biotime_update',
        'trainer_user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'datos_salud' => 'array',
            'datos_emergencia' => 'array',
            'consentimientos' => 'array',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'biotime_state' => 'boolean',
            'biotime_update' => 'boolean',
        ];
    }

    /**
     * URL para abrir chat directo de WhatsApp (wa.me/51XXXXXXXXX, sin agregar contacto).
     * Perú: 51 + 9 dígitos. Devuelve null si no hay teléfono.
     */
    public function getWhatsAppUrlAttribute(): ?string
    {
        return $this->getWhatsAppUrlWithMessage();
    }

    /**
     * URL de WhatsApp con mensaje predefinido: wa.me/whatsappphonenumber/?text=urlencodedtext
     *
     * @param  string|null  $text  Mensaje predefinido (se codifica en URL).
     */
    public function getWhatsAppUrlWithMessage(?string $text = null): ?string
    {
        $tel = $this->telefono;
        if ($tel === null || trim((string) $tel) === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $tel);
        $digits = ltrim($digits, '0');
        if (str_starts_with($digits, '51') && strlen($digits) >= 11) {
            $base = 'https://wa.me/' . $digits;
        } else {
            $base = 'https://wa.me/51' . $digits;
        }
        if ($text !== null && trim($text) !== '') {
            return $base . '/?text=' . rawurlencode(trim($text));
        }
        return $base;
    }

    /**
     * Valor booleano fiable para biotime_state (desde BD: 0/1, "0"/"1", true/false).
     */
    public function getBiotimeStateBoolAttribute(): bool
    {
        $v = array_key_exists('biotime_state', $this->attributes)
            ? $this->getRawOriginal('biotime_state')
            : ($this->biotime_state ?? false);
        return $v === true || $v === 1 || $v === '1';
    }

    /**
     * Valor booleano fiable para biotime_update (desde BD: 0/1, "0"/"1", true/false).
     */
    public function getBiotimeUpdateBoolAttribute(): bool
    {
        $v = array_key_exists('biotime_update', $this->attributes)
            ? $this->getRawOriginal('biotime_update')
            : ($this->biotime_update ?? false);
        return $v === true || $v === 1 || $v === '1';
    }

    // Relaciones
    public function clienteMembresias(): HasMany
    {
        return $this->hasMany(ClienteMembresia::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function evaluacionesFisicas(): HasMany
    {
        return $this->hasMany(EvaluacionFisica::class);
    }

    public function biotimeAccessLogs(): HasMany
    {
        return $this->hasMany(BiotimeAccessLog::class);
    }

    public function evaluacionesMedidasNutricion(): HasMany
    {
        return $this->hasMany(EvaluacionMedidasNutricion::class);
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function seguimientosNutricion(): HasMany
    {
        return $this->hasMany(SeguimientoNutricion::class);
    }

    public function crmMensajes(): HasMany
    {
        return $this->hasMany(CrmMensaje::class);
    }

    public function crmLeads(): HasMany
    {
        return $this->hasMany(\App\Models\Crm\Lead::class, 'cliente_id');
    }

    public function crmTags(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Crm\Tag::class, 'cliente_tag')->withTimestamps();
    }

    public function crmActivities(): HasMany
    {
        return $this->hasMany(\App\Models\Crm\CrmActivity::class, 'cliente_id');
    }

    public function crmTasks(): HasMany
    {
        return $this->hasMany(\App\Models\Crm\CrmTask::class, 'cliente_id');
    }

    public function clientRoutines(): HasMany
    {
        return $this->hasMany(\App\Models\ClientRoutine::class, 'cliente_id');
    }

    public function trainerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_user_id');
    }

    /** Usuario que registró al cliente (created_by) */
    public function registroPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clientDebts(): HasMany
    {
        return $this->hasMany(ClientDebt::class, 'cliente_id');
    }

    public function nutritionGoals(): HasMany
    {
        return $this->hasMany(NutritionGoal::class, 'cliente_id');
    }

    public function healthRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(HealthRecord::class, 'cliente_id');
    }

    /**
     * Obtener el total de deuda del cliente
     * Suma todos los saldos pendientes de sus pagos (último saldo de cada membresía/matrícula)
     */
    public function getDeudaTotalAttribute(): float
    {
        $deudaTotal = 0;

        // Obtener IDs únicos de membresías y matrículas con deuda
        $membresiasIds = \App\Models\Core\Pago::where('cliente_id', $this->id)
            ->whereNotNull('cliente_membresia_id')
            ->where('saldo_pendiente', '>', 0)
            ->distinct()
            ->pluck('cliente_membresia_id');

        $matriculasIds = \App\Models\Core\Pago::where('cliente_id', $this->id)
            ->whereNotNull('cliente_matricula_id')
            ->where('saldo_pendiente', '>', 0)
            ->distinct()
            ->pluck('cliente_matricula_id');

        // Obtener el último saldo pendiente de cada membresía
        foreach ($membresiasIds as $membresiaId) {
            $ultimoPago = \App\Models\Core\Pago::where('cliente_id', $this->id)
                ->where('cliente_membresia_id', $membresiaId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($ultimoPago && $ultimoPago->saldo_pendiente > 0) {
                $deudaTotal += $ultimoPago->saldo_pendiente;
            }
        }

        // Obtener el último saldo pendiente de cada matrícula
        foreach ($matriculasIds as $matriculaId) {
            $ultimoPago = \App\Models\Core\Pago::where('cliente_id', $this->id)
                ->where('cliente_matricula_id', $matriculaId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($ultimoPago && $ultimoPago->saldo_pendiente > 0) {
                $deudaTotal += $ultimoPago->saldo_pendiente;
            }
        }

        // Deudas por ventas a crédito y otros orígenes (client_debts)
        $deudaTotal += (float) $this->clientDebts()->pendientes()->sum('saldo_pendiente');

        // Cuotas de matrícula pendientes o vencidas (enrollment_installments)
        $cuotasPendientes = \App\Models\Core\EnrollmentInstallment::query()
            ->whereIn('enrollment_installment_plan_id', function ($q) {
                $q->select('id')->from('enrollment_installment_plans')
                    ->whereIn('cliente_matricula_id', function ($q2) {
                        $q2->select('id')->from('cliente_matriculas')->where('cliente_id', $this->id);
                    });
            })
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->sum('monto');
        $deudaTotal += (float) $cuotasPendientes;

        return round($deudaTotal, 2);
    }
}
