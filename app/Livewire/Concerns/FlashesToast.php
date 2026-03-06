<?php

namespace App\Livewire\Concerns;

trait FlashesToast
{
    protected function flashToast(string $type, string $message): void
    {
        session()->flash($type, $message);
        $this->dispatch('show-flash', type: $type, message: $message);
    }
}
