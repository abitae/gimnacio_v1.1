<?php

namespace App\Services;

use App\Models\Integration\BiotimeSetting;
use App\Models\Integration\IntegrationErrorLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class BiotimeApiClient
{
    protected ?array $credentials = null;

    protected ?string $cachedToken = null;

    protected string $cacheKey = 'biotime_api_token';

    protected int $cacheTtlSeconds = 300;

    /**
     * Get credentials from BiotimeSetting (DB) or config.
     */
    public function getCredentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $setting = BiotimeSetting::getInstance();
        if ($setting && $setting->enabled && $setting->base_url) {
            $this->credentials = $setting->getCredentials();
        } else {
            $config = config('services.biotime', []);
            $baseUrl = $config['base_url'] ?? '';
            $this->credentials = [
                'base_url' => $baseUrl ? rtrim($baseUrl, '/') : '',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'auth_type' => in_array($config['auth_type'] ?? 'jwt', ['jwt', 'token'], true) ? ($config['auth_type'] ?? 'jwt') : 'jwt',
            ];
        }

        return $this->credentials;
    }

    /**
     * Set credentials explicitly (e.g. for testing with form values before save).
     */
    public function setCredentials(array $credentials): self
    {
        $this->credentials = [
            'base_url' => isset($credentials['base_url']) ? rtrim((string) $credentials['base_url'], '/') : '',
            'username' => (string) ($credentials['username'] ?? ''),
            'password' => (string) ($credentials['password'] ?? ''),
            'auth_type' => in_array($credentials['auth_type'] ?? 'jwt', ['jwt', 'token'], true) ? ($credentials['auth_type'] ?? 'jwt') : 'jwt',
        ];
        $this->cachedToken = null;
        return $this;
    }

    /**
     * Obtain auth token (JWT or Token) and cache it.
     */
    public function getToken(): string
    {
        $creds = $this->getCredentials();
        if (empty($creds['base_url']) || empty($creds['username'])) {
            throw new \InvalidArgumentException('BioTime base_url and username are required.');
        }

        $cacheKey = $this->cacheKey . '_' . md5($creds['base_url'] . $creds['username'] . $creds['auth_type']);
        $token = $this->cachedToken ?? Cache::get($cacheKey);
        if ($token) {
            return $token;
        }

        $path = $creds['auth_type'] === 'token' ? '/api-token-auth/' : '/jwt-api-token-auth/';
        $url = $creds['base_url'] . $path;

        $response = Http::acceptJson()
            ->asJson()
            ->post($url, [
                'username' => $creds['username'],
                'password' => $creds['password'],
            ]);

        if (! $response->successful()) {
            $this->logError('auth', ['url' => $url], $response->body());
            throw new \RuntimeException('BioTime authentication failed: ' . $response->body());
        }

        $body = $response->json();
        $token = $body['token'] ?? null;
        if (empty($token)) {
            $this->logError('auth', ['url' => $url, 'body' => $body], 'No token in response');
            throw new \RuntimeException('BioTime response did not contain a token.');
        }

        $this->cachedToken = $token;
        Cache::put($cacheKey, $token, $this->cacheTtlSeconds);

        return $token;
    }

    /**
     * Build Authorization header value.
     */
    protected function authHeader(): string
    {
        $creds = $this->getCredentials();
        $token = $this->getToken();
        $prefix = $creds['auth_type'] === 'token' ? 'Token' : 'JWT';
        return "{$prefix} {$token}";
    }

    /**
     * Make GET request to API.
     */
    protected function get(string $path, array $query = []): array
    {
        $creds = $this->getCredentials();
        $url = $creds['base_url'] . $path;
        $response = Http::acceptJson()
            ->withHeaders([
                'Authorization' => $this->authHeader(),
                'Content-Type' => 'application/json',
            ])
            ->get($url, $query);

        return $this->handleResponse($response, $url, $query);
    }

    /**
     * Make POST request to API.
     */
    protected function post(string $path, array $data = []): array
    {
        $creds = $this->getCredentials();
        $url = $creds['base_url'] . $path;
        $response = Http::acceptJson()
            ->withHeaders([
                'Authorization' => $this->authHeader(),
                'Content-Type' => 'application/json',
            ])
            ->post($url, $data);

        return $this->handleResponse($response, $url, $data);
    }

    /**
     * Make PUT request to API.
     */
    protected function put(string $path, array $data = []): array
    {
        $creds = $this->getCredentials();
        $url = $creds['base_url'] . $path;
        $response = Http::acceptJson()
            ->withHeaders([
                'Authorization' => $this->authHeader(),
                'Content-Type' => 'application/json',
            ])
            ->put($url, $data);

        return $this->handleResponse($response, $url, $data);
    }

    /**
     * Make DELETE request to API.
     */
    protected function delete(string $path): array
    {
        $creds = $this->getCredentials();
        $url = $creds['base_url'] . $path;
        $response = Http::acceptJson()
            ->withHeaders([
                'Authorization' => $this->authHeader(),
                'Content-Type' => 'application/json',
            ])
            ->delete($url);

        return $this->handleResponse($response, $url, []);
    }

    /**
     * Parse response and throw on API error (code != 0).
     */
    protected function handleResponse(Response $response, string $url, array $context = []): array
    {
        $rawBody = $response->body();
        $body = $response->json() ?? [];

        if (! $response->successful()) {
            $this->logError('request', ['url' => $url, 'status' => $response->status(), 'context' => $context], $rawBody);
            $message = $this->formatApiErrorMessage($response->status(), $body, $rawBody);
            throw new \RuntimeException('BioTime API error: ' . $message);
        }

        if (array_key_exists('code', $body)) {
            $code = $body['code'];
            if ($code !== 0 && $code !== '0') {
                $this->logError('request', ['url' => $url, 'context' => $context], $body['msg'] ?? 'API returned non-zero code');
                throw new \RuntimeException('BioTime API error: ' . ($body['msg'] ?? 'Unknown error'));
            }
        }

        return $body;
    }

    /**
     * Build a user-friendly error message when the API returns HTML or non-JSON (e.g. 500).
     */
    protected function formatApiErrorMessage(int $status, array $body, string $rawBody): string
    {
        if (! empty($body['msg']) && is_string($body['msg'])) {
            return $body['msg'];
        }
        $isHtml = str_starts_with(trim($rawBody), '<') || stripos($rawBody, '<html') !== false;
        if ($isHtml) {
            if ($status >= 500) {
                return 'El servidor BioTime no está disponible o respondió con un error interno (' . $status . '). Intente más tarde o contacte al administrador del sistema BioTime.';
            }
            return 'El servidor BioTime respondió con un error (' . $status . '). Compruebe la URL y la configuración.';
        }
        return $rawBody !== '' ? $rawBody : 'Error del servidor (código ' . $status . ').';
    }

    protected function logError(string $context, array $payload, string $message): void
    {
        try {
            IntegrationErrorLog::create([
                'source' => 'biotime',
                'payload' => array_merge(['context' => $context], $payload),
                'error_message' => $message,
            ]);
        } catch (\Throwable $e) {
            // avoid failing the request if logging fails
        }
    }

    /**
     * Test connection: authenticate and optionally fetch one area.
     */
    public function testConnection(): bool
    {
        $this->getToken();
        $this->get('/personnel/api/areas/', ['page_size' => 1]);
        return true;
    }

    // ==================== Company (personnel/api/companies/) ====================

    /**
     * List companies (personnel). Query params: page, page_size, ordering, etc.
     */
    public function listCompanies(array $query = []): array
    {
        return $this->get('/personnel/api/companies/', $query);
    }

    // ==================== Area (personnel/api/areas/) ====================

    /**
     * List areas (personnel).
     *
     * Query params: page, page_size, area_code, area_name, area_code_icontains,
     * area_name_icontains, ordering (e.g. id, area_code, area_name).
     */
    public function listAreas(array $query = []): array
    {
        return $this->get('/personnel/api/areas/', $query);
    }

    /**
     * Read a single area by id.
     */
    public function getArea(int $id): array
    {
        return $this->get('/personnel/api/areas/' . $id . '/');
    }

    /**
     * Create area. Required: area_code (string), area_name (string), company (int).
     * Optional: parent_area (int|null).
     */
    public function createArea(array $data): array
    {
        $body = [
            'area_code' => (string) ($data['area_code'] ?? ''),
            'area_name' => (string) ($data['area_name'] ?? ''),
            'company' => (int) ($data['company'] ?? 0),
        ];
        if (array_key_exists('parent_area', $data)) {
            $body['parent_area'] = $data['parent_area'] === null ? null : (int) $data['parent_area'];
        }
        return $this->post('/personnel/api/areas/', $body);
    }

    /**
     * Update area. Required: area_code (string), area_name (string), company (int).
     * Optional: parent_area (int|null).
     */
    public function updateArea(int $id, array $data): array
    {
        $body = [
            'area_code' => (string) ($data['area_code'] ?? ''),
            'area_name' => (string) ($data['area_name'] ?? ''),
            'company' => (int) ($data['company'] ?? 0),
        ];
        if (array_key_exists('parent_area', $data)) {
            $body['parent_area'] = $data['parent_area'] === null ? null : (int) $data['parent_area'];
        }
        return $this->put('/personnel/api/areas/' . $id . '/', $body);
    }

    /**
     * Delete area by id.
     */
    public function deleteArea(int $id): array
    {
        return $this->delete('/personnel/api/areas/' . $id . '/');
    }

    // ==================== Department (personnel/api/departments/) ====================

    /**
     * List departments (personnel).
     *
     * Query params: page, page_size, dept_code, dept_name, dept_code_icontains,
     * dept_name_icontains, company, ordering (e.g. id, dept_code, dept_name).
     */
    public function listDepartments(array $query = []): array
    {
        return $this->get('/personnel/api/departments/', $query);
    }

    /**
     * Read a single department by id.
     */
    public function getDepartment(int $id): array
    {
        return $this->get('/personnel/api/departments/' . $id . '/');
    }

    /**
     * Create department. Required: dept_code (string), dept_name (string), company (int).
     * Optional: parent_dept (int|null).
     */
    public function createDepartment(array $data): array
    {
        $body = [
            'dept_code' => (string) ($data['dept_code'] ?? ''),
            'dept_name' => (string) ($data['dept_name'] ?? ''),
            'company' => (int) ($data['company'] ?? 0),
        ];
        if (array_key_exists('parent_dept', $data)) {
            $body['parent_dept'] = $data['parent_dept'] === null || $data['parent_dept'] === '' ? null : (int) $data['parent_dept'];
        }
        return $this->post('/personnel/api/departments/', $body);
    }

    /**
     * Update department. Required: dept_code (string), dept_name (string), company (int).
     * Optional: parent_dept (int|null).
     */
    public function updateDepartment(int $id, array $data): array
    {
        $body = [
            'dept_code' => (string) ($data['dept_code'] ?? ''),
            'dept_name' => (string) ($data['dept_name'] ?? ''),
            'company' => (int) ($data['company'] ?? 0),
        ];
        if (array_key_exists('parent_dept', $data)) {
            $body['parent_dept'] = $data['parent_dept'] === null || $data['parent_dept'] === '' ? null : (int) $data['parent_dept'];
        }
        return $this->put('/personnel/api/departments/' . $id . '/', $body);
    }

    /**
     * Delete department by id.
     */
    public function deleteDepartment(int $id): array
    {
        return $this->delete('/personnel/api/departments/' . $id . '/');
    }

    // ==================== Employee (personnel/api/employees/) ====================

    /**
     * List employees (personnel).
     *
     * Query params: page, page_size, emp_code, emp_code_icontains, first_name,
     * first_name_icontains, last_name, last_name_icontains, department, areas.
     */
    public function listEmployees(array $query = []): array
    {
        return $this->get('/personnel/api/employees/', $query);
    }

    /**
     * Read a single employee by id.
     */
    public function getEmployee(int $id): array
    {
        return $this->get('/personnel/api/employees/' . $id . '/');
    }

    /**
     * Create employee.
     * POST /personnel/api/employees/
     * Headers: Content-Type: application/json, Authorization: JWT ...
     * Request Body: emp_code, department, company, area, first_name, last_name (opcionales).
     */
    public function createEmployee(array $data): array
    {
        $area = isset($data['area']) ? (array) $data['area'] : [];
        $areaId = ! empty($area) ? (int) (is_array($area) ? reset($area) : $area) : 0;
        $body = [
            'emp_code' => (string) ($data['emp_code'] ?? ''),
            'department' => (int) ($data['department'] ?? 0),
            'company' => (int) ($data['company'] ?? 0),
            'area' => $areaId > 0 ? [$areaId] : [],
        ];
        if (array_key_exists('first_name', $data)) {
            $body['first_name'] = (string) $data['first_name'];
        }
        if (array_key_exists('last_name', $data)) {
            $body['last_name'] = (string) $data['last_name'];
        }
        return $this->post('/personnel/api/employees/', $body);
    }

    /**
     * Update employee. Mismos valores que create: emp_code, department, company, area (array con un solo valor, ej. [1]), first_name, last_name.
     * Opcionales: hire_date, app_status.
     */
    public function updateEmployee(int $id, array $data): array
    {
        $area = isset($data['area']) ? (array) $data['area'] : [];
        $areaId = ! empty($area) ? (int) (is_array($area) ? reset($area) : $area) : 0;
        $body = [
            'emp_code' => (string) ($data['emp_code'] ?? ''),
            'department' => (int) ($data['department'] ?? 0),
            'company' => (int) ($data['company'] ?? 0),
            'area' => $areaId > 0 ? [$areaId] : [],
        ];
        if (array_key_exists('first_name', $data)) {
            $body['first_name'] = (string) $data['first_name'];
        }
        if (array_key_exists('last_name', $data)) {
            $body['last_name'] = (string) $data['last_name'];
        }
        if (isset($data['hire_date'])) {
            $body['hire_date'] = $data['hire_date'];
        }
        if (array_key_exists('app_status', $data)) {
            $body['app_status'] = (int) $data['app_status'];
        }
        return $this->put('/personnel/api/employees/' . $id . '/', $body);
    }

    /**
     * Delete employee by id.
     */
    public function deleteEmployee(int $id): array
    {
        return $this->delete('/personnel/api/employees/' . $id . '/');
    }

    /**
     * Adjust area: assign areas to employees.
     * Required: employees (list of employee ids), areas (list of area ids).
     */
    public function adjustArea(array $data): array
    {
        $body = [
            'employees' => array_map('intval', (array) ($data['employees'] ?? [])),
            'areas' => array_map('intval', (array) ($data['areas'] ?? [])),
        ];
        return $this->post('/personnel/api/employees/adjust_area/', $body);
    }

    /**
     * Adjust department: assign department to employees.
     * Required: employees (list of employee ids), department (int).
     */
    public function adjustDepartment(array $data): array
    {
        $body = [
            'employees' => array_map('intval', (array) ($data['employees'] ?? [])),
            'department' => (int) ($data['department'] ?? 0),
        ];
        return $this->post('/personnel/api/employees/adjust_department/', $body);
    }

    /**
     * Adjust resign: set resign for employees.
     * Required: employees (list of employee ids), resign_date (yyyy-mm-dd), resign_type (1=quit, 2=dismissed, 3=resign, 4=transfer, 5=retainJobWithoutSalary), disableatt (bool).
     * Optional: reason (string).
     */
    public function adjustRegsin(array $data): array
    {
        $body = [
            'employees' => array_map('intval', (array) ($data['employees'] ?? [])),
            'resign_date' => (string) ($data['resign_date'] ?? ''),
            'resign_type' => (int) ($data['resign_type'] ?? 0),
            'disableatt' => (bool) ($data['disableatt'] ?? true),
        ];
        if (isset($data['reason'])) {
            $body['reason'] = $data['reason'];
        }
        return $this->post('/personnel/api/employees/adjust_regsin/', $body);
    }

    /**
     * Del bio template: remove biometric templates from employees.
     * Required: employees (list of employee ids).
     * Optional: finger_print (bool), face (bool), finger_vein (bool), palm (bool).
     */
    public function delBioTemplate(array $data): array
    {
        $body = [
            'employees' => array_map('intval', (array) ($data['employees'] ?? [])),
        ];
        if (array_key_exists('finger_print', $data)) {
            $body['finger_print'] = (bool) $data['finger_print'];
        }
        if (array_key_exists('face', $data)) {
            $body['face'] = (bool) $data['face'];
        }
        if (array_key_exists('finger_vein', $data)) {
            $body['finger_vein'] = (bool) $data['finger_vein'];
        }
        if (array_key_exists('palm', $data)) {
            $body['palm'] = (bool) $data['palm'];
        }
        return $this->post('/personnel/api/employees/del_bio_template/', $body);
    }

    /**
     * Resync to device: resync employees to devices.
     * Required: employees (list of employee ids).
     */
    public function resyncToDevice(array $data): array
    {
        $body = [
            'employees' => array_map('intval', (array) ($data['employees'] ?? [])),
        ];
        return $this->post('/personnel/api/employees/resync_to_device/', $body);
    }

    /**
     * List terminals (iclock).
     */
    public function listTerminals(array $query = []): array
    {
        return $this->get('/iclock/api/terminals/', $query);
    }

    /**
     * List transactions (iclock).
     */
    public function listTransactions(array $query = []): array
    {
        return $this->get('/iclock/api/transactions/', $query);
    }

    /**
     * Get transaction report (att).
     */
    public function getTransactionReport(array $query = []): array
    {
        return $this->get('/att/api/transactionReport/', $query);
    }
}
