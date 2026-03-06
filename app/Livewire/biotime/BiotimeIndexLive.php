<?php

namespace App\Livewire\Biotime;

use App\Services\BiotimeApiClient;
use Livewire\Component;

class BiotimeIndexLive extends Component
{
    public $connectionStatus = null;

    public $connectionError = '';

    protected BiotimeApiClient $client;

    public function boot(BiotimeApiClient $client)
    {
        $this->client = $client;
    }

    public function mount()
    {
        $this->authorize('biotime.view');
        $this->checkConnection();
    }

    public function checkConnection()
    {
        $this->connectionStatus = null;
        $this->connectionError = '';

        try {
            $this->client->testConnection();
            $this->connectionStatus = true;
        } catch (\Throwable $e) {
            $this->connectionStatus = false;
            $this->connectionError = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.biotime.biotime-index-live');
    }
}
