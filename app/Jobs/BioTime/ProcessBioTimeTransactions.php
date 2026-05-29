<?php

declare(strict_types=1);

namespace App\Jobs\BioTime;

use App\Jobs\BioTime\Concerns\ProcessesBioTimeEntity;

class ProcessBioTimeTransactions extends ProcessesBioTimeEntity
{
    protected function entity(): string
    {
        return 'transactions';
    }
}
