# EdiConnect · Tecnologías Web

Plataforma de gestión de comunidades residenciales con PHP 8.2, Laravel 12, Nginx y MySQL 8.

## Ejecutar con Docker

Con Docker Desktop instalado e iniciado (contenedores Linux), abre una terminal en esta carpeta y ejecuta:

```powershell
docker compose up -d
```

La primera ejecución construye la imagen e instala las dependencias con Composer. MySQL debe estar listo antes de iniciar Laravel; la aplicación genera y conserva su clave, ejecuta las migraciones y crea las cuentas de demostración una sola vez. No necesitas instalar PHP, Composer ni Node en tu equipo, ni crear un `.env`.

Cuando termine el arranque, abre <http://localhost:8000>.

| Rol | Correo | Contraseña inicial |
| --- | --- | --- |
| Administrador | admin@ediconnect.cl | Password123! |
| Conserje | conserje@ediconnect.cl | Password123! |
| Residente | residente@ediconnect.cl | Password123! |

Estas cuentas y los valores predeterminados son para desarrollo local.

Se actualizaron Laravel y Sanctum porque Composer bloqueaba las versiones originales de Laravel 10 por avisos de seguridad. `composer.lock` fija las versiones instaladas.

## Estado y mantenimiento

```powershell
docker compose ps
docker compose logs -f app
docker compose down
```

Los datos de MySQL, la clave de la aplicación y las sesiones se guardan en volúmenes. Detener o recrear los contenedores conserva esos datos y las contraseñas modificadas. `docker compose down -v` elimina los volúmenes y todos los datos locales.

Después de modificar código PHP o dependencias, reconstruye la imagen:

```powershell
docker compose up -d --build
```

Para personalizar variables, puedes copiar `.env.example` a `.env`. Es opcional; Compose ya incluye los valores necesarios. Si cambias el puerto, configura `APP_PORT` y `APP_URL` juntos. Las credenciales de MySQL deben coincidir con las del volumen existente; cambiar `.env` no cambia contraseñas en una base ya inicializada.

La recuperación de contraseña actual muestra un enlace de prueba en pantalla; no envía correos reales.

## Verificar la instalación

Con los contenedores iniciados y las cuentas de demostración conservando su contraseña inicial:

```powershell
docker compose exec -T app php tests/docker-smoke.php
```

Esta prueba usa la aplicación real a través de Nginx y comprueba login, dashboards, permisos, CSRF y emisión de tokens de la API.

## HU-04: activación y desactivación de usuarios

Entra como administrador y abre **Usuarios y Roles → Administrar usuarios**, o visita `/admin/usuarios`. El listado muestra nombre, correo, RUT, rol y estado de cada cuenta, con las acciones **Activar** y **Desactivar**.

Una cuenta inactiva no puede iniciar sesión por web ni por API. Si tiene una sesión web abierta, pierde acceso al solicitar una página protegida. Al desactivarla se revocan sus tokens API y su cookie de recordar sesión. Al reactivarla conserva su rol y contraseña. Solo un administrador activo puede cambiar estados; no puede desactivar su propia cuenta.

Para comprobar esta HU con usuarios temporales que se eliminan al terminar:

```powershell
docker compose exec -T app php tests/docker-hu04.php
```

## HU-06: actualización de datos de contacto

Desde cualquier panel, abre **Mi perfil** (`/perfil`). Una cuenta activa puede actualizar su propio teléfono y correo secundario. Ambos son opcionales; dejarlos vacíos elimina el dato guardado. El teléfono admite entre 7 y 15 dígitos, con código de país, espacios, paréntesis o guiones. El correo secundario debe tener un formato válido.

El nombre, RUT, correo de acceso, rol y estado se muestran como datos de consulta. Las peticiones solo guardan los campos de contacto permitidos sobre la cuenta autenticada, aunque incluyan identificadores de otras cuentas o campos adicionales. Un error de validación conserva lo escrito y no guarda cambios parciales. El correo secundario no cambia las credenciales de acceso.

La nueva columna se crea automáticamente al reconstruir y arrancar Docker. Para probar persistencia, validaciones y protección de campos en los tres roles con cuentas temporales:

```powershell
docker compose exec -T app php tests/docker-hu06.php
```

### Cambiar la contraseña personal

