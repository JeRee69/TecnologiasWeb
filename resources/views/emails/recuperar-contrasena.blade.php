<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Recuperar contraseña</title></head>
<body style="margin:0;background:#f5f7fb;font-family:Arial,sans-serif;color:#172033;">
  <main style="max-width:560px;margin:32px auto;padding:32px;background:#ffffff;border-radius:12px;">
    <p style="font-size:22px;font-weight:bold;color:#001a42;">EdiConnect</p>
    <h1 style="font-size:24px;">Restablece tu contraseña</h1>
    <p>Hola, {{ $nombre }}:</p>
    <p>Recibimos una solicitud para recuperar el acceso a tu cuenta. Este enlace vence en {{ $minutosVigencia }} minutos y solo puede utilizarse una vez.</p>
    <p style="margin:28px 0;"><a href="{{ $enlace }}" style="display:inline-block;background:#0058be;color:#ffffff;padding:14px 20px;border-radius:8px;text-decoration:none;font-weight:bold;">Restablecer contraseña</a></p>
    <p>Si el botón no funciona, copia este enlace en tu navegador:</p>
    <p style="overflow-wrap:anywhere;"><a href="{{ $enlace }}">{{ $enlace }}</a></p>
    <p>Si no solicitaste el cambio, puedes ignorar este correo. Tu contraseña seguirá siendo la misma.</p>
  </main>
</body>
</html>
