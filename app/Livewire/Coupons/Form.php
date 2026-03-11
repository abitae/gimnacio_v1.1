<?php

namespace App\Livewire\Coupons;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\DiscountCoupon;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public ?int $couponId = null;

    public ?DiscountCoupon $coupon = null;

    public array $form = [
        'codigo' => '',
        'nombre' => '',
        'descripcion' => '',
        'valor_descuento' => 0,
        'fecha_inicio' => '',
        'fecha_vencimiento' => '',
        'cantidad_max_usos' => null,
        'aplica_a' => 'todos',
        'estado' => 'activo',
    ];

    public function mount(): void
    {
        $this->couponId = $this->coupon?->id;
        $this->authorize($this->couponId ? 'cupones.update' : 'cupones.create');
        if ($this->coupon) {
            $c = $this->coupon;
            $this->form = [
                'codigo' => $c->codigo,
                'nombre' => $c->nombre,
                'descripcion' => $c->descripcion ?? '',
                'valor_descuento' => (float) $c->valor_descuento,
                'fecha_inicio' => $c->fecha_inicio->format('Y-m-d'),
                'fecha_vencimiento' => $c->fecha_vencimiento->format('Y-m-d'),
                'cantidad_max_usos' => $c->cantidad_max_usos,
                'aplica_a' => $c->aplica_a,
                'estado' => $c->estado,
            ];
        } else {
            $this->form['fecha_inicio'] = now()->format('Y-m-d');
            $this->form['fecha_vencimiento'] = now()->addMonth()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->authorize($this->couponId ? 'cupones.update' : 'cupones.create');
        $rules = [
            'form.codigo' => 'required|string|max:60',
            'form.nombre' => 'required|string|max:120',
            'form.valor_descuento' => 'required|numeric|min:0',
            'form.fecha_inicio' => 'required|date',
            'form.fecha_vencimiento' => 'required|date|after_or_equal:form.fecha_inicio',
            'form.cantidad_max_usos' => 'nullable|integer|min:1',
            'form.aplica_a' => 'required|in:pos,matricula,membresia,clases,todos',
            'form.estado' => 'required|in:activo,inactivo',
        ];
        if ($this->couponId) {
            $rules['form.codigo'] = 'required|string|max:60|unique:discount_coupons,codigo,' . $this->couponId;
        } else {
            $rules['form.codigo'] = 'required|string|max:60|unique:discount_coupons,codigo';
        }
        $this->validate($rules);

        try {
            $data = array_merge($this->form, ['tipo_descuento' => 'monto_fijo']);
            $data['cantidad_max_usos'] = $data['cantidad_max_usos'] ?: null;
            if ($this->couponId) {
                DiscountCoupon::findOrFail($this->couponId)->update($data);
                $this->flashToast('success', 'Cupón actualizado.');
            } else {
                DiscountCoupon::create($data);
                $this->flashToast('success', 'Cupón creado.');
            }
            $this->redirectRoute('cupones.index', navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.coupons.form')->layout('layouts.app', ['title' => $this->couponId ? 'Editar cupón' : 'Nuevo cupón']);
    }
}