En **Mi perfil → Cambiar contraseña**, indica la contraseña actual, una nueva de al menos 12 caracteres y su confirmación. Debe ser diferente de la actual y ocupar como máximo 72 bytes (límite de bcrypt). Esta acción solo cambia la cuenta autenticada y tiene un límite de 10 intentos por minuto.

La sesión donde se realiza el cambio continúa activa; las demás sesiones se rechazan en su siguiente petición. Se invalidan las cookies anteriores de recordar sesión, los tokens API y los enlaces de recuperación pendientes. Las contraseñas no se vuelven a mostrar al fallar una validación. El formulario de contacto sigue sin permitir cambiar contraseñas.

```powershell
docker compose exec -T app php tests/docker-password.php
```

Esta prueba crea cuentas temporales para cada rol, comprueba los rechazos, el nuevo login y la invalidación de otros accesos, y elimina las cuentas al terminar.

## HU-19: registro de espacios comunes

En el panel del administrador, abre **Espacios Comunes → Administrar espacios** (`/admin/espacios-comunes`). Permite registrar espacios con nombre, descripción y estado, y consultar el listado paginado de registros.

El nombre es obligatorio, único y admite hasta 150 caracteres; la descripción es obligatoria y admite hasta 2000. El estado debe ser **Activo** o **Inactivo**. Las validaciones conservan los valores escritos cuando hay errores. Solo las cuentas de administrador activas pueden entrar y registrar espacios.

La tabla `espacios_comunes` se crea automáticamente al reconstruir y arrancar Docker. Los espacios activos se pueden consultar con `EspacioComun::activos()`. La HU-19 cubre registro y consulta administrativa; la configuración se describe en la HU-20 y la disponibilidad para residentes en la HU-21.

```powershell
docker compose exec -T app php tests/docker-hu19.php
```

La prueba usa cuentas y espacios temporales, comprueba persistencia, validaciones y permisos, y elimina sus datos al terminar.

## HU-20: configuración de espacios comunes

En **Administrar espacios**, elige **Configurar espacio** o **Ver y editar configuración** en el espacio correspondiente. La configuración exige aforo (1 a 100000 personas), horario diario de apertura y cierre dentro del mismo día, costo entero en CLP (0 para gratuito, máximo 999999999999) y reglas de uso (hasta 5000 caracteres).

Solo el administrador activo puede consultar y guardar la configuración. Se guarda sobre el espacio seleccionado sin cambiar su nombre, descripción ni estado; también se puede preparar la configuración de un espacio inactivo. Los espacios anteriores conservan sus datos y aparecen con configuración pendiente hasta completarla. El listado resume aforo, horario y costo, y el formulario recupera las reglas y los valores guardados para editarlos.

La migración se aplica al reconstruir y arrancar Docker. El horario actual es el mismo todos los días; no incluye franjas por día, turnos nocturnos ni cobros.

```powershell
docker compose exec -T app php tests/docker-hu20.php
```

La prueba comprueba persistencia, edición, aislamiento entre espacios, validaciones y permisos con datos temporales que elimina al terminar.

### Activar o desactivar un espacio existente

Cada espacio del listado tiene un botón **Activar** o **Desactivar**, exclusivo del administrador activo. Desactivarlo requiere confirmar y conserva nombre, descripción, aforo, horario, costo y reglas. Se puede reactivar después sin volver a registrarlo ni configurarlo. Un espacio puede crearse inactivo para prepararlo antes de habilitar su uso o desactivarse temporalmente por mantenimiento. La consulta `EspacioComun::activos()` refleja los cambios de estado. Las pruebas de `docker-hu20.php` también verifican este flujo, sus permisos y la conservación de datos.

### Alertas de la interfaz

Las confirmaciones de activación y desactivación y los avisos de éxito o error usan SweetAlert2 11.26.25, con etiquetas en español y colores del sitio. Los errores junto a cada campo se mantienen. La biblioteca y su licencia MIT se incluyen en `public/lib/sweetalert2`, sin depender de una descarga externa en el navegador. El comportamiento compartido está en `public/js/alertas.js` y se integra mediante `components.alertas`. Si JavaScript no carga, los mensajes del servidor permanecen visibles.

## HU-21: consulta de disponibilidad de espacios

