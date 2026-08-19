<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Core\AppPublicidad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AppPublicidad
 */
class PublicidadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'imagen_url' => $this->imagenUrl(),
            'enlace_url' => $this->enlace_url,
        ];
    }
}
