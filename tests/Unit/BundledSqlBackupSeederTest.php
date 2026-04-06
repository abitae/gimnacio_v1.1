<?php

use Database\Seeders\BundledSqlBackupSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('throws when database driver is not mysql', function () {
    $connection = Mockery::mock(\Illuminate\Database\Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('sqlite');

    DB::shouldReceive('connection')->withNoArgs()->andReturn($connection);

    $seeder = new BundledSqlBackupSeeder;
    $seeder->setContainer(app());

    expect(fn () => $seeder->run())->toThrow(RuntimeException::class);
});
