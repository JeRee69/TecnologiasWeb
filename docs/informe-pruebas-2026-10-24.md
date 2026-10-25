# Informe de pruebas funcionales de EdiConnect

Fecha: 24 de octubre de 2026, America/Santiago. Versión examinada: `6323cdb`.

**Informe inicial, previo a las correcciones.** Los escenarios normales pasaron, pero se detectaron problemas de autorización en la API y de validación de comprobantes. Los 11 hallazgos ya tienen corrección y pruebas de regresión; consulta el [informe de correcciones](C:/Users/iturr/OneDrive/Documentos/GitHub/TecnologiasWeb/docs/correcciones-2026-10-24.md) para el estado actual. El resto de este documento conserva la evidencia original.

## Alcance y resultados

- Instancia Docker aislada: proyecto `ediconnect-qa`, accesible durante las pruebas en `http://localhost:8001`, con base de datos y archivos separados de la aplicación habitual.
- 28 suites de integración reales contra Nginx, PHP-FPM, Laravel y MySQL: **28 aprobadas, 1.142 comprobaciones PASS**. Las comprobaciones incluyen repeticiones por rol, permisos y escenarios; no representan 1.142 funcionalidades diferentes.
- Auditoría complementaria: **19 verificaciones, 7 aprobadas y 12 fallidas**. Las fallidas se agrupan en 11 hallazgos técnicos/reglas inconsistentes; dos corresponden al mismo problema de búsqueda en dos pantallas.
- PDF: comprobado contenido, acentos, montos, estados, paginación y límites del documento. El caso de 50 conceptos conservó los datos y el total en 6 páginas.
- Navegador: login administrativo, panel, navegación del mural y revisión de comprobantes; esta última también a 390 px de ancho, sin desbordamiento global horizontal. Las tablas conservan desplazamiento interno.
- No se contó `tests/verificar_logica.py` como prueba de la aplicación: simula lógica en Python y no ejecuta Laravel.

La matriz indica qué se probó y dónde aparecieron excepciones. «Pasó» significa que los casos ejecutados no encontraron un fallo; no constituye cobertura exhaustiva de todas las entradas posibles.

## Matriz de funcionalidades

