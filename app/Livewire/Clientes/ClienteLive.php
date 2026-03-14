<?php

namespace App\Livewire\Clientes;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ClienteLive extends Component
{
    use FlashesToast, WithPagination, WithFileUploads;

    /** Tamaño máximo del lado largo de la foto de perfil (px). */
    private const FOTO_MAX_WIDTH = 800;

    /** Calidad JPEG para fotos de perfil (0-100). */
    private const FOTO_JPEG_QUALITY = 88;

    // Filters and pagination
    public $search = '';
    public $estadoFilter = '';
    public $perPage = 15;

    // Modal state
    public $modalState = [
        'create' => false,
        'delete' => false,
        'photo' => false,
        'salud' => false,
    ];

    /** ID del cliente para el modal de datos de salud (gestión nutricional). */
    public ?int $saludClienteId = null;

    // Selected items
    public $clienteId = null;
    public $photoClienteId = null;
    public $selectedClienteId = null;
    public $selectedCliente = null; // Cached selected cliente

    // Photo upload
    public $foto = null;
    public $currentPhoto = null;
    public $capturedPhotoUrl = null; // URL temporal de la foto capturada

    // Form data grouped
    public $formData = [
        'tipo_documento' => 'DNI',
        'numero_documento' => '',
        'nombres' => '',
        'apellidos' => '',
        'telefono' => '',
        'email' => '',
        'direccion' => '',
        'ocupacion' => '',
        'fecha_nacimiento' => '',
        'lugar_nacimiento' => '',
        'estado_civil' => '',
        'numero_hijos' => '',
        'placa_carro' => '',
        'sexo' => '',
        'biotime_state' => false,
        'biotime_update' => false,
        'datos_emergencia' => [
            'nombre' => '',
            'telefono' => '',
            'relacion' => '',
        ],
        'consentimientos' => [
            'uso_imagen' => false,
            'tratamiento_datos' => false,
        ],
    ];

    protected $paginationTheme = 'tailwind';

    protected ClienteService $service;

    public function boot(ClienteService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->authorize('clientes.view');
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
    }

    public function updatingEstadoFilter()
    {
        $this->resetPage();
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->authorize('clientes.create');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('clientes.update');
        $cliente = $this->service->find($id);

        if (!$cliente) {
            $this->flashToast('error', 'Cliente no encontrado');
            return;
        }

        $this->clienteId = $cliente->id;
        $this->mapClienteToForm($cliente);
        $this->modalState['create'] = true;
    }

    public function openDeleteModal($id)
    {
        $this->authorize('clientes.delete');
        $this->clienteId = $id;
        $this->modalState['delete'] = true;
    }

    public function openPhotoModal($id = null)
    {
        $this->authorize('clientes.update');
        $this->photoClienteId = $id;
        $this->foto = null;
        $this->capturedPhotoUrl = null;

        if ($id) {
            $cliente = $this->service->find($id);
            $this->currentPhoto = $cliente && $cliente->foto ? $cliente->foto : null;
        } else {
            $this->currentPhoto = null;
        }

        $this->modalState['photo'] = true;
    }

    /** Limpia la foto capturada temporal (al hacer "Tomar otra foto"). */
    public function clearCapturedPhoto()
    {
        if (isset($this->formData['foto_captured'])) {
            $tempPath = $this->formData['foto_captured'];
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
            unset($this->formData['foto_captured']);
        }
        $this->capturedPhotoUrl = null;
    }

    public function openSaludModal(int $clienteId): void
    {
        $this->saludClienteId = $clienteId;
        $this->modalState['salud'] = true;
    }

    #[On('close-salud-modal')]
    public function closeSaludModal(): void
    {
        $this->modalState['salud'] = false;
        $this->saludClienteId = null;
    }

    public function closeCreateModal(): void
    {
        $this->cleanupTemporaryCapturedPhoto();
        $this->modalState['create'] = false;
        $this->resetForm();
    }

    public function closeDeleteModal(): void
    {
        $this->modalState['delete'] = false;
        $this->clienteId = null;
    }

    public function closePhotoModal(): void
    {
        $this->cleanupTemporaryCapturedPhoto();
        $this->modalState['photo'] = false;
        $this->photoClienteId = null;
        $this->foto = null;
        $this->currentPhoto = null;
        $this->capturedPhotoUrl = null;
    }

