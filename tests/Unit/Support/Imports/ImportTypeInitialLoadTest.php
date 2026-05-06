<?php

use App\Support\Imports\ImportType;

it('includes cuotas in implemented initial load types', function () {
    expect(ImportType::implemented())->toContain(ImportType::CUOTAS)
        ->and(ImportType::labels())->toHaveKey(ImportType::CUOTAS);
});