| Funcionalidad | Prueba ejecutada | Resultado |
|---|---|---|
| Login web por correo/RUT | Credenciales, campos, usuario activo/inactivo y redirección | Pasó; suites smoke, HU-04 y usuarios |
| Cierre de sesión y protección de sesión | Logout, invitado y middleware guest | Pasó |
| Paneles y permisos por rol | Administrador, conserje y residente; rutas ajenas | Pasó en web |
| Registro de usuarios | Campos obligatorios, duplicados, credenciales y login de nueva cuenta | Pasó HU-03 |
| Asignación de rol al crear usuario | Rol permitido y asociación persistida | Pasó HU-03 |
| Activación/desactivación de usuarios | Cambio de estado, revocaciones y pérdida de acceso | Pasó HU-04 |
| Edición de contacto personal | Teléfono, correo secundario, validaciones y aislamiento | Pasó HU-06 |
| Cambio de contraseña desde perfil | Clave actual, complejidad, otras sesiones, tokens y CSRF | Pasó docker-password |
| Recuperación/restablecimiento | Enlace inválido y restablecimiento con token válido | Funciona; F10/F11: política inconsistente y tokens conservados |
| Autenticación API | Credenciales y emisión de token Sanctum | Pasó smoke; los endpoints de negocio tienen F01/F02/F04 |
| Registro de departamentos | Número único, piso y persistencia | Pasó |
| Activar/desactivar departamentos | Estado y bloqueo de nuevas asociaciones | Pasó |
| Asociar/desasociar residentes | Asociación y titularidad en los escenarios existentes | Pasó |
| Contactos de emergencia | Registro y eliminación normal; entradas adversas | Fallos F07/F08 |
| Generación mensual de gastos | Unidad, período, monto, duplicados y concurrencia | Pasó HU-11 |
| Desglose de gastos | Conceptos, suma exacta, reemplazo, errores y concurrencia | Pasó HU-12 |
| Consulta de gastos del residente | Unidades propias, períodos, montos, detalles y paginación | Pasó HU-13; la API incumple el aislamiento en F01 |
| Estado pendiente/moroso/pagado | Vencimiento, medianoche de Santiago y resumen de historial | Pasó HU-14 |
| Validación manual de pago | Monto total, referencia, responsable y doble validación | Pasó HU-14; aprobación por comprobante tiene F05 |
| Descarga PDF | Datos completos, permisos, estados, unidades inactivas y desglose largo | Pasó HTTP y verificación del documento |
| Carga web de comprobante | Archivo válido, formato inválido, ausencia, límite de 5 MB y fecha futura | Pasó para montos habituales; F06 con monto máximo |
| Carga web de comprobante ajeno | URL manipulada hacia otra unidad | Pasó: HTTP 404 |
| Cargas simultáneas web | Dos sesiones y un cobro | En la ejecución quedó un único comprobante en revisión; no se reprodujo duplicación |
| Carga API de comprobante | Invitado, cobro ajeno y cobro pagado | Fallos F02/F03 |
| Revisión administrativa de comprobantes | Listado, filtros y estados normales | Pasó suite comprobantes |
| Aprobación de comprobantes | Flujo normal y monto parcial | Flujo normal pasa; F05 permite liquidar un total con monto inferior |
| Rechazo de comprobantes | Motivo obligatorio, persistencia y visualización del rechazo | Pasó suite comprobantes |
| Registro de espacios comunes | Nombre, descripción, estado y permisos | Pasó HU-19 |
| Configuración de espacios | Aforo, horario, costo, reglas y activación | Pasó HU-20 |
| Disponibilidad de espacios | Bloques disponibles, ocupados y pasados | Pasó HU-21 |
| Solicitud de reserva | Sesión, horario, persistencia, duplicados y concurrencia | Pasó HU-22 |
| Prevención de solapamientos | Coincidencias totales/parciales y bloques contiguos | Pasó HU-23 |
| Historial personal de reservas | Privacidad, estados, fechas y paginación | Pasó HU-24 |
| Cancelación personal de reserva | Motivo, titular, inicio, estado y liberación de horario | Pasó |
| Consulta administrativa de reservas | Filtros, titulares, cancelaciones e historial | Pasó |
| Aprobar/rechazar/cancelar reserva | Transiciones, motivo, eventos, autoría y concurrencia | Pasó |
| Recepción web de encomiendas | Departamento activo, registro y permisos | Pasó HU-25 |
| Consulta personal de encomiendas | Asociación y aislamiento por unidad | Pasó HU-26 |
| Retiro de encomiendas | Estado y confirmación del retiro | Pasó HU-27 |
| Permanencia de encomiendas | Fechas, duración y ordenamiento | Pasó HU-28 |
| Recepción API de encomiendas | Registro sin autenticación | Fallo F04 |
| Visitas | Registro, consulta en recinto y salida | Pasó |
| Vehículos | Registro, búsqueda de patente, vigencia y revocación | Pasó |
| Avisos administrativos | Publicar, editar, retirar y priorizar | Pasó flujo normal; F09 con búsqueda malformada |
| Mural del residente | Avisos vigentes, prioridad y visibilidad | Pasó flujo normal; F09 con búsqueda malformada |
| Registro de incidencias | Categoría, descripción y unidad del residente | Pasó suite mural/incidencias |
| Consulta personal de incidencias | Listado, detalle y bloqueo de incidencia ajena | Pasó |
| Gestión administrativa/conserjería de incidencias | En proceso, resolución, respuesta y responsable | Pasó |
| Frontend común y navegación | Renderizado, recursos locales, sección activa y menús por rol | Pasó para las pantallas cubiertas en docker-front; observación visual V01 en mural |

## Hallazgos y reproducción

### F01 — Alta: consulta pública de gastos comunes

**Evidencia:** `API-01`. Un cliente sin cookies ni token llamó `GET /api/v1/gastos-comunes` y obtuvo HTTP 200, incluyendo el identificador y monto de un cobro de la unidad B.

**Reproducir:** crear un gasto de prueba y consultar esa ruta desde una sesión sin autenticar. No es necesario conocer previamente su departamento.

**Esperado:** HTTP 401 para invitados y consulta limitada a las unidades del residente autenticado. Las rutas web sí aplican ese aislamiento.

**Ubicación:** `routes/api.php` y `app/Http/Controllers/Api/ComprobanteApiController.php::listarGastos`.

### F02 — Alta: carga anónima de comprobantes e identidad fija

**Evidencia:** `API-02`. `POST /api/v1/comprobantes` sin sesión ni token, con `gasto_id` de otra unidad y un PNG válido, devolvió HTTP 201. Se guardó `user_id=3`.

**Esperado:** autenticar al residente y comprobar su asociación al gasto antes de aceptar el archivo. No atribuir cargas anónimas a una cuenta fija.

**Ubicación:** `routes/api.php` y `ComprobanteApiController::store`, especialmente `$request->user()?->id ?? 3`.

### F03 — Media: API acepta comprobantes de un gasto ya pagado

**Evidencia:** `API-03`. Se marcó un cobro de prueba como pagado y se envió otro archivo por API: HTTP 201, con dos comprobantes registrados para el caso. La ruta web aplica una comprobación de estado que esta API no ejecuta.

