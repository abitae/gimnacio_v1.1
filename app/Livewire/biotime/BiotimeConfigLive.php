<?php

namespace App\Livewire\Biotime;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Integration\BiotimeSetting;
use App\Services\BiotimeApiClient;
use Livewire\Component;

class BiotimeConfigLive extends Component
{
    use FlashesToast;
    public $base_url = '';

    public $username = '';

    public $password = '';

    public $auth_type = 'jwt';

    public $enabled = true;

    public $last_tested_at = null;

    public $testMessage = '';

    public $testSuccess = false;

    protected BiotimeApiClient $client;

    public function boot(BiotimeApiClient $client)
    {
        $this->client = $client;
    }

    public function mount()
    {
        $this->authorize('biotime.view');
        $setting = BiotimeSetting::getInstance();
        if ($setting) {
            $this->base_url = $setting->base_url ?? '';
            $this->username = $setting->username ?? '';
            $this->password = ''; // never pre-fill password
            $this->auth_type = $setting->auth_type ?? 'jwt';
            $this->enabled = (bool) $setting->enabled;
            $this->last_tested_at = $setting->last_tested_at?->format('d/m/Y H:i');
        } else {
            $config = config('services.biotime', []);
            $this->base_url = $config['base_url'] ?? '';
            $this->username = $config['username'] ?? '';
            $this->auth_type = $config['auth_type'] ?? 'jwt';
        }
    }

    protected function rules(): array
    {
        return [
            'base_url' => ['required', 'string', 'url'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'auth_type' => ['required', 'in:jwt,token'],
            'enabled' => ['boolean'],
        ];
    }

    public function save()
    {
        $this->authorize('biotime.update');
        $this->validate();

        $data = [
            'base_url' => rtrim($this->base_url, '/'),
            'username' => $this->username,
            'auth_type' => $this->auth_type,
            'enabled' => $this->enabled,
        ];
        if ($this->password !== '') {
            $data['password'] = $this->password;
        }

        $setting = BiotimeSetting::getInstance();
        if ($setting) {
            $setting->update($data);
        } else {
            BiotimeSetting::create(array_merge($data, ['password' => $this->password ?: '']));
        }

        $this->last_tested_at = BiotimeSetting::getInstance()?->last_tested_at?->format('d/m/Y H:i');
        $this->flashToast('success', __('Configuración guardada correctamente.'));
    }

    public function testConnection()
    {
        $this->validate([
            'base_url' => ['required', 'string', 'url'],
            'username' => ['required', 'string'],
            'auth_type' => ['required', 'in:jwt,token'],
        ]);

        $password = $this->password;
        if ($password === '' && BiotimeSetting::getInstance()?->password) {
            $password = BiotimeSetting::getInstance()->password;
        }
        if ($password === '') {
            $this->addError('password', __('La contraseña es necesaria para probar la conexión.'));
            return;
        }

        $this->testMessage = '';
        $this->testSuccess = false;

        try {
            $this->client->setCredentials([
                'base_url' => rtrim($this->base_url, '/'),
                'username' => $this->username,
                'password' => $password,
                'auth_type' => $this->auth_type,
            ]);
            $this->client->testConnection();

            $setting = BiotimeSetting::getInstance();
            if ($setting) {
                $setting->update(['last_tested_at' => now()]);
                $this->last_tested_at = $setting->last_tested_at->format('d/m/Y H:i');
            }

            $this->testSuccess = true;
            $this->testMessage = __('Conexión exitosa con BioTime.');
        } catch (\Throwable $e) {
            $this->testSuccess = false;
            $this->testMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.biotime.biotime-config-live');
    }
}
