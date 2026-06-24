<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('checking.ver', 'web');
});

it('denies checking route without permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('checking.index'))->assertForbidden();
});

it('allows checking route with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('checking.ver');
    $this->actingAs($user);

    $this->get(route('checking.index'))->assertOk();
});
