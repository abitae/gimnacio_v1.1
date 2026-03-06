<?php

namespace App\Livewire\Biotime\Department;

use App\Services\BiotimeApiClient;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentIndexLive extends Component
{
    use WithPagination;

    public $pageSize = 20;

    public $departments = [];

    public $total = 0;

    public $filterDeptCode = '';

    public $filterDeptName = '';

    /** Ordering: id, dept_code, dept_name, -id, -dept_code, -dept_name */
    public $ordering = 'id';

    public $message = '';

    public $messageSuccess = false;

    public $modalOpen = false;

    public $editingId = null;

    public $formDeptCode = '';

    public $formDeptName = '';

    public $formCompany = '';

    public $formParentDept = '';

    public $companiesList = [];

    public $departmentsForParentSelect = [];

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
        $this->loadDepartments();
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

    protected function ensureCompaniesFromDepartments(): void
    {
        if (! empty($this->companiesList) || empty($this->departments)) {
            return;
        }
        $seen = [];
        foreach ($this->departments as $dept) {
            $company = $dept['company'] ?? null;
            if (is_array($company) && ! empty($company['id']) && ! isset($seen[$company['id']])) {
                $seen[$company['id']] = true;
                $this->companiesList[] = $company;
            }
        }
    }

    public function loadDepartmentsForParentSelect(): void
    {
        try {
            $response = $this->client->listDepartments(['page_size' => 500]);
            $this->departmentsForParentSelect = $response['data'] ?? [];
        } catch (\Throwable $e) {
            $this->departmentsForParentSelect = [];
        }
    }

    public function loadDepartments()
    {
        $this->message = '';
        try {
            $query = [
                'page' => $this->getPage(),
                'page_size' => $this->pageSize,
            ];
            if ($this->filterDeptCode !== '') {
                $query['dept_code_icontains'] = $this->filterDeptCode;
            }
            if ($this->filterDeptName !== '') {
                $query['dept_name_icontains'] = $this->filterDeptName;
            }
            if ($this->ordering !== '') {
                $query['ordering'] = $this->ordering;
            }
            $response = $this->client->listDepartments($query);
            $this->departments = $response['data'] ?? [];
            $this->total = (int) ($response['count'] ?? 0);
            $this->ensureCompaniesFromDepartments();
        } catch (\Throwable $e) {
            $this->message = 'Error al cargar departamentos: ' . $e->getMessage();
            $this->departments = [];
        }
    }

    public function updatedPage($page = null, $value = null)
    {
        $this->loadDepartments();
    }

    public function updatedFilterDeptCode()
    {
        $this->resetPage();
        $this->loadDepartments();
    }

    public function updatedFilterDeptName()
    {
        $this->resetPage();
        $this->loadDepartments();
    }

    public function updatedOrdering()
    {
        $this->resetPage();
        $this->loadDepartments();
    }

    public function updatedPageSize()
    {
        $this->resetPage();
        $this->loadDepartments();
    }

    public function openCreateModal()
    {
        $this->authorize('biotime.create');
        $this->editingId = null;
        $this->formDeptCode = '';
        $this->formDeptName = '';
        $this->formCompany = $this->companiesList[0]['id'] ?? '';
        $this->formParentDept = '';
        $this->loadDepartmentsForParentSelect();
        $this->modalOpen = true;
        $this->resetValidation();
    }

    public function openEditModal(int $id)
    {
        $this->authorize('biotime.update');
        try {
            $dept = $this->client->getDepartment($id);
            $this->editingId = $id;
            $this->formDeptCode = $dept['dept_code'] ?? '';
            $this->formDeptName = $dept['dept_name'] ?? '';
            $company = $dept['company'] ?? null;
            $this->formCompany = is_array($company) ? ($company['id'] ?? '') : '';
            $parent = $dept['parent_dept'] ?? null;
            $this->formParentDept = $parent !== null && $parent !== '' ? (string) $parent : '';
            $this->loadDepartmentsForParentSelect();
            $this->modalOpen = true;
            $this->resetValidation();
        } catch (\Throwable $e) {
            $this->message = 'Error al cargar departamento: ' . $e->getMessage();
            $this->messageSuccess = false;
        }
    }

    public function closeModal()
    {
        $this->modalOpen = false;
        $this->editingId = null;
    }

    protected function deptCodeExists(string $code, ?int $excludeId = null): bool
    {
        if ($code === '') {
            return false;
        }
        try {
            $response = $this->client->listDepartments([
                'dept_code' => $code,
                'page_size' => 5,
            ]);
            $data = $response['data'] ?? [];
            foreach ($data as $dept) {
                $id = (int) ($dept['id'] ?? 0);
                if ($excludeId !== null && $id === $excludeId) {
                    continue;
                }
                if (($dept['dept_code'] ?? '') === $code) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function saveDepartment()
    {
        $this->authorize($this->editingId ? 'biotime.update' : 'biotime.create');
        $this->validate([
            'formDeptCode' => ['required', 'string', 'max:255'],
            'formDeptName' => ['required', 'string', 'max:255'],
            'formCompany' => ['required', 'numeric', 'min:1'],
        ], [], [
            'formDeptCode' => 'código de departamento',
            'formDeptName' => 'nombre de departamento',
            'formCompany' => 'empresa',
        ]);

        if ($this->deptCodeExists($this->formDeptCode, $this->editingId)) {
            $this->addError('formDeptCode', 'El código de departamento ya existe. Debe ser único.');
            return;
        }

        try {
            $data = [
                'dept_code' => $this->formDeptCode,
                'dept_name' => $this->formDeptName,
                'company' => (int) $this->formCompany,
                'parent_dept' => $this->formParentDept !== '' && $this->formParentDept !== null ? (int) $this->formParentDept : null,
            ];
            if ($this->editingId) {
                $this->client->updateDepartment($this->editingId, $data);
                $this->message = 'Departamento actualizado correctamente.';
            } else {
                $this->client->createDepartment($data);
                $this->message = 'Departamento creado correctamente.';
            }
            $this->messageSuccess = true;
            $this->closeModal();
            $this->loadDepartments();
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

    public function deleteDepartment()
    {
        $this->authorize('biotime.delete');
        if ($this->deleteId === null) {
            return;
        }
        try {
            $this->client->deleteDepartment((int) $this->deleteId);
            $this->message = 'Departamento eliminado correctamente.';
            $this->messageSuccess = true;
            $this->cancelDelete();
            $this->loadDepartments();
        } catch (\Throwable $e) {
            $this->message = 'Error al eliminar: ' . $e->getMessage();
            $this->messageSuccess = false;
        }
    }

    public function render()
    {
        $currentPage = $this->getPage();
        $paginator = new LengthAwarePaginator(
            $this->departments,
            $this->total,
            $this->pageSize,
            $currentPage,
            ['path' => request()->url(), 'pageName' => 'page']
        );
        $paginator->withQueryString();

        return view('livewire.biotime.department.department-index-live', [
            'departmentsPaginator' => $paginator,
        ]);
    }
}