**Esperado:** rechazar la nueva carga en cobros pagados y mantener las reglas de «en revisión» coherentes entre web y API.

**Ubicación:** `ComprobanteApiController::store` y `GastoComun::permiteCargarComprobante`.

### F04 — Alta: registro de encomiendas sin autenticación

**Evidencia:** `API-04`. Un cliente anónimo envió `POST /api/v1/encomiendas` con una unidad activa y una empresa de prueba. Se creó el registro y se devolvió HTTP 201.

**Esperado:** exigir una identidad autorizada para registrar recepciones, como ocurre en la ruta web de conserjería.

**Ubicación:** `routes/api.php` y `app/Http/Controllers/Api/EncomiendaApiController.php::store`.

### F05 — Alta: aprobación de comprobante con monto inferior al cobro

**Evidencia:** `PAGO-01`. Para un gasto de **$85.000**, el residente cargó un comprobante declarando **$1**. El administrador lo aprobó y el gasto quedó **pagado**, con HTTP 302. La diferencia no se bloqueó ni se gestionó como saldo pendiente.

**Reproducir:** cargar archivo válido con `monto=1` y aprobarlo desde `/admin/comprobantes/{id}/aprobar`.

**Esperado:** si la aprobación representa pago completo, exigir coincidencia con el total, igual que el formulario de validación manual. Si se desean abonos, se requiere un saldo explícito y el cobro no debe quedar liquidado por un abono.

**Ubicación:** `app/Http/Controllers/Admin/ComprobantePagoController.php::aprobar`.

### F06 — Media: el monto máximo admitido falla al guardar el comprobante

**Evidencia:** `PAGO-02`. Un gasto por **$999.999.999.999**, aceptado por el módulo de gastos, produjo HTTP 500 al cargar un comprobante con ese mismo monto. No se creó el registro del comprobante.

El log confirmó `SQLSTATE[22003]`, valor fuera de rango para la columna `monto`. La columna del comprobante es `unsignedInteger`; el gasto utiliza un rango mayor y la validación acepta ese rango.

**Esperado:** tipos de columna y límites coherentes; una entrada admitida no debe acabar en error 500.

**Ubicación:** `database/migrations/2026_10_24_000018_create_comprobantes_pago_table.php` y `Residente/GastoComunController::storeComprobante`.

### F07 — Media: contacto de emergencia asociado a un residente ajeno

**Evidencia:** `DEPTO-01`. El administrador creó un contacto de la unidad A enviando `user_id` de un residente asociado únicamente a B. El servidor lo aceptó y persistió la relación.

**Esperado:** cuando se elige un residente para el contacto, debe pertenecer a la misma unidad; alternativamente, debe quedar como contacto general de la unidad.

**Ubicación:** `app/Http/Controllers/Admin/DepartamentoController.php::storeContacto`.

### F08 — Media: contacto de emergencia acepta teléfono sin dígitos

**Evidencia:** `DEPTO-02`. Se guardó literalmente `-------` como teléfono, con HTTP 302.

**Esperado:** validar una cantidad real de dígitos, no solo la longitud de una combinación de guiones, espacios y paréntesis.

**Ubicación:** expresión regular de `DepartamentoController::storeContacto`.

### F09 — Media: filtros de búsqueda provocan error 500

**Evidencia:** dos verificaciones `FILTRO`. Estas consultas autenticadas devolvieron HTTP 500:

```text
/admin/avisos?busqueda[]=x
/residente/mural?busqueda[]=x
```

El log confirmó `trim(): Argument #1 must be of type string, array given`. No exige una búsqueda extensa: basta enviar un tipo de dato distinto del esperado.

**Esperado:** validar el tipo y responder con un error controlado, sin excepción de servidor.

**Ubicación:** `Admin/AvisoController::index` y `Residente/MuralController::index`.

### F10 — Media, regla inconsistente: recuperación permite contraseña de 6 caracteres

**Evidencia:** `AUTH-01`. Con un token de recuperación válido, se estableció la contraseña de prueba `123456` y el hash guardado confirmó el cambio.

El cambio desde el perfil exige 12 caracteres; la recuperación exige 6. El formulario de recuperación también anuncia 6. Es una inconsistencia de política que se debe resolver, no un fallo de funcionamiento del restablecimiento.

**Esperado bajo el criterio de auditoría:** aplicar una política uniforme a creación/cambio/recuperación, especialmente cuando se usa la recuperación para reemplazar una clave fuerte.

**Ubicación:** `Auth/RecuperarContrasenaController::restablecerContrasena` y formulario de restablecimiento.

### F11 — Media: recuperación conserva tokens API anteriores

**Evidencia:** `AUTH-02`. Se creó un token Sanctum de prueba y se restableció la contraseña. El registro anterior continuó en `personal_access_tokens`. El cambio de contraseña desde perfil sí lo revoca.