    public function closeModal()
    {
        if (isset($this->formData['foto_captured'])) {
            $tempPath = $this->formData['foto_captured'];
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
            unset($this->formData['foto_captured']);
        }

        $this->modalState = [
            'create' => false,
            'delete' => false,
            'photo' => false,
            'salud' => false,
        ];
        $this->saludClienteId = null;
        $this->photoClienteId = null;
        $this->clienteId = null;
        $this->foto = null;
        $this->currentPhoto = null;
        $this->capturedPhotoUrl = null;
        $this->resetForm();
    }

    public function capturePhoto($imageData)
    {
        try {
            if (empty($imageData)) {
                $this->flashToast('error', 'No se recibió ninguna imagen.');
                return;
            }

            $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData), true);
            if ($decoded === false || empty($decoded)) {
                $this->flashToast('error', 'La imagen capturada no es válida.');
                return;
            }

            $maxSize = 5 * 1024 * 1024; // 5MB antes de procesar
            if (strlen($decoded) > $maxSize) {
                $this->flashToast('error', 'La imagen es demasiado grande. Máximo 5MB.');
                return;
            }

            $path = $this->processAndStoreImageFromBinary($decoded, 'temp');
            if ($path === null) {
                $this->flashToast('error', 'El archivo capturado no es una imagen válida (JPEG, PNG o WEBP).');
                return;
            }

