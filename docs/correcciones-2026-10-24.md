# Correcciones de la auditoría de EdiConnect

Las 11 fallas del informe inicial quedaron corregidas en el código local y la instancia habitual de Docker. También se corrigió la navegación del mural administrativo.

| Hallazgo inicial | Corrección | Evidencia de regresión |
|---|---|---|
| F01: lectura pública de cobros | Autenticación Sanctum, rol Residente y consulta limitada a unidades propias | API-01, API-05, API-06 |
| F02: comprobantes anónimos/identidad fija | Token obligatorio, asociación al gasto y autor tomado del usuario autenticado | API-02, API-07, API-11 |
| F03: cargas API en gastos pagados | Regla compartida por web/API; cobros pagados o en revisión rechazan cargas | API-03, API-12 |
| F04: encomiendas anónimas | Token y rol Conserje; autoría persistida | API-04, API-08, API-09, API-10 |
| F05: aprobación de importes insuficientes | Se exige coincidencia exacta; aprobación inválida no modifica comprobante ni cobro | PAGO-01, PAGO-04 |
| F06: monto máximo causa error 500 | Migración amplía el monto de comprobantes a BIGINT UNSIGNED | PAGO-02 |
| F07: contacto vinculado a residente ajeno | La validación exige asociación al mismo departamento | DEPTO-01, DEPTO-03 |
| F08: teléfono sin dígitos | Entre 7 y 15 dígitos reales, con formato opcional | DEPTO-02, DEPTO-03 |
| F09: búsquedas malformadas causan 500 | Validación de tipos, longitud y filtros antes de llamar trim o consultar | Dos casos FILTRO de avisos/mural |
| F10: contraseñas inconsistentes | Mínimo de 12 caracteres y máximo de 72 bytes sin NUL en creación, cambio y recuperación; claves generadas de 16 caracteres | AUTH-01, AUTH-LIMITE, suites HU-03 y password |
| F11: tokens conservados al recuperar | Cambio de clave, rotación de remember_token, revocación de tokens y consumo del enlace dentro de una transacción | AUTH-02 a AUTH-06 |
| V01: mural con cabecera anterior | Listado y edición de avisos usan layouts.app y el menú por rol | Suite mural/incidencias y revisión visual |

La carga de comprobantes ahora se ejecuta mediante `RegistrarComprobante`, con bloqueo del gasto antes de consultar el comprobante en revisión. Si falla el guardado en base de datos, se elimina el archivo recién creado. La aprobación usa el mismo orden de bloqueo. La interfaz deshabilita aprobar importes que no coinciden y explica que deben rechazarse para corrección.

## Validación final

- **28 suites funcionales aprobadas: 1.142 comprobaciones PASS.**
- **39 verificaciones de auditoría aprobadas, sin fallos.**
- Se probó tanto el rechazo de accesos inválidos como la operación permitida: consulta propia, carga autenticada, descarga privada y registro de encomienda por conserje.
- La revocación de tokens se comprobó mediante una llamada posterior a la API: el token anterior al restablecimiento devuelve HTTP 401.
- La recuperación rechaza contraseñas cortas, entradas de más de 72 bytes y caracteres nulos; acepta una contraseña válida y no permite reutilizar el enlace.
- La edición de contactos del perfil también se volvió a verificar después de corregir una regresión detectada durante la extracción de la regla de contraseña.
- La migración `2026_10_24_000020_ampliar_monto_comprobantes_pago` está aplicada en la instancia habitual, conservando los registros existentes.

Los tests que antes llamaban endpoints públicos se actualizaron para autenticarse y comprobar las nuevas restricciones; la prueba antigua que esperaba un segundo comprobante en revisión ahora exige su rechazo.

Evidencia de la ejecución:

- [Resumen de suites](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/qa-correcciones-2026-10-24/suites.json)
- [Auditoría con resultados individuales](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/qa-correcciones-2026-10-24/auditoria.json)

Para repetir la auditoría, usa la instancia aislada, nunca datos reales:

```powershell
docker compose -p ediconnect-qa -f docker-compose.yml -f tests/compose.qa.yml up -d --build
# Esperar que app y db estén healthy y web esté iniciado.
docker compose -p ediconnect-qa -f docker-compose.yml -f tests/compose.qa.yml exec -T -e QA_AUDIT=1 app php tests/docker-auditoria-integral.php
```

## Uso de la API y política de contraseña

Los clientes API deben obtener un token con `POST /api/v1/auth/login`, enviando `email` y `password`, y usar `Authorization: Bearer <token>`. Las respuestas de API son JSON incluso si el cliente no envía Accept. Los comprobantes y la consulta de gastos requieren Residente; las encomiendas requieren Conserje. Las cuentas inactivas no acceden.

La política nueva se aplica a contraseñas nuevas. No se modificaron las contraseñas existentes de los usuarios. La entrega real de correo de recuperación continúa fuera del alcance de estas correcciones: el flujo local sigue mostrando el enlace de simulación.

Estos resultados verifican los escenarios ensayados; no sustituyen pruebas de carga ni una auditoría exhaustiva de producción.