Como residente activo, abre **Reserva de Espacios → Consultar disponibilidad** (`/residente/espacios-comunes/disponibilidad`). Selecciona un espacio activo y una fecha en el calendario, y pulsa **Consultar disponibilidad**. Se muestran aforo, costo, reglas y los horarios del día. Los espacios sin configuración completa muestran un aviso.

Los bloques duran 60 minutos desde la apertura; el último se acorta al alcanzar el cierre. Esta duración es una decisión de implementación, ya que la HU-20 no define duración de reservas. Se distinguen **Disponible**, **Ocupado** y **Horario pasado** con texto y color. Solo los disponibles se pueden seleccionar. Los horarios iniciados se deshabilitan usando `America/Santiago`. Cambiar los filtros oculta los resultados anteriores hasta volver a consultar.

La migración crea `reservas`, relacionada con espacio y usuario, con fecha, hora de inicio, hora de fin y estado (`pendiente`, `confirmada`, `rechazada`, `cancelada`). Las reservas pendientes y confirmadas bloquean cualquier bloque con el que se solapen; las canceladas y rechazadas no lo bloquean. Una reserva que termina exactamente al inicio de otro bloque no lo ocupa. La consulta lee datos persistidos sin mostrar datos de otros residentes y se envía sin caché.

La HU-21 permite consultar y seleccionar horarios; la confirmación se incorpora en la HU-22. No se agregan reservas ficticias: un espacio sin reservas vigentes aparece disponible dentro de su horario.

Reconstruye para aplicar la migración y ejecuta la prueba con datos temporales que se eliminan al terminar:

```powershell
docker compose up -d --build
docker compose exec -T app php tests/docker-hu21.php
```

La prueba comprueba solapamientos parciales, límites entre bloques, cancelaciones, aislamiento por espacio y fecha, horarios pasados, validación de filtros, permisos y controles deshabilitados.

## HU-22: reserva de espacios comunes

Desde **Consultar disponibilidad**, selecciona un bloque disponible y pulsa **Enviar solicitud**. El sistema vuelve a comprobar el espacio activo, la configuración, el horario y las reservas vigentes en el servidor. Solo acepta bloques exactos del calendario que todavía no hayan comenzado, incluido el último bloque corto del día. Una reserva que ocupa parte de un bloque también impide solicitarlo.

La solicitud se guarda con estado **Pendiente**, asociada exclusivamente al residente autenticado, hasta la aprobación del administrador. Los identificadores de usuario, estados y horas adicionales enviados por el navegador no alteran estos datos. Después de enviar se abre **Mis reservas** (`/residente/reservas`) con un aviso de recepción, espacio, fecha, horario, estado y número de solicitud. El listado está paginado y solo muestra registros propios; también está enlazado desde el portal y el calendario.

La comprobación y creación se ejecutan en una transacción con bloqueo de la fila del espacio (`FOR UPDATE`). Esto serializa confirmaciones simultáneas incluso si el espacio aún no tiene reservas, y coordina con los cambios administrativos del espacio. Si otro residente ocupa el horario antes de confirmar, se muestra un error para volver a consultar. Reenviar una confirmación no duplica la reserva porque el bloque ya está ocupado. Cualquier futuro flujo que cree o modifique reservas debe usar el mismo bloqueo por espacio.

Las rutas exigen una cuenta activa con rol Residente; confirmar exige CSRF. Las cancelaciones se describen más abajo. No se incluyen cobros.

```powershell
docker compose up -d --build
docker compose exec -T app php tests/docker-hu22.php
```

La prueba usa datos temporales y verifica persistencia, asociación a la sesión, listado privado, filtros y horarios inválidos, bloques contiguos, doble envío, conflictos desde el calendario, CSRF, permisos y dos confirmaciones concurrentes de residentes distintos. Elimina sus datos al terminar.

## HU-23: prevención de reservas solapadas

El calendario y la confirmación comparten el cálculo de disponibilidad de `DisponibilidadEspacio`. Una reserva pendiente o confirmada del mismo espacio y fecha bloquea un horario si su inicio es anterior al fin solicitado **y** su fin es posterior al inicio solicitado. Se rechazan coincidencias exactas, parciales y reservas contenidas o que contengan el bloque completo, conservando la precisión de segundos de los registros existentes.

