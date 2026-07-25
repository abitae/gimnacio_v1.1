<?php

it('only allows documented withoutGlobalScope bypasses in application code', function () {
    $allowlist = [
        'app/Services/ClienteCrossSucursalAlertService.php',
        'app/Support/SucursalScope.php',
        'app/Services/AsistenciaService.php',
        'app/Services/ProductoService.php',
        'app/Services/ClaseService.php',
        // BioTime recibe una sucursal autenticada por token y vuelve a aplicar
        // where(sucursal_id) explícito para impedir colisiones entre instalaciones.
        'app/Models/BioTime/BioTimeEmployee.php',
        'app/Services/BioTime/BioTimeSyncService.php',
    ];

    $violations = [];
    $basePath = dirname(__DIR__, 3);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basePath.'/app', FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($basePath) + 1));

        if (str_contains($relative, '/Concerns/BelongsToSucursal.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname()) ?: '';

        if (! preg_match('/withoutGlobalScope(s)?\(/', $contents)) {
            continue;
        }

        if (! in_array($relative, $allowlist, true)) {
            $violations[] = $relative;
        }
    }

    expect($violations)->toBeEmpty('Bypasses no documentados: '.implode(', ', $violations));
});
