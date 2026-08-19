<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppPublicidad extends Model
{
    /** @use HasFactory<\Database\Factories\Core\AppPublicidadFactory> */
    use BelongsToSucursal;
    use HasFactory;

    protected $table = 'app_publicidades';

    protected $fillable = [
        'sucursal_id',
        'titulo',
        'imagen',
        'enlace_url',
        'orden',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'orden' => 'integer',
        ];
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activo')->orderBy('orden')->orderBy('id');
    }

    public function imagenUrl(): ?string
    {
        if (! filled($this->imagen)) {
            return null;
        }

        $url = Storage::disk('public')->url($this->imagen);
        $path = str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? (parse_url($url, PHP_URL_PATH) ?: '/storage/'.$this->imagen)
            : $url;

        return rtrim(request()->getSchemeAndHttpHost(), '/').'/'.ltrim((string) $path, '/');
    }
}
