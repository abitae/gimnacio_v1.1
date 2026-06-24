# Definiciones de deuda (capas alineadas)

## Capas

| Capa | Servicio | Uso |
| --- | --- | --- |
| Resumen operativo | `DailyOperationsDebtService` | Checking, ficha cliente, bandeja POS |
| Transacción | `ClientDebtService`, `ClienteMatriculaService` | Cobros, modales |
| Analítica | `FinanceAnalyticsService` | Reportes solo lectura |

## Deuda total (cliente)

Suma de ítems accionables con `saldo_pendiente > 0`:

- Matrículas contado (`tipo: matricula`)
- Cuotas pendientes/vencidas (`tipo: cuota`)
- Membresía legacy con saldo (`tipo: membresia`)
- Ventas a crédito (`tipo: client_debt` / `client_debt_membership`)

Redondeo: 2 decimales, moneda PEN.

## Exclusiones

- Planes cancelados
- Cuotas pagadas
- Matrículas sin saldo

## Paridad

Mismo `cliente_id` debe reportar el mismo `total_pendiente` en ficha, checking y reporte analítico (tests en `tests/Feature/Consistency/` cuando DB test esté disponible).
