<?php

namespace App\Livewire\biotime\area;

use App\Services\BiotimeApiClient;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class AreaIndexLive extends Component
{
    use WithPagination;

    public $pageSize = 20;

    public $areas = [];

    public $total = 0;

    public $filterAreaCode = '';

    public $filterAreaName = '';

    /** Ordering: id, area_code, area_name, -id, -area_code, -area_name */
    public $ordering = 'id';

    public $message = '';

    public $messageSuccess = false;

    public $modalOpen = false;

    public $editingId = null;

    public $formAreaCode = '';

    public $formAreaName = '';

    public $formParentArea = null;

    public $formCompany = '';

    public $companiesList = [];

    /** Lista de áreas para el select "Área superior" en el formulario */
    public $areasForParentSelect = [];

    public $showDeleteModal = false;

    public $deleteId = null;

    protected $paginationTheme = 'tailwind';

    protected BiotimeApiClient $client;

    public function boot(BiotimeApiClient $client)
    {
        $this->client = $client;
    }

    public function mount()
    {
        $this->authorize('biotime.view');
        $this->loadCompanies();
        $this->loadAreas();
    }

    public function loadCompanies()
    {
        try {
            $response = $this->client->listCompanies(['page_size' => 500]);
            $this->companiesList = $response['data'] ?? [];
        } catch (\Throwable $e) {
            $this->companiesList = [];
        }
    }

    protected function ensureCompaniesFromAreas(): void
    {
        if (! empty($this->companiesList) || empty($this->areas)) {
            return;
        }
        $seen = [];
        foreach ($this->areas as $area) {
            $company = $area['company'] ?? null;
            if (is_array($company) && ! empty($company['id']) && ! isset($seen[$company['id']])) {
                $seen[$company['id']] = true;
                $this->companiesList[] = $company;
            }
        }
    }

    public function loadAreas()
    {
        $this->message = '';
        try {
            $query = [
                'page' => $this->getPage(),
                'page_size' => $this->pageSize,
            ];
            if ($this->filterAreaCode !== '') {
                $query['area_code_icontains'] = $this->filterAreaCode;
            }
            if ($this->filterAreaName !== '') {
                $query['area_name_icontains'] = $this->filterAreaName;
            }
            if ($this->ordering !== '') {
                $query['ordering'] = $this->ordering;
            }
            $response = $this->client->listAreas($query);
            $this->areas = $response['data'] ?? [];
            $this->total = (int) ($response['count'] ?? 0);
            $this->ensureCompaniesFromAreas();
        } catch (\Throwable $e) {
            $this->message = 'Error al cargar áreas: ' . $e->getMessage();
            $this->areas = [];
        }
    }

    public function updatedPage($page = null, $value = null)
    {
        $this->loadAreas();
    }

    public function updatedFilterAreaCode()
    {
        $this->resetPage();
        $this->loadAreas();
    }

    public function updatedFilterAreaName()
    {
        $this->resetPage();
        $this->loadAreas();
    }

    public function updatedOrdering()
    {
        $this->resetPage();
        $this->loadAreas();
    }

    public function updatedPageSize()
    {
        $this->resetPage();
        $this->loadAreas();
    }

    public function loadAreasForParentSelect(): void
    {
        try {
            $response = $this->client->listAreas(['page_size' => 500]);
            $this->areasForParentSelect = $response['data'] ?? [];
        } catch (\Throwable $e) {
            $this->areasForParentSelect = [];
        }
    }

    public function openCreateModal()
    {
        $this->authorize('biotime.create');
        $this->editingId = null;
        $this->formAreaCode = '';
        $this->formAreaName = '';
        $this->formParentArea = '';
        $this->formCompany = $this->companiesList[0]['id'] ?? '';
        $this->loadAreasForParentSelect();
        $this->modalOpen = true;
        $this->resetValidation();
    }

    public function openEditModal(int $id)
    {
        $this->authorize('biotime.update');
        try {
            $area = $this->client->getArea($id);
            $this->editingId = $id;
            $this->formAreaCode = $area['area_code'] ?? '';
            $this->formAreaName = $area['area_name'] ?? '';
            $parent = $area['parent_area'] ?? null;
            $this->formParentArea = $parent !== null && $parent !== '' ? (string) $parent : '';
            $company = $area['company'] ?? null;
            $this->formCompany = is_array($company) ? ($company['id'] ?? '') : '';
            $this->loadAreasForParentSelect();
            $this->modalOpen = true;
            $this->resetValidation();
        } catch (\Throwable $e) {
            $this->message = 'Error al cargar área: ' . $e->getMessage();
            $this->messageSuccess = false;
        }
    }

    public function closeModal()
    {
        $this->modalOpen = false;
        $this->editingId = null;
    }

    /**
     * Comprueba si ya existe un área con el código dado (excluyendo id al editar).
     */
    protected function areaCodeExists(string $code, ?int $excludeId = null): bool
    {
        if ($code === '') {
            return false;
        }
        try {
            $response = $this->client->listAreas([
                'area_code' => $code,
                'page_size' => 5,
            ]);
            $data = $response['data'] ?? [];
            foreach ($data as $area) {
                $id = (int) ($area['id'] ?? 0);
                if ($excludeId !== null && $id === $excludeId) {
                    continue;
                }
                if (($area['area_code'] ?? '') === $code) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function saveArea()
    {
        $this->authorize($this->editingId ? 'biotime.update' : 'biotime.create');
        $this->validate([
            'formAreaCode' => ['required', 'string', 'max:255'],
            'formAreaName' => ['required', 'string', 'max:255'],
            'formCompany' => ['required', 'numeric', 'min:1'],
        ], [], [
            'formAreaCode' => 'código de área',
            'formAreaName' => 'nombre de área',
            'formCompany' => 'empresa',
        ]);

        if ($this->areaCodeExists($this->formAreaCode, $this->editingId)) {
            $this->addError('formAreaCode', 'El código de área ya existe. Debe ser único.');
            return;
        }

        try {
            $data = [
                'area_code' => $this->formAreaCode,
                'area_name' => $this->formAreaName,
                'company' => (int) $this->formCompany,
                'parent_area' => $this->formParentArea !== '' && $this->formParentArea !== null ? (int) $this->formParentArea : null,
            ];
            if ($this->editingId) {
                $this->client->updateArea($this->editingId, $data);
                $this->message = 'Área actualizada correctamente.';
            } else {
                $this->client->createArea($data);
                $this->message = 'Área creada correctamente.';
            }
            $this->messageSuccess = true;
            $this->closeModal();
            $this->loadAreas();
        } catch (\Throwable $e) {
            $this->message = 'Error: ' . $e->getMessage();
            $this->messageSuccess = false;
        }
    }

    public function confirmDelete(int $id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete()
    {
        $this->deleteId = null;
        $this->showDeleteModal = false;
    }

    public function deleteArea()
    {
        $this->authorize('biotime.delete');
        if ($this->deleteId === null) {
            return;
        }
        try {
            $this->client->deleteArea((int) $this->deleteId);
            $this->message = 'Área eliminada correctamente.';
            $this->messageSuccess = true;
            $this->cancelDelete();
            $this->loadAreas();
        } catch (\Throwable $e) {
            $this->message = 'Error al eliminar: ' . $e->getMessage();
            $this->messageSuccess = false;
        }
    }

    public function render()
    {
        $currentPage = $this->getPage();
        $paginator = new LengthAwarePaginator(
            $this->areas,
            $this->total,
            $this->pageSize,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );
        $paginator->withQueryString();

        return view('livewire.biotime.area.area-index-live', [
            'areasPaginator' => $paginator,
        ]);
    }
}
