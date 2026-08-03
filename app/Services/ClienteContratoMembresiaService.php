<?php

namespace App\Services;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\Pago;
use App\Services\WhatsApp\WhatsAppServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

class ClienteContratoMembresiaService
{
    /** @var list<string> */
    private const SEDES_CONTRATO = ['Ayacucho', 'Cajamarca', 'Chilca'];

    public const NOMBRE_GIMNASIO_CONTRATO = 'GIMNASIO FITNESS CENTER';

    public function __construct(
        protected WhatsAppServiceInterface $whatsAppService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function datosContrato(Cliente $cliente): array
    {
        $cliente->loadMissing(['sucursal', 'registroPor']);

        $matricula = $this->matriculaReferencia($cliente);
        if ($matricula) {
            $matricula->loadMissing(['membresia', 'clase', 'asesor', 'sucursal', 'pagos', 'enrollmentInstallments']);
        }

        $tipoMembresia = $this->resolverTipoMembresia($matricula);
        $sedeNombre = $this->resolverSede($matricula, $cliente);
        $montoPagado = $this->resolverMontoPagado($matricula);
        $formaPago = $this->resolverFormaPago($matricula);
        $fechasFraccionado = $this->resolverFechasFraccionado($matricula);

        $fechaNacimiento = $cliente->fecha_nacimiento instanceof Carbon
            ? $cliente->fecha_nacimiento
            : ($cliente->fecha_nacimiento ? Carbon::parse($cliente->fecha_nacimiento) : null);

        $fechaInicio = $matricula?->fecha_inicio;
        $fechaFin = $matricula?->fecha_fin;

        $asesorNombre = $matricula?->asesor?->name
            ?? $cliente->registroPor?->name
            ?? '';

        $ciudadFirma = $this->resolverCiudadFirma($matricula, $cliente);

        return [
            'gimnasio_nombre' => self::NOMBRE_GIMNASIO_CONTRATO,
            'gimnasio_nombre_titulo' => self::NOMBRE_GIMNASIO_CONTRATO,
            'afiliado_nombre' => trim($cliente->nombres.' '.$cliente->apellidos),
            'afiliado_dni' => $cliente->numero_documento ?? '',
            'afiliado_celular' => $cliente->telefono ?? '',
            'afiliado_fecha_nacimiento' => $fechaNacimiento?->format('d / m / Y'),
            'afiliado_direccion' => trim((string) ($cliente->direccion ?? '')),
            'afiliado_codigo' => trim((string) ($cliente->codigo ?? '')),
            'asesor_nombre' => trim($asesorNombre),
            'tipo_membresia' => $tipoMembresia,
            'tipo_membresia_otro' => $tipoMembresia['otro'] ?? ($matricula?->membresia?->nombre ?? ''),
            'sedes' => collect(self::SEDES_CONTRATO)->mapWithKeys(fn (string $sede) => [
                $sede => Str::contains(Str::lower($sedeNombre), Str::lower($sede)),
            ])->all(),
            'fecha_inicio' => $fechaInicio?->format('d / m / Y'),
            'fecha_termino' => $fechaFin?->format('d / m / Y'),
            'monto_pagado' => $montoPagado > 0 ? number_format($montoPagado, 2) : null,
            'forma_pago' => $formaPago !== '' ? $formaPago : null,
            'fechas_pago_fraccionado' => $fechasFraccionado !== '' ? $fechasFraccionado : null,
            'ciudad_firma' => $ciudadFirma,
            'fecha_firma_dia' => now()->format('d'),
            'fecha_firma_mes' => now()->translatedFormat('F'),
            'fecha_firma_anio' => now()->format('y'),
            'matricula_nombre' => $matricula?->membresia?->nombre ?? $matricula?->clase?->nombre ?? '',
        ];
    }

    protected function matriculaReferencia(Cliente $cliente): ?ClienteMatricula
    {
        $membresia = $cliente->clienteMatriculas()
            ->where('tipo', 'membresia')
            ->orderByRaw("CASE WHEN estado = 'activa' THEN 0 ELSE 1 END")
            ->orderByDesc('fecha_matricula')
            ->first();

        if ($membresia) {
            return $membresia;
        }

        return $cliente->clienteMatriculas()
            ->orderByDesc('fecha_matricula')
            ->first();
    }

    /**
     * @return array{mensual: bool, trimestral: bool, semestral: bool, anual: bool, otro: bool, otro_texto: string}
     */
    protected function resolverTipoMembresia(?ClienteMatricula $matricula): array
    {
        $base = [
            'mensual' => false,
            'trimestral' => false,
            'semestral' => false,
            'anual' => false,
            'otro' => false,
            'otro_texto' => '',
        ];

        if (! $matricula) {
            return $base;
        }

        $nombre = Str::lower((string) ($matricula->membresia?->nombre ?? ''));
        $dias = (int) ($matricula->membresia?->duracion_dias ?? 0);

        if (Str::contains($nombre, 'mensual') || ($dias > 0 && $dias <= 35)) {
            $base['mensual'] = true;

            return $base;
        }

        if (Str::contains($nombre, 'trimestral') || ($dias > 35 && $dias <= 100)) {
            $base['trimestral'] = true;

            return $base;
        }

        if (Str::contains($nombre, 'semestral') || ($dias > 100 && $dias <= 200)) {
            $base['semestral'] = true;

            return $base;
        }

        if (Str::contains($nombre, 'anual') || $dias > 200) {
            $base['anual'] = true;

            return $base;
        }

        $base['otro'] = true;
        $base['otro_texto'] = $matricula->membresia?->nombre ?? 'Plan contratado';

        return $base;
    }

    protected function resolverSede(?ClienteMatricula $matricula, Cliente $cliente): string
    {
        return trim((string) (
            $matricula?->sucursal?->nombre
            ?? $cliente->sucursal?->nombre
            ?? ''
        ));
    }

    protected function resolverMontoPagado(?ClienteMatricula $matricula): float
    {
        if (! $matricula) {
            return 0.0;
        }

        $pagado = (float) $matricula->pagos->sum(fn (Pago $pago) => (float) $pago->monto);

        if ($pagado > 0) {
            return round($pagado, 2);
        }

        return round((float) ($matricula->precio_final ?? 0), 2);
    }

    protected function resolverFormaPago(?ClienteMatricula $matricula): string
    {
        if (! $matricula) {
            return '';
        }

        return match ($matricula->modalidad_pago) {
            'cuotas' => 'Pago fraccionado / cuotas',
            'contado' => 'Contado',
            default => ucfirst((string) $matricula->modalidad_pago),
        };
    }

    protected function resolverFechasFraccionado(?ClienteMatricula $matricula): string
    {
        if (! $matricula || $matricula->modalidad_pago !== 'cuotas') {
            return '';
        }

        $fechas = $matricula->enrollmentInstallments
            ->map(function (EnrollmentInstallment $cuota) {
                $fecha = $cuota->fecha_vencimiento ?? $cuota->fecha_pago;

                return $fecha ? Carbon::parse($fecha)->format('d/m/Y') : null;
            })
            ->filter()
            ->values();

        if ($fechas->isEmpty()) {
            return 'Según cronograma acordado en recepción';
        }

        return $fechas->implode(' · ');
    }

    protected function resolverCiudadFirma(?ClienteMatricula $matricula, Cliente $cliente): string
    {
        $sede = $this->resolverSede($matricula, $cliente);

        foreach (self::SEDES_CONTRATO as $nombreSede) {
            if (Str::contains(Str::lower($sede), Str::lower($nombreSede))) {
                return $nombreSede === 'Chilca' ? 'Chilca' : 'Huancayo';
            }
        }

        return 'Huancayo';
    }

    public function generarPdf(Cliente $cliente): string
    {
        $contrato = $this->datosContrato($cliente);
        $html = view('clientes.contrato-membresia-pdf', compact('contrato'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 14,
            'margin_bottom' => 14,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function nombreArchivoPdf(Cliente $cliente): string
    {
        return 'contrato-membresia-'.($cliente->codigo ?: $cliente->id).'.pdf';
    }

    public function respuestaPreview(Cliente $cliente): Response
    {
        $pdf = $this->generarPdf($cliente);
        $filename = $this->nombreArchivoPdf($cliente);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function respuestaDescarga(Cliente $cliente): Response
    {
        $pdf = $this->generarPdf($cliente);
        $filename = $this->nombreArchivoPdf($cliente);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * URL firmada temporal (48 h) para descargar el contrato (envío por WhatsApp, etc.).
     */
    public function getUrlDescargaFirmada(Cliente $cliente): string
    {
        return URL::temporarySignedRoute(
            'clientes.contrato-membresia.descargar.signed',
            now()->addHours(48),
            ['cliente' => $cliente->id]
        );
    }

    public function mensajeWhatsAppContrato(Cliente $cliente): string
    {
        $nombre = trim($cliente->nombres.' '.$cliente->apellidos);
        $urlDescarga = $this->getUrlDescargaFirmada($cliente);

        return 'Hola'.($nombre !== '' ? ' '.$nombre : '').', te enviamos tu contrato de membresía. Descárgalo aquí: '.$urlDescarga;
    }

    /**
     * @return array{ success: bool, message: string }
     */
    public function enviarContratoPorWhatsApp(Cliente $cliente): array
    {
        $telefono = $cliente->telefono ?? '';

        if (empty(trim((string) $telefono))) {
            return ['success' => false, 'message' => 'El cliente no tiene teléfono registrado. Añade un número en la ficha del cliente para poder enviar por WhatsApp.'];
        }

        $destino = trim($telefono);
        if (! str_starts_with($destino, '+')) {
            $destino = preg_replace('/^0/', '', $destino);
            $destino = (str_starts_with($destino, '51') ? '+' : '+51').$destino;
        }

        $pdfBase64 = base64_encode($this->generarPdf($cliente));
        $nombreArchivo = $this->nombreArchivoPdf($cliente);
        $caption = $this->mensajeWhatsAppContrato($cliente);

        $result = $this->whatsAppService->enviarDocumento($destino, $pdfBase64, $nombreArchivo, $caption);

        if ($result['success']) {
            return ['success' => true, 'message' => 'Contrato enviado por WhatsApp.'];
        }

        return ['success' => false, 'message' => $result['error'] ?? 'No se pudo enviar por la API de WhatsApp. Se abrió el chat para que envíes el enlace manualmente.'];
    }
}
