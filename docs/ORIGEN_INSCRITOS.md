# Origen de los inscritos (DATA-002)

> Según el código actual. Pendiente de confirmar si existe una fuente externa de inscripciones; ajustar este documento cuando se confirme.

Los inscritos B2B y B2C se capturan manualmente en Cohort Monitor. Inscritos B2B los registra Admisiones B2B; Inscritos B2C, Admisiones B2C (o por importación de Excel). Administración puede corregir ambos. Los campos INCAF son el dato reportado por INCAF y no reemplazan a estos.

## Detalle

| Dato | Columna | Dónde se captura | Quién puede editarlo |
|---|---|---|---|
| Inscritos B2B | `cohorts.b2b_admissions` | Formulario de creación y de edición | Administración; Admisiones B2B solo en cohortes con meta B2B mayor que 0 |
| Inscritos B2C | `cohorts.b2c_admissions` | Formulario de creación y de edición; importación de Excel (columna `inscritos_b2c`) | Administración; Admisiones B2C solo en cohortes con meta B2C mayor que 0 |
| INCAF inscritos / B2B / B2C | `cohorts.incaf_enrolled`, `incaf_b2b`, `incaf_b2c` | Formulario de edición | Administración. Vacío significa «sin dato», distinto de 0 |

- La importación de Excel no carga inscritos B2B.
- La aplicación no tiene integración ni sincronización con otra fuente de inscripciones.
- Fuera de la aplicación, los scripts SQL de `scripts/data/` (generados a partir de `cohortes_2026.csv`) han actualizado inscritos directamente en la base. Esos cambios no pasan por las validaciones ni por la auditoría de la aplicación.
- Los permisos por rol están en `App\Core\Auth::COHORT_EDITABLE_FIELDS`.