**Esperado:** invalidar los tokens anteriores también al recuperar una cuenta.

**Límite de la evidencia:** se confirmó su conservación en base de datos. Los endpoints de negocio examinados no exigen autenticación Sanctum; por ello no se afirma que se haya probado el uso de ese token contra un recurso correctamente protegido.

**Ubicación:** `Auth/RecuperarContrasenaController::restablecerContrasena`.

## Observaciones y límites

- **V01, visual:** el mural administrativo carga y funciona, pero usa la cabecera anterior y no muestra la barra lateral compartida que sí aparece en comprobantes y en el panel. Se observó en navegador; es una inconsistencia visual, independiente del fallo del filtro F09.
- La recuperación de contraseña está implementada como simulación local: muestra un enlace en pantalla. No se probó entrega real de correo ni integración SMTP; el código no envía ese correo en este flujo.
- Se probaron datos ficticios en una instalación nueva de QA. No se hicieron pruebas de carga sostenida, compatibilidad con todos los navegadores, restauración de respaldos ni despliegue productivo.
- La ausencia de duplicación en el caso de dos cargas concurrentes de comprobante no demuestra seguridad bajo todas las condiciones de concurrencia. Se reporta únicamente el resultado observado.
- Dos suites se iniciaron antes de que Nginx de QA estuviera listo y fallaron por resolución de `web`. Se repitieron con todos los contenedores listos y ambas pasaron. El resumen final recoge esas ejecuciones válidas; no se contó ese arranque como un fallo del software.
- Las pruebas no modificaron controladores, modelos, migraciones ni vistas. Se añadió la auditoría reproducible y la configuración aislada de QA.

## Evidencia y reproducción general

Los resultados originales están en la carpeta local:

[Logs y resultados de QA](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/qa-2026-10-24)

- [Resumen de las 28 suites](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/qa-2026-10-24/suites.json)
- [Resultados de la auditoría adicional](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/qa-2026-10-24/auditoria.json)
- [Errores de servidor confirmados](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/qa-2026-10-24/errores-confirmados.log)

Para repetir en la instancia aislada, desde la raíz del proyecto:

```powershell
docker compose -p ediconnect-qa -f docker-compose.yml -f tests/compose.qa.yml up -d --build
# Esperar que app y db estén healthy y web esté en ejecución.
docker compose -p ediconnect-qa -f docker-compose.yml -f tests/compose.qa.yml exec -T -e QA_AUDIT=1 app php tests/docker-auditoria-integral.php
```

La auditoría devuelve código 1 mientras persistan las verificaciones fallidas; ese código es intencional. No ejecutar esta auditoría ni `docker-comprobantes.php` sobre datos reales: la primera está diseñada para QA y la segunda reutiliza la unidad demo 202 y sus cobros.

Para detener solo QA, conservando sus volúmenes:

```powershell
docker compose -p ediconnect-qa -f docker-compose.yml -f tests/compose.qa.yml down
```

La prioridad de corrección es F01/F02/F04 (autorización API) y F05 (coherencia de aprobación de pagos), seguida de F06/F07/F08/F09 y la política de recuperación F10/F11.

## Ejecuciones de suites

| Suite | Comprobaciones PASS | Resultado |
|---|---:|---|
| docker-admin-reservas.php | 31 | Pasó |
| docker-cancelacion-reservas.php | 27 | Pasó |
| docker-comprobantes.php | 42 | Pasó |
| docker-departamentos.php | 17 | Pasó |
| docker-front.php | 103 | Pasó |
| docker-hu03.php | 22 | Pasó |
| docker-hu04.php | 33 | Pasó |
| docker-hu06.php | 83 | Pasó |
| docker-hu11.php | 41 | Pasó |
| docker-hu12.php | 36 | Pasó |
| docker-hu13.php | 63 | Pasó |
| docker-hu14.php | 41 | Pasó |
| docker-hu15.php | 31 | Pasó |
| docker-hu19.php | 36 | Pasó |
| docker-hu20.php | 59 | Pasó |
| docker-hu21.php | 25 | Pasó |
| docker-hu22.php | 33 | Pasó |
| docker-hu23.php | 77 | Pasó |
| docker-hu24.php | 36 | Pasó |
| docker-hu25.php | 27 | Pasó |
| docker-hu26.php | 21 | Pasó |
| docker-hu27.php | 18 | Pasó |
| docker-hu28.php | 14 | Pasó |
| docker-mural-incidencias.php | 48 | Pasó |
| docker-password.php | 85 | Pasó |
| docker-revision-reservas.php | 36 | Pasó |
| docker-smoke.php | 17 | Pasó |
| docker-visitas-vehiculos.php | 40 | Pasó |
