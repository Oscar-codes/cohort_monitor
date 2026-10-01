-- ============================================================
-- Actualización de campos INCAF (incaf_enrolled, incaf_b2b, incaf_b2c)
-- Requiere: migración 020_add_incaf_fields_to_cohorts.sql
-- Generado: 2026-10-01
--
-- 37 códigos recibidos: 31 encontrados (se actualizan por id),
-- 6 NO encontrados en la tabla `cohorts` (se ignoran, no hay
-- UPDATE para ellos):
--   - AIESS7   (fila 13)
--   - AIESS11  (fila 17)
--   - AISCA3   (fila 18)
--   - AIGSK22  (fila 19)
--   - AIESS14  (fila 20)
--   - AISCA5   (fila 21)
--
-- "fila" = posición del código en la lista original (1-37).
-- NO EJECUTAR sin revisión previa.
-- ============================================================

USE kodigo;

START TRANSACTION;

-- fila=1 cohort_code=FSJ34
UPDATE cohorts SET incaf_enrolled = 40, incaf_b2b = 0, incaf_b2c = 40 WHERE id = 19; -- cohort_code = FSJ34

-- fila=2 cohort_code=FSJ35
UPDATE cohorts SET incaf_enrolled = 40, incaf_b2b = 40, incaf_b2c = 0 WHERE id = 138; -- cohort_code = FSJ35

-- fila=3 cohort_code=FSJ36
UPDATE cohorts SET incaf_enrolled = 40, incaf_b2b = 20, incaf_b2c = 20 WHERE id = 139; -- cohort_code = FSJ36

-- fila=4 cohort_code=PY4
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 0, incaf_b2c = 30 WHERE id = 17; -- cohort_code = PY4

-- fila=5 cohort_code=PY5
UPDATE cohorts SET incaf_enrolled = 29, incaf_b2b = 0, incaf_b2c = 29 WHERE id = 16; -- cohort_code = PY5

-- fila=6 cohort_code=PY6
UPDATE cohorts SET incaf_enrolled = 29, incaf_b2b = 29, incaf_b2c = 0 WHERE id = 284; -- cohort_code = PY6

-- fila=7 cohort_code=BIANL1
UPDATE cohorts SET incaf_enrolled = 42, incaf_b2b = 21, incaf_b2c = 21 WHERE id = 77; -- cohort_code = BIANL1

-- fila=8 cohort_code=BIANL2
UPDATE cohorts SET incaf_enrolled = 43, incaf_b2b = 21, incaf_b2c = 22 WHERE id = 333; -- cohort_code = BIANL2

-- fila=9 cohort_code=SQL5
UPDATE cohorts SET incaf_enrolled = 35, incaf_b2b = 35, incaf_b2c = 0 WHERE id = 194; -- cohort_code = SQL5

-- fila=10 cohort_code=SQL6
UPDATE cohorts SET incaf_enrolled = 35, incaf_b2b = 0, incaf_b2c = 35 WHERE id = 21; -- cohort_code = SQL6

-- fila=11 cohort_code=SQL7
UPDATE cohorts SET incaf_enrolled = 15, incaf_b2b = 8, incaf_b2c = 7 WHERE id = 354; -- cohort_code = SQL7

-- fila=12 cohort_code=AIGSK16
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 18, incaf_b2c = 12 WHERE id = 35; -- cohort_code = AIGSK16

-- fila=14 cohort_code=AIGSK19
UPDATE cohorts SET incaf_enrolled = 31, incaf_b2b = 18, incaf_b2c = 13 WHERE id = 377; -- cohort_code = AIGSK19

-- fila=15 cohort_code=AIESS9
UPDATE cohorts SET incaf_enrolled = 31, incaf_b2b = 18, incaf_b2c = 13 WHERE id = 383; -- cohort_code = AIESS9

-- fila=16 cohort_code=AIGTA3
UPDATE cohorts SET incaf_enrolled = 31, incaf_b2b = 18, incaf_b2c = 13 WHERE id = 390; -- cohort_code = AIGTA3

-- fila=22 cohort_code=AIMLF1
UPDATE cohorts SET incaf_enrolled = 29, incaf_b2b = 20, incaf_b2c = 9 WHERE id = 268; -- cohort_code = AIMLF1

-- fila=23 cohort_code=AIMLF2
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 21, incaf_b2c = 9 WHERE id = 317; -- cohort_code = AIMLF2

-- fila=24 cohort_code=DATTR1
UPDATE cohorts SET incaf_enrolled = 40, incaf_b2b = 22, incaf_b2c = 18 WHERE id = 23; -- cohort_code = DATTR1

-- fila=25 cohort_code=DATTR2
UPDATE cohorts SET incaf_enrolled = 40, incaf_b2b = 18, incaf_b2c = 22 WHERE id = 273; -- cohort_code = DATTR2

-- fila=26 cohort_code=DATTR3
UPDATE cohorts SET incaf_enrolled = 41, incaf_b2b = 12, incaf_b2c = 29 WHERE id = 287; -- cohort_code = DATTR3

-- fila=27 cohort_code=DATTR4
UPDATE cohorts SET incaf_enrolled = 28, incaf_b2b = 16, incaf_b2c = 12 WHERE id = 358; -- cohort_code = DATTR4

-- fila=28 cohort_code=WDFRT2
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 15, incaf_b2c = 15 WHERE id = 304; -- cohort_code = WDFRT2

-- fila=29 cohort_code=WDFRT3
UPDATE cohorts SET incaf_enrolled = 40, incaf_b2b = 20, incaf_b2c = 20 WHERE id = 360; -- cohort_code = WDFRT3

-- fila=30 cohort_code=AITCH4
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 26, incaf_b2c = 4 WHERE id = 25; -- cohort_code = AITCH4

-- fila=31 cohort_code=AITCH5
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 24, incaf_b2c = 6 WHERE id = 288; -- cohort_code = AITCH5

-- fila=32 cohort_code=AITCH6
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 24, incaf_b2c = 6 WHERE id = 297; -- cohort_code = AITCH6

-- fila=33 cohort_code=AIDJR1
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 0, incaf_b2c = 30 WHERE id = 24; -- cohort_code = AIDJR1

-- fila=34 cohort_code=AIDJR2
UPDATE cohorts SET incaf_enrolled = 30, incaf_b2b = 0, incaf_b2c = 30 WHERE id = 263; -- cohort_code = AIDJR2

-- fila=35 cohort_code=AIDJR3
UPDATE cohorts SET incaf_enrolled = 34, incaf_b2b = 17, incaf_b2c = 17 WHERE id = 365; -- cohort_code = AIDJR3

-- fila=36 cohort_code=AIDJR4
UPDATE cohorts SET incaf_enrolled = 35, incaf_b2b = 17, incaf_b2c = 18 WHERE id = 367; -- cohort_code = AIDJR4

-- fila=37 cohort_code=AIDJR5
UPDATE cohorts SET incaf_enrolled = 35, incaf_b2b = 17, incaf_b2c = 18 WHERE id = 368; -- cohort_code = AIDJR5

-- Verificación antes de confirmar (debe devolver 31 filas, y
-- incaf_enrolled = incaf_b2b + incaf_b2c en todas):
-- SELECT id, cohort_code, incaf_enrolled, incaf_b2b, incaf_b2c,
--        (incaf_enrolled = incaf_b2b + incaf_b2c) AS suma_ok
-- FROM cohorts
-- WHERE id IN (19,138,139,17,16,284,77,333,194,21,354,35,377,383,390,
--              268,317,23,273,287,358,304,360,25,288,297,24,263,365,367,368)
-- ORDER BY id;

COMMIT;
