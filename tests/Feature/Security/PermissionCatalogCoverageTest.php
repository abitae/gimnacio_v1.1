<?php

use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\File;

it('covers every permission string referenced by routes, authorization checks and views', function () {
    $patterns = [
        '/permission:([a-z0-9_\\-\\.]+)/i',
        "/authorize\\('([a-z0-9_\\-\\.]+)'/i",
        "/@can\\('([a-z0-9_\\-\\.]+)'/i",
    ];

    $files = collect([
        ...collect(File::files(base_path('routes')))->map->getPathname()->all(),
        ...collect(File::allFiles(base_path('app')))->map->getPathname()->all(),
        ...collect(File::allFiles(base_path('resources/views')))->map->getPathname()->all(),
    ])->filter(fn ($path) => is_string($path) && is_file($path))->values();

    $usedPermissions = $files
        ->flatMap(function (string $path) use ($patterns) {
            $contents = file_get_contents($path) ?: '';
            $matches = [];

            foreach ($patterns as $pattern) {
                preg_match_all($pattern, $contents, $found);
                foreach ($found[1] ?? [] as $permission) {
                    $matches[] = $permission;
                }
            }

            return $matches;
        })
        ->unique()
        ->sort()
        ->values();

    $catalogPermissions = collect(PermissionCatalog::permissionNames())
        ->unique()
        ->sort()
        ->values();

    expect($usedPermissions->diff($catalogPermissions)->values()->all())->toBe([]);
});
