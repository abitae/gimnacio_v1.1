<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_venta',
        'cliente_id',
        'employee_id',
        'cliente_venta_nombre',
        'cliente_venta_documento',
        'cliente_venta_telefono',
        'caja_id',
        'usuario_id',
        'tipo_comprobante',
        'numero_comprobante',
        'serie_comprobante',
        'subtotal',
        'descuento',
        'igv',
        'total',
        'metodo_pago',
        'payment_method_id',
        'numero_operacion',
        'entidad_financiera',
        'discount_coupon_id',
        'monto_descuento_cupon',
        'es_credito',
        'monto_inicial',
        'fecha_vencimiento_deuda',
        'estado',
        'fecha_venta',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'monto_descuento_cupon' => 'decimal:2',
            'fecha_venta' => 'datetime',
            'es_credito' => 'boolean',
            'monto_inicial' => 'decimal:2',
            'fecha_vencimiento_deuda' => 'date',
        ];
    }

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VentaItem::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function clientDebt(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClientDebt::class);
    }

    public function employeeDebt(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmployeeDebt::class);
    }

    public function discountCoupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class);
    }

    /**
     * Nombre del comprador para el comprobante (cliente, empleado o cliente solo venta).
     */
    public function getNombreCompradorAttribute(): string
    {
        if ($this->cliente_id && $this->cliente) {
            return trim($this->cliente->nombres . ' ' . $this->cliente->apellidos);
        }
        if ($this->employee_id && $this->employee) {
            return $this->employee->nombre_completo;
        }
        if ($this->cliente_venta_nombre) {
            return $this->cliente_venta_nombre . ($this->cliente_venta_documento ? ' · ' . $this->cliente_venta_documento : '');
        }
        return '—';
    }
}
