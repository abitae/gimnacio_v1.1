<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Core\Asistencia;
use App\Models\Core\Caja;
use App\Models\Core\Cita;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\EvaluacionFisica;
use App\Models\Core\Pago;
use App\Models\System\AuditLog;
use App\Models\System\Sucursal;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'estado',
        'appearance',
        'appearance_sidebar',
        'appearance_header',
        'accent',
        'sidebar_bg',
        'header_bg',
        'body_bg',
        'font_size',
        'default_sucursal_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'default_sucursal_id' => 'integer',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // Relaciones
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'registrado_por');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'registrada_por');
    }

    public function evaluacionesFisicas(): HasMany
    {
        return $this->hasMany(EvaluacionFisica::class, 'evaluado_por');
    }

    public function evaluacionesMedidasNutricion(): HasMany
    {
        return $this->hasMany(\App\Models\Core\EvaluacionMedidasNutricion::class, 'evaluado_por');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function clienteMembresiasAsesor(): HasMany
    {
        return $this->hasMany(ClienteMembresia::class, 'asesor_id');
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'usuario_id');
    }

    public function clientesAsignados(): HasMany
    {
        return $this->hasMany(Cliente::class, 'trainer_user_id');
    }

    public function citasComoTrainer(): HasMany
    {
        return $this->hasMany(Cita::class, 'trainer_user_id');
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'sucursal_user')->withTimestamps();
    }

    public function defaultSucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'default_sucursal_id');
    }

    /**
     * Roles que pueden figurar como asesor de venta en clientes y matrículas.
     *
     * @var list<string>
     */
    public const ASESOR_ROLE_NAMES = [
        'vendedor',
        'caja',
        PermissionCatalog::BRANCH_ADMIN_ROLE_NAME,
        PermissionCatalog::SUPER_ADMIN_ROLE_NAME,
    ];

    /**
     * Usuarios activos con rol de asesor/vendedor (listados en filtros de clientes).
     */
    public function scopeAsesoresActivos($query)
    {
        return $query
            ->where('estado', 'activo')
            ->role(self::ASESOR_ROLE_NAMES)
            ->orderBy('name');
    }

    /**
     * Entrenadores (rol trainer) opcionalmente filtrados por sede.
     */
    public function scopeTrainersForSucursal($query, ?int $sucursalId = null)
    {
        $query->role('trainer');

        if ($sucursalId) {
            $query->where(function ($inner) use ($sucursalId): void {
                $inner->whereHas('sucursales', fn ($s) => $s->whereKey($sucursalId))
                    ->orWhere('default_sucursal_id', $sucursalId);
            });
        }

        return $query->orderBy('name');
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            $user->appearance = 'light';
            $user->appearance_sidebar = 'light';
            $user->appearance_header = 'light';
        });
    }
}
