<?php

use Database\Seeders\DatabaseSeeder;

it('runs default DatabaseSeeder without requiring bundled sql files', function () {
    $this->seed(DatabaseSeeder::class);

    expect(true)->toBeTrue();
});
