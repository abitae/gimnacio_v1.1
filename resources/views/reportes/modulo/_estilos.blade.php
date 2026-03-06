<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; line-height: 1.35; margin: 0; }
    /* Cabecera del reporte con color (sólido para compatibilidad PDF) */
    .report-header {
        background: #4338ca;
        color: #fff;
        padding: 12px 14px;
        margin: -8px -8px 14px -8px;
    }
    .report-title { font-size: 16px; font-weight: bold; color: #fff; margin: 0 0 4px 0; }
    .report-subtitle { font-size: 10px; color: rgba(255,255,255,0.9); margin: 0; }
    /* Secciones */
    .section-title {
        font-size: 10px; font-weight: bold; color: #4338ca;
        text-transform: uppercase; margin: 0 0 6px 0; padding-bottom: 3px;
        border-bottom: 2px solid #c7d2fe;
    }
    /* Tablas */
    table { border-collapse: collapse; width: 100%; font-size: 8px; }
    th, td { padding: 5px 6px; border: 1px solid #e5e7eb; }
    th {
        background: #e0e7ff;
        font-weight: 600;
        color: #3730a3;
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    /* Cajas de resumen con tinte */
    .resumen-box {
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        padding: 8px 12px;
        margin-bottom: 10px;
        border-radius: 2px;
        color: #3730a3;
    }
    .resumen-box strong { color: #312e81; }
    /* Pie */
    .footer-report {
        margin-top: 14px;
        padding-top: 8px;
        border-top: 1px solid #e5e7eb;
        font-size: 7px;
        color: #9ca3af;
        text-align: center;
    }
</style>
