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
