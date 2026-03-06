<?php

namespace App\Livewire\Productos;

use App\Livewire\Concerns\FlashesToast;
use App\Services\ProductoService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductoLive extends Component
{
    use FlashesToast, WithPagination, WithFileUploads;

    public $search = '';
    public $categoriaFilter = '';
    public $estadoFilter = '';
    public $stockBajoFilter = false;
    public $perPage = 15;

    public $modalState = ['create' => false, 'delete' => false, 'image' => false];
    public $productoId = null;
    public $imageProductoId = null;
    public $imagen = null;
    public $currentImagen = null;

    public $formData = [
        'codigo' => '',
        'nombre' => '',
        'descripcion' => '',
        'categoria_id' => null,
        'precio_venta' => '0.00',
        'precio_compra' => '0.00',
        'stock_actual' => '0',
        'stock_minimo' => '0',
        'unidad_medida' => 'unidad',
        'estado' => 'activo',
    ];

    protected $paginationTheme = 'tailwind';
    protected ProductoService $service;

    public function boot(ProductoService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->authorize('productos.view');
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->authorize('productos.create');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('productos.update');
        $producto = $this->service->find($id);
        if (!$producto) {
            $this->flashToast('error', 'Producto no encontrado');
            return;
        }

        $this->productoId = $producto->id;
        $this->formData = [
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion ?? '',
            'categoria_id' => $producto->categoria_id,
            'precio_venta' => $producto->precio_venta,
            'precio_compra' => $producto->precio_compra,
            'stock_actual' => $producto->stock_actual,
            'stock_minimo' => $producto->stock_minimo,
            'unidad_medida' => $producto->unidad_medida,
            'estado' => $producto->estado,
        ];
        $this->modalState['create'] = true;
    }

    public function openImageModal($id)
    {
        $this->authorize('productos.update');
        $this->imageProductoId = $id;
        $this->imagen = null;
        
        $producto = $this->service->find($id);
        $this->currentImagen = $producto && $producto->imagen ? $producto->imagen : null;
        
        $this->modalState['image'] = true;
    }

    public function uploadImage()
    {
        $this->authorize('productos.update');
        try {
            $this->validate([
                'imagen' => [
                    'required',
                    'image',
                    'mimes:jpeg,jpg,png,webp',
                    'max:2048',
                ],
            ], [
                'imagen.required' => 'Debes seleccionar una imagen.',
                'imagen.image' => 'El archivo debe ser una imagen válida.',
                'imagen.mimes' => 'La imagen debe ser de tipo: JPEG, JPG, PNG o WEBP.',
                'imagen.max' => 'La imagen no debe pesar más de 2MB.',
            ]);

            $producto = $this->service->find($this->imageProductoId);

            if (!$producto) {
                $this->flashToast('error', 'Producto no encontrado');
                return;
            }

            // Eliminar imagen anterior si existe
            if ($producto->imagen && \Storage::disk('public')->exists($producto->imagen)) {
                \Storage::disk('public')->delete($producto->imagen);
            }

            // Guardar nueva imagen
            $path = $this->imagen->store('productos/imagenes', 'public');
            
            $this->service->update($this->imageProductoId, [
                'imagen' => $path,
            ]);
            
            $this->flashToast('success', 'Imagen subida correctamente');
            $this->closeModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores de validación se mostrarán automáticamente
        } catch (\Exception $e) {
            $this->flashToast('error', 'Error al subir la imagen: ' . $e->getMessage());
        }
    }

    public function save()
    {
        $this->authorize($this->productoId ? 'productos.update' : 'productos.create');
        try {
            if ($this->productoId) {
                $this->service->update($this->productoId, $this->formData);
                $this->flashToast('success', 'Producto actualizado exitosamente.');
            } else {
                $this->service->create($this->formData);
                $this->flashToast('success', 'Producto creado exitosamente.');
            }
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete()
    {
        $this->authorize('productos.delete');
        try {
            $this->service->delete($this->productoId);
            $this->flashToast('success', 'Producto eliminado exitosamente.');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->modalState = ['create' => false, 'delete' => false, 'image' => false];
        $this->productoId = null;
        $this->imageProductoId = null;
        $this->imagen = null;
        $this->currentImagen = null;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->formData = [
            'codigo' => '',
            'nombre' => '',
            'descripcion' => '',
            'categoria_id' => null,
            'precio_venta' => '0.00',
            'precio_compra' => '0.00',
            'stock_actual' => '0',
            'stock_minimo' => '0',
            'unidad_medida' => 'unidad',
            'estado' => 'activo',
        ];
    }

    public function render()
    {
        $filtros = [];
        if ($this->search) {
            $filtros['busqueda'] = $this->search;
        }
        if ($this->categoriaFilter) {
            $filtros['categoria_id'] = $this->categoriaFilter;
        }
        if ($this->estadoFilter) {
            $filtros['estado'] = $this->estadoFilter;
        }
        if ($this->stockBajoFilter) {
            $filtros['stock_bajo'] = true;
        }

        $productos = $this->service->obtenerProductos($this->perPage, $filtros);
        $categorias = \App\Models\Core\CategoriaProducto::where('estado', 'activa')->orderBy('nombre')->get();

        return view('livewire.productos.producto-live', [
            'productos' => $productos,
            'categorias' => $categorias,
        ]);
    }
}
