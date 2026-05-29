<?php

declare(strict_types=1);

namespace App\Jobs\BioTime;

use App\Jobs\BioTime\Concerns\ProcessesBioTimeEntity;

class ProcessBioTimeDepartments extends ProcessesBioTimeEntity
{
    protected function entity(): string
    {
        return 'departments';
    }
}
