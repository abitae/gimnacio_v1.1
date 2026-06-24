# Mapeo legacy Excel → modelo nuevo

## Clientes

| Excel (típico) | Modelo | Notas |
| --- | --- | --- |
| DNI / documento | `clientes.numero_documento` | Clave natural |
| Código socio | `clientes.codigo` | Idempotencia import |
| Nombres / apellidos | `nombres`, `apellidos` | |

## Membresía legacy → matrícula

| `cliente_membresias` | `cliente_matriculas` |
| --- | --- |
| `membresia_id` | `membresia_id`, `tipo=membresia` |
| `fecha_inicio/fin` | mismas fechas |
| `estado` | mapeo activa/vencida/congelada |
| Pagos pendientes | `pagos` + saldo calculado |

## Deudas

Import legacy deudas → `client_debts` o saldo en matrícula según processor (`LegacyDebtImportService`, `MatriculaDebtReconciler`).

## Origen de datos

Nuevos registros por import deben etiquetarse `DataOrigin::Import` cuando existan columnas metadata (plan plataforma).
