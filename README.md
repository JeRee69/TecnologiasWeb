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

La tabla `espacios_comunes` se crea automáticamente al reconstruir y arrancar Docker. Los espacios activos se pueden consultar con `EspacioComun::activos()`. La HU-19 cubre registro y consulta administrativa; la configuración se describe en la HU-20. Las reservas y el acceso de residentes a este módulo quedan para un sprint futuro.

```powershell
docker compose exec -T app php tests/docker-hu19.php
```

La prueba usa cuentas y espacios temporales, comprueba persistencia, validaciones y permisos, y elimina sus datos al terminar.

## HU-20: configuración de espacios comunes

En **Administrar espacios**, elige **Configurar espacio** o **Ver y editar configuración** en el espacio correspondiente. La configuración exige aforo (1 a 100000 personas), horario diario de apertura y cierre dentro del mismo día, costo entero en CLP (0 para gratuito, máximo 999999999999) y reglas de uso (hasta 5000 caracteres).

Solo el administrador activo puede consultar y guardar la configuración. Se guarda sobre el espacio seleccionado sin cambiar su nombre, descripción ni estado; también se puede preparar la configuración de un espacio inactivo. Los espacios anteriores conservan sus datos y aparecen con configuración pendiente hasta completarla. El listado resume aforo, horario y costo, y el formulario recupera las reglas y los valores guardados para editarlos.

La migración se aplica al reconstruir y arrancar Docker. El horario actual es el mismo todos los días; no incluye franjas por día, turnos nocturnos, reservas ni cobros.

```powershell
docker compose exec -T app php tests/docker-hu20.php
```

La prueba comprueba persistencia, edición, aislamiento entre espacios, validaciones y permisos con datos temporales que elimina al terminar.

### Activar o desactivar un espacio existente

Cada espacio del listado tiene un botón **Activar** o **Desactivar**, exclusivo del administrador activo. Desactivarlo requiere confirmar y conserva nombre, descripción, aforo, horario, costo y reglas. Se puede reactivar después sin volver a registrarlo ni configurarlo. Un espacio puede crearse inactivo para prepararlo antes de habilitar su uso o desactivarse temporalmente por mantenimiento. La consulta `EspacioComun::activos()` refleja los cambios de estado. Las pruebas de `docker-hu20.php` también verifican este flujo, sus permisos y la conservación de datos.

### Alertas de la interfaz

Las confirmaciones de activación y desactivación y los avisos de éxito o error usan SweetAlert2 11.26.25, con etiquetas en español y colores del sitio. Los errores junto a cada campo se mantienen. La biblioteca y su licencia MIT se incluyen en `public/lib/sweetalert2`, sin depender de una descarga externa en el navegador. El comportamiento compartido está en `public/js/alertas.js` y se integra mediante `components.alertas`. Si JavaScript no carga, los mensajes del servidor permanecen visibles.

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