Los bloques ocupados aparecen deshabilitados. Si otro residente ocupa el horario después de abrir el calendario, la confirmación vuelve a validar bajo el bloqueo transaccional de la HU-22 y rechaza la solicitud sin insertar una reserva ni modificar la existente. El mensaje indica: **El horario ya no está disponible: coincide total o parcialmente con una reserva existente.** Se muestra tanto en el calendario al volver del formulario como en los errores de validación JSON (HTTP 422), sin exponer datos del titular.

Dos reservas contiguas pueden compartir el instante de término/inicio: los intervalos se consideran `[inicio, fin)`. Las reservas canceladas, de otros espacios o de otras fechas no bloquean el horario.

```powershell
docker compose up -d --build
docker compose exec -T app php tests/docker-hu23.php
docker compose exec -T app php tests/docker-hu22.php
```

La prueba HU-23 cubre 15 escenarios de solapamiento y límites, verificando tanto los controles del calendario como la confirmación real, el mensaje de error y la conservación de la reserva existente. La HU-22 también comprueba solicitudes concurrentes de dos residentes. Ambas usan datos temporales y los eliminan al terminar.

## HU-24: consulta de reservas personales

Desde el portal del residente o el calendario, abre **Mis reservas** (`/residente/reservas`). El listado muestra espacio, fecha, horario, estado y número de reserva, ordenado por fecha y horario descendentes, con 10 registros por página. Incluye reservas pasadas y futuras, confirmadas, pendientes, rechazadas y canceladas, incluso si el espacio se desactiva posteriormente. Si no tienes registros, muestra un mensaje y el enlace para consultar disponibilidad.

La consulta usa exclusivamente el usuario autenticado; enviar identificadores de otros residentes no cambia el listado ni la paginación. Solo acceden residentes activos. La respuesta lleva `Cache-Control: private, no-store`. Una página fuera de rango muestra un enlace para regresar a la primera página.

```powershell
docker compose up -d --build
docker compose exec -T app php tests/docker-hu24.php
```

La prueba crea dos residentes con 13 reservas cada uno sobre los mismos espacios y compara todas las columnas de ambas páginas con los registros esperados. Comprueba privacidad con identificadores manipulados, persistencia desde otra sesión, estados, fechas pasadas y futuras, espacios inactivos, texto escapado, historial vacío, páginas fuera de rango y permisos. Elimina sus datos temporales al terminar.

### Cancelar una reserva con justificación

En **Mis reservas**, abre **Cancelar reserva** en una reserva pendiente o confirmada que aún no haya comenzado. Escribe una justificación obligatoria de hasta 1000 caracteres y pulsa **Confirmar cancelación**. Se muestra una confirmación con SweetAlert2; el formulario y las validaciones del servidor también funcionan sin JavaScript.

Solo el titular activo con rol Residente puede cancelar. El servidor vuelve a comprobar el inicio en horario de Santiago y el estado al guardar. No permite cancelar reservas iniciadas, pasadas ni ya canceladas. No se establece una antelación mínima adicional. Se conservan espacio, fecha y horario, cambiando el estado a **Cancelada** y guardando `motivo_cancelacion` y `cancelada_at`. Ambos datos aparecen en el historial personal; no se borra la reserva. Las cancelaciones antiguas sin motivo muestran **Sin justificación registrada**.

El horario se libera al cancelar y puede reservarlo otro residente. La operación usa el mismo bloqueo por espacio que la confirmación para coordinar solicitudes simultáneas. Repetir la cancelación no reemplaza la justificación original. La migración agrega campos opcionales a las reservas existentes sin alterar sus estados.

```powershell
docker compose up -d --build
docker compose exec -T app php tests/docker-cancelacion-reservas.php
```

La prueba comprueba justificación obligatoria, límites y tipos, privacidad, permisos, CSRF, reservas iniciadas o pasadas, conservación de datos, cancelación duplicada, texto escapado, liberación del horario y cancelación simultánea con una nueva confirmación. Usa datos temporales y los elimina al terminar.

### Consulta administrativa de reservas y cancelaciones

En el panel del administrador, abre **Espacios Comunes → Ver reservas y cancelaciones** (`/admin/reservas`). El listado incluye reservas de todos los residentes con su nombre y correo, espacio, fecha, horario y estado. En las canceladas también muestra justificación y fecha de cancelación, conservando las cancelaciones antiguas sin justificación. Incluye espacios desactivados e historial pasado.

