<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use BelongsToSucursal;
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'nombres',
        'apellidos',
        'documento',
        'cargo',
        'area',
        'telefono',
        'fecha_ingreso',
        'estado',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'sucursal_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class, 'employee_id');
    }

    public function employeeDebts(): HasMany
    {
        return $this->hasMany(EmployeeDebt::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}
