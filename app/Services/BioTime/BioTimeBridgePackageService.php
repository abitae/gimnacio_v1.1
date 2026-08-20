<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use Illuminate\Support\Str;
use ZipArchive;

class BioTimeBridgePackageService
{
    public function exePath(): string
    {
        return (string) config('biotime.bridge_exe_path');
    }

    public function configTemplatePath(): string
    {
        return (string) config('biotime.bridge_config_path');
    }

    public function exeExists(): bool
    {
        $path = $this->exePath();

        return is_file($path) && is_readable($path);
    }

    public function configTemplateExists(): bool
    {
        $path = $this->configTemplatePath();

        return is_file($path) && is_readable($path);
    }

    public function isAvailable(): bool
    {
        return $this->exeExists() && $this->configTemplateExists();
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function buildZip(): array
    {
        if (! $this->exeExists()) {
            throw new \RuntimeException('No se encontró BioTimeBridge.exe. Compílalo con tools/biotime-bridge/scripts/build-exe.bat.');
        }

        if (! $this->configTemplateExists()) {
            throw new \RuntimeException('No se encontró la plantilla config.yaml del puente BioTime.');
        }

        $zipPath = storage_path('app/tmp/biotime-bridge-'.Str::uuid().'.zip');
        $dir = dirname($zipPath);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio temporal para el ZIP del puente.');
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el ZIP del puente BioTime.');
        }

        $zip->addFile($this->exePath(), 'BioTimeBridge.exe');
        $zip->addFromString('config.yaml', $this->configYamlForDownload());
        $zip->addFromString(
            'LEEME.txt',
            "1. Extrae TODO el ZIP en una carpeta local (ej. C:\\BioTimeBridge\\).\r\n".
            "   BioTimeBridge.exe y config.yaml deben quedar en la MISMA carpeta.\r\n".
            "2. En Laravel → BioTime → Sedes, regenera/copia el token de esta sede.\r\n".
            "3. Pega el token en laravel_token de config.yaml y ajusta biotime_base_url si hace falta.\r\n".
            "4. Doble clic en BioTimeBridge.exe → se abre la ventana del puente.\r\n".
            "   Si no abre, ejecuta desde cmd: BioTimeBridge.exe gui\r\n"
        );
        $zip->addFromString(
            'start-gui.bat',
            "@echo off\r\n".
            "cd /d \"%~dp0\"\r\n".
            "start \"\" \"%~dp0BioTimeBridge.exe\"\r\n"
        );
        $zip->close();

        return [
            'path' => $zipPath,
            'filename' => 'BioTimeBridge.zip',
        ];
    }

    public function configYamlForDownload(): string
    {
        $contents = (string) file_get_contents($this->configTemplatePath());
        $baseUrl = rtrim((string) config('app.url'), '/');

        return (string) preg_replace(
            '/^laravel_base_url:\s*.+$/m',
            'laravel_base_url: "'.$this->yamlDoubleQuoted($baseUrl).'"',
            $contents,
            1
        );
    }

    protected function yamlDoubleQuoted(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