Permite filtrar por espacio, fecha de la reserva y estado. La paginación conserva los filtros; **Limpiar filtros** vuelve al historial completo. Solo acceden administradores activos y la respuesta se envía sin caché. Los residentes cancelan sus propias solicitudes o reservas futuras desde **Mis reservas**. La gestión administrativa se detalla a continuación.

```powershell
docker compose exec -T app php tests/docker-admin-reservas.php
```

La prueba comprueba datos de varios residentes, filtros, paginación, espacios inactivos, texto escapado, cancelaciones antiguas, validaciones y permisos. También realiza una cancelación desde el portal del residente y verifica que aparezca al administrador con motivo y fecha. Limpia sus datos temporales al terminar.

### Aprobación y rechazo de solicitudes

El residente **solicita** un horario; la administración debe aprobarlo antes de que quede confirmado. Las solicitudes pendientes bloquean el horario para impedir peticiones solapadas. El panel del administrador abre directamente el filtro de pendientes, y el historial incluye un acceso a **Ver solicitudes pendientes** con el total global.

En **Gestionar solicitud**, un administrador activo puede:

- Aprobar una pendiente: pasa a **Confirmada**, con observación opcional. Se comprueba nuevamente que el espacio esté activo y configurado, el bloque siga vigente y disponible (excluyendo la propia solicitud) y el titular siga siendo residente activo.
- Rechazar una pendiente: pasa a **Rechazada**, con justificación obligatoria de hasta 1000 caracteres; libera el horario. También permite cerrar solicitudes pendientes vencidas mediante rechazo.
- Cancelar una confirmada futura: pasa a **Cancelada**, con justificación obligatoria; libera el horario y conserva los datos de cancelación.

No se reabren rechazadas ni canceladas y no se cambia una confirmada a rechazada. Las reservas confirmadas antes de este cambio conservan su estado; la migración no las convierte a pendientes.

Cada nueva solicitud, decisión y cancelación registra un evento con estado anterior/nuevo, usuario responsable, fecha y observación en `reserva_eventos`. **Ver historial** permite al administrador consultar la secuencia; el residente ve los estados y observaciones de sus propias solicitudes. Las observaciones se muestran como texto seguro. Los registros anteriores mantienen sus datos originales sin inventar eventos retroactivos.

Todas las operaciones de estado usan el mismo bloqueo transaccional por espacio. Dos decisiones sobre una pendiente se serializan y solo se acepta la primera; la siguiente devuelve un error de validación sin sobrescribir el historial. Se requiere CSRF y el rol Administrador activo para gestionar estados.

```powershell
docker compose up -d --build
docker compose exec -T app php tests/docker-revision-reservas.php
```

La prueba comprueba solicitudes pendientes, aprobación, rechazo, cancelación administrativa, justificaciones, historial y autoría, liberación del horario, cambios de espacio y residente, solapamientos, horarios pasados, transiciones inválidas, CSRF, permisos y decisiones concurrentes. Elimina sus datos temporales al terminar.

## HU-07, HU-08, HU-09 y HU-10: Módulo de Departamentos

Desde el dashboard del administrador, abre **Departamentos → Administrar departamentos** (`/admin/departamentos`).

- **HU-07 (Registro):** Permite registrar nuevas unidades residenciales indicando el número único (ej.: 101, 204-B) y el piso correspondiente (-10 a 200). Valida unicidad a nivel de formulario y base de datos.
- **HU-08 (Gestión de Estado):** Permite activar o desactivar departamentos. Si un departamento está inactivo, el sistema restringe estrictamente la vinculación de nuevos residentes.
- **HU-09 (Asociación de Residentes):** Permite vincular cuentas activas con rol Residente a departamentos activos, con designación opcional de residente titular principal o habitante general, así como desasociar residentes.
- **HU-10 (Contactos de Emergencia):** Permite registrar contactos de emergencia (nombre, teléfono y relación/parentesco) vinculados a la unidad o a un residente específico de la misma, con capacidad de eliminación directa.

Para ejecutar la suite de pruebas del módulo de departamentos con datos temporales que se limpian automáticamente:

```powershell
docker compose exec -T app php tests/docker-departamentos.php
```
