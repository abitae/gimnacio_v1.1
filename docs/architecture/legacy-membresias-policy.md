# Política legacy: cliente_membresias

## Reglas

1. **Lectura:** permitida vía `LegacyMembresiaReadService` (ficha, reportes, cobranza controlada).
2. **Escritura nuevas altas:** prohibida desde UI; usar `cliente_matriculas`.
3. **UI:** badge «Legacy» en filas `ClienteMembresia`.
4. **Cobranza:** solo servicios autorizados (`ClienteMembresiaService`, operaciones POS).
5. **Sunset:** migración masiva opcional vía comando super admin (futuro).

## Fuente de verdad comercial activa

`cliente_matriculas` + `enrollment_installments`
