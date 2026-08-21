<?php

namespace App\Services;

use Mpdf\Mpdf;

class ReporteModuloPdfService
{
    protected function configMpdf(string $orientation = 'P'): array
    {
        return [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => $orientation,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
        ];
    }

    protected function configTicketMpdf(): array
    {
        return [
            'mode' => 'utf-8',
            'format' => [80, 220],
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 4,
            'margin_bottom' => 6,
        ];
    }

    public function generarPdfVentas(array $data): string
    {
        $html = view('reportes.modulo.pdf-ventas', $data)->render();
        $mpdf = new Mpdf($this->configMpdf('L'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generarPdfMatriculas(array $data): string
    {
        $html = view('reportes.modulo.pdf-matriculas', $data)->render();
        $mpdf = new Mpdf($this->configMpdf('L'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generarPdfFinanciero(array $data): string
    {
        $html = view('reportes.modulo.pdf-financiero', $data)->render();
        $mpdf = new Mpdf($this->configMpdf('L'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generarPdfClientes(array $data): string
    {
        $html = view('reportes.modulo.pdf-clientes', $data)->render();
        $mpdf = new Mpdf($this->configMpdf('L'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generarPdfClientesMembresiaClases(array $data): string
    {
        $html = view('reportes.modulo.pdf-clientes-membresia-clases', $data)->render();
        $mpdf = new Mpdf($this->configMpdf('L'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generarPdfUsuarios(array $data): string
    {
        $html = view('reportes.modulo.pdf-usuarios', $data)->render();
        $mpdf = new Mpdf($this->configMpdf());
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generarPdfCajas(array $data): string
    {
        $formato = (string) ($data['formato'] ?? 'reporte');
        $vista = match ($formato) {
            'ticket' => 'reportes.modulo.pdf-cajas-ticket',
            'matriz' => 'reportes.modulo.pdf-cajas-matriz',
            default => 'reportes.modulo.pdf-cajas',
        };
        $html = view($vista, $data)->render();
        $mpdf = new Mpdf($formato === 'ticket' ? $this->configTicketMpdf() : $this->configMpdf('L'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generarPdfProductosServicios(array $data): string
    {
        $html = view('reportes.modulo.pdf-productos-servicios', $data)->render();
        $mpdf = new Mpdf($this->configMpdf('L'));
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function generarPdfGimnasio(array $data): string
    {
        $html = view('reportes.modulo.pdf-gimnasio', $data)->render();
        $mpdf = new Mpdf($this->configMpdf());
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
