<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class RawExcelArrayImport implements ToArray
{
    public function array(array $array): void
    {
    }
}