            $this->formData['foto_captured'] = $path;
            $this->capturedPhotoUrl = Storage::disk('public')->url($path);
            $this->flashToast('success', 'Foto capturada correctamente.');
        } catch (\Exception $e) {
            $this->flashToast('error', 'Error al capturar la foto: ' . $e->getMessage());
        }
    }

    public function uploadPhoto()
    {
        $this->authorize('clientes.update');
        try {
            $path = null;

            if (isset($this->formData['foto_captured'])) {
                $tempPath = $this->formData['foto_captured'];
                if (!Storage::disk('public')->exists($tempPath)) {
                    $this->flashToast('error', 'La foto capturada no se encontró. Captura la foto nuevamente.');
                    return;
                }
                $binary = Storage::disk('public')->get($tempPath);
                $path = $this->processAndStoreImageFromBinary($binary, 'clientes');
                if ($path === null) {
                    $this->flashToast('error', 'No se pudo procesar la imagen capturada.');
                    return;
                }
                Storage::disk('public')->delete($tempPath);
            } elseif ($this->foto) {
                $this->validate([
                    'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
                ], [
                    'foto.required' => 'Debes seleccionar una imagen.',
                    'foto.image' => 'El archivo debe ser una imagen válida.',
                    'foto.mimes' => 'La imagen debe ser JPEG, JPG, PNG o WEBP.',
                    'foto.max' => 'La imagen no debe pesar más de 5MB.',
                ]);
                $path = $this->processAndStoreUploadedFile($this->foto);
                if ($path === null) {
                    $this->flashToast('error', 'No se pudo procesar la imagen.');
                    return;
                }
            } else {
                $this->flashToast('error', 'Debes seleccionar o capturar una imagen.');
                return;
            }

            if (!$this->photoClienteId) {
                $this->formData['foto_temp'] = $path;
                unset($this->formData['foto_captured']);
                $this->flashToast('success', 'Foto guardada. Completa el formulario y guarda el cliente.');
                $this->closePhotoModal();
                return;
            }

            $cliente = $this->service->find($this->photoClienteId);
            if (!$cliente) {
                $this->flashToast('error', 'Cliente no encontrado');
                return;
            }

            if ($cliente->foto && Storage::disk('public')->exists($cliente->foto)) {
                Storage::disk('public')->delete($cliente->foto);
            }

            $clienteActualizado = $this->service->update($this->photoClienteId, ['foto' => $path]);

            unset($this->formData['foto_captured']);
            $this->foto = null;
            $this->capturedPhotoUrl = null;

            if ($this->selectedClienteId === $this->photoClienteId) {
                $this->selectedCliente = $clienteActualizado;
            }

            $this->flashToast('success', 'Foto guardada correctamente.');
            $this->closePhotoModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', 'Error al subir la foto: ' . $e->getMessage());
        }
    }

    public function save()
    {
        $this->authorize($this->clienteId ? 'clientes.update' : 'clientes.create');
        try {
            $data = $this->mapFormToData();

            // Si hay una foto temporal guardada durante el registro, incluirla
            if (isset($this->formData['foto_temp'])) {
                $data['foto'] = $this->formData['foto_temp'];
                unset($this->formData['foto_temp']);
            }

            if ($this->clienteId) {
                $this->service->update($this->clienteId, $data);
                $this->flashToast('success', 'Cliente actualizado correctamente');
            } else {
                $data['estado_cliente'] = 'inactivo';
                $data['biotime_state'] = false;
                $data['biotime_update'] = false;
                $this->service->create($data);
                $this->flashToast('success', 'Cliente creado correctamente');
            }

            $this->closeCreateModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete()
    {
        $this->authorize('clientes.delete');
        try {
            $this->service->delete($this->clienteId);
            $this->flashToast('success', 'Cliente eliminado correctamente');
            $this->closeDeleteModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function mapClienteToForm(Cliente $cliente): void
    {
        $this->formData = [
            'tipo_documento' => $cliente->tipo_documento,
            'numero_documento' => $cliente->numero_documento,
            'nombres' => $cliente->nombres,
            'apellidos' => $cliente->apellidos,
            'telefono' => $cliente->telefono ?? '',
            'email' => $cliente->email ?? '',
            'direccion' => $cliente->direccion ?? '',
            'ocupacion' => $cliente->ocupacion ?? '',
            'fecha_nacimiento' => $cliente->fecha_nacimiento ? $cliente->fecha_nacimiento->format('Y-m-d') : '',
            'lugar_nacimiento' => $cliente->lugar_nacimiento ?? '',
            'estado_civil' => $cliente->estado_civil ?? '',
            'numero_hijos' => $cliente->numero_hijos !== null ? (string) $cliente->numero_hijos : '',
            'placa_carro' => $cliente->placa_carro ?? '',
            'sexo' => $cliente->sexo ?? '',
            'biotime_state' => (bool) $cliente->biotime_state,
            'biotime_update' => (bool) $cliente->biotime_update,
            'datos_emergencia' => [
                'nombre' => $cliente->datos_emergencia['nombre_contacto'] ?? '',
                'telefono' => $cliente->datos_emergencia['telefono_contacto'] ?? '',
                'relacion' => $cliente->datos_emergencia['relacion'] ?? '',
            ],
            'consentimientos' => [
                'uso_imagen' => $cliente->consentimientos['uso_imagen'] ?? false,
                'tratamiento_datos' => $cliente->consentimientos['tratamiento_datos'] ?? false,
            ],
        ];
    }

    protected function mapFormToData(): array
    {
        return [
            'tipo_documento' => $this->formData['tipo_documento'],
            'numero_documento' => $this->formData['numero_documento'],
            'nombres' => $this->formData['nombres'],
            'apellidos' => $this->formData['apellidos'],
            'telefono' => $this->formData['telefono'] ?: null,
            'email' => $this->formData['email'] ?: null,
            'direccion' => $this->formData['direccion'] ?: null,
            'ocupacion' => $this->formData['ocupacion'] ?: null,
            'fecha_nacimiento' => $this->formData['fecha_nacimiento'] ?: null,
            'lugar_nacimiento' => $this->formData['lugar_nacimiento'] ?: null,
            'estado_civil' => $this->formData['estado_civil'] ?: null,
            'numero_hijos' => $this->formData['numero_hijos'] !== '' ? (int) $this->formData['numero_hijos'] : null,
            'placa_carro' => $this->formData['placa_carro'] ?: null,
            'sexo' => $this->formData['sexo'] ?: null,
            'biotime_update' => (bool) ($this->formData['biotime_update'] ?? false),
            'datos_emergencia' => [
                'nombre_contacto' => $this->formData['datos_emergencia']['nombre'] ?: null,
                'telefono_contacto' => $this->formData['datos_emergencia']['telefono'] ?: null,
                'relacion' => $this->formData['datos_emergencia']['relacion'] ?: null,
            ],
            'consentimientos' => [
                'uso_imagen' => $this->formData['consentimientos']['uso_imagen'],
                'tratamiento_datos' => $this->formData['consentimientos']['tratamiento_datos'],
                'fecha_consentimiento' => now()->toDateString(),
            ],
        ];
    }

    protected function cleanupTemporaryCapturedPhoto(): void
    {
        if (isset($this->formData['foto_captured'])) {
            $tempPath = $this->formData['foto_captured'];
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
            unset($this->formData['foto_captured']);
        }
    }

    protected function resetForm(): void
    {
        $this->clienteId = null;

        // Limpiar fotos temporales del formData si existen
        if (isset($this->formData['foto_captured'])) {
            $tempPath = $this->formData['foto_captured'];
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
            unset($this->formData['foto_captured']);
        }

        if (isset($this->formData['foto_temp'])) {
            unset($this->formData['foto_temp']);
        }

        $this->capturedPhotoUrl = null;

        $this->formData = [
            'tipo_documento' => 'DNI',
            'numero_documento' => '',
            'nombres' => '',
            'apellidos' => '',
            'telefono' => '',
            'email' => '',
            'direccion' => '',
            'ocupacion' => '',
            'fecha_nacimiento' => '',
            'lugar_nacimiento' => '',
            'estado_civil' => '',
            'numero_hijos' => '',
            'placa_carro' => '',
            'sexo' => '',
            'biotime_state' => false,
            'biotime_update' => false,
            'datos_emergencia' => [
                'nombre' => '',
                'telefono' => '',
                'relacion' => '',
            ],
            'consentimientos' => [
                'uso_imagen' => false,
                'tratamiento_datos' => false,
            ],
        ];
    }

    /**
     * Procesa datos binarios de imagen: redimensiona y comprime a JPEG, guarda en disco y devuelve la ruta relativa.
     * Subcarpeta: 'temp' para capturas temporales, o 'clientes' para fotos definitivas.
     */
    protected function processAndStoreImageFromBinary(string $binary, string $subfolder): ?string
    {
        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            return null;
        }
        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $newWidth = $width;
        $newHeight = $height;
        $maxSide = self::FOTO_MAX_WIDTH;
        if ($width > $maxSide || $height > $maxSide) {
            if ($width >= $height) {
                $newWidth = $maxSide;
                $newHeight = (int) round($height * ($maxSide / $width));
            } else {
                $newHeight = $maxSide;
                $newWidth = (int) round($width * ($maxSide / $height));
            }
        }

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        if ($thumb === false) {
            imagedestroy($source);
            return null;
        }

        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        $dir = $subfolder === 'temp' ? 'clientes/fotos/temp' : 'clientes/fotos';
        $filename = ($subfolder === 'temp' ? 'captured_' : 'cliente_') . uniqid() . '_' . time() . '.jpg';
        $fullPath = $dir . '/' . $filename;

        ob_start();
        $ok = imagejpeg($thumb, null, self::FOTO_JPEG_QUALITY);
        $jpegData = ob_get_clean();
        imagedestroy($thumb);

        if (!$ok || $jpegData === false || $jpegData === '') {
            return null;
        }

        Storage::disk('public')->put($fullPath, $jpegData);
        return $fullPath;
    }

    /**
     * Procesa un archivo subido por Livewire: lee, redimensiona y guarda en clientes/fotos.
     */
    protected function processAndStoreUploadedFile($uploadedFile): ?string
    {
        $path = $uploadedFile->getRealPath();
        if (!$path || !is_readable($path)) {
            return null;
        }
        $binary = file_get_contents($path);
        if ($binary === false) {
            return null;
        }
        return $this->processAndStoreImageFromBinary($binary, 'clientes');
    }

    protected function handleValidationErrors(\Illuminate\Validation\ValidationException $e): void
    {
        foreach ($e->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $this->flashToast('error', $message);
            }
        }
    }

    public function selectCliente($id)
    {
        $this->selectedClienteId = $id;
        $this->selectedCliente = $this->service->find($id);
    }

    public function verPerfil(int $id): void
    {
        $this->redirect(route('clientes.perfil', ['cliente' => $id]), navigate: true);
    }

    public function render()
    {
        if ($this->search || $this->estadoFilter) {
            $clientes = $this->service->search($this->search, $this->estadoFilter, $this->perPage);
        } else {
            $clientes = $this->service->paginate($this->perPage);
        }
        return view('livewire.clientes.cliente-live', [
            'clientes' => $clientes,
            'selectedCliente' => $this->selectedCliente,
        ]);
    }
}
