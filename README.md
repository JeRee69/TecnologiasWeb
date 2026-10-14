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
