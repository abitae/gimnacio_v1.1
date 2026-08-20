<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use Illuminate\Support\Str;
use ZipArchive;

class BioTimeBridgePackageService
{
    public function resolveExePath(): ?string
    {
        return $this->resolveFirstExistingPath($this->exeCandidates());
    }

    public function resolveConfigTemplatePath(): ?string
    {
        return $this->resolveFirstExistingPath($this->configTemplateCandidates());
    }

    public function exePath(): string
    {
        return $this->resolveExePath() ?? (string) config('biotime.bridge_exe_path');
    }

    public function configTemplatePath(): string
    {
        return $this->resolveConfigTemplatePath() ?? (string) config('biotime.bridge_config_path');
    }

    public function exeExists(): bool
    {
        return $this->resolveExePath() !== null;
    }

    public function configTemplateExists(): bool
    {
        return $this->resolveConfigTemplatePath() !== null;
    }

    public function isAvailable(): bool
    {
        return $this->exeExists() && $this->configTemplateExists();
    }

    /**
     * @return list<string>
     */
    public function missingComponents(): array
    {
        $missing = [];

        if (! $this->exeExists()) {
            $missing[] = 'public/dist/dist/BioTimeBridge.exe';
        }

        if (! $this->configTemplateExists()) {
            $missing[] = 'public/dist/dist/config.yaml.example';
        }

        return $missing;
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function buildZip(): array
    {
        $exePath = $this->resolveExePath();
        if ($exePath === null) {
            throw new \RuntimeException('No se encontró BioTimeBridge.exe en public/dist/dist/.');
        }

        $configPath = $this->resolveConfigTemplatePath();
        if ($configPath === null) {
            throw new \RuntimeException('No se encontró public/dist/dist/config.yaml.example.');
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

        $zip->addFile($exePath, 'BioTimeBridge.exe');
        $zip->addFromString('config.yaml', $this->configYamlForDownload($configPath));
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

    public function configYamlForDownload(?string $templatePath = null): string
    {
        $path = $templatePath ?? $this->resolveConfigTemplatePath();
        if ($path === null) {
            throw new \RuntimeException('No se encontró la plantilla config.yaml del puente BioTime.');
        }

        $contents = (string) file_get_contents($path);
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

    /**
     * @return list<string>
     */
    protected function exeCandidates(): array
    {
        $paths = [];

        $configured = config('biotime.bridge_exe_path');
        if (is_string($configured) && $configured !== '') {
            $paths[] = $configured;
        }

        if (! $this->hasExplicitEnv('BIOTIME_BRIDGE_EXE_PATH')) {
            $paths = array_merge($paths, config('biotime.bridge_exe_fallback_paths', []));
        }

        return $this->uniquePaths($paths);
    }

    /**
     * @return list<string>
     */
    protected function configTemplateCandidates(): array
    {
        $paths = [];

        $configured = config('biotime.bridge_config_path');
        if (is_string($configured) && $configured !== '') {
            $paths[] = $configured;
        }

        if (! $this->hasExplicitEnv('BIOTIME_BRIDGE_CONFIG_PATH')) {
            $paths = array_merge($paths, config('biotime.bridge_config_fallback_paths', []));
        }

        return $this->uniquePaths($paths);
    }

    /**
     * @param  list<string>  $paths
     */
    protected function resolveFirstExistingPath(array $paths): ?string
    {
        foreach ($paths as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    protected function uniquePaths(array $paths): array
    {
        $unique = [];

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (! in_array($normalized, $unique, true)) {
                $unique[] = $normalized;
            }
        }

        return $unique;
    }

    protected function hasExplicitEnv(string $key): bool
    {
        $value = env($key);

        return is_string($value) && $value !== '';
    }
}
