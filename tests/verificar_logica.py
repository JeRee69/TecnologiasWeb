import hashlib
import time
import secrets

usuarios_db = {
    'admin@ediconnect.cl': {
        'id': 1,
        'nombre': 'Administrador Principal',
        'pass_hash': hashlib.sha256('Password123!'.encode()).hexdigest(),
        'rol': 'Administrador',
        'estado': 'activo',
    },
    'conserje@ediconnect.cl': {
        'id': 2,
        'nombre': 'Juan Conserje',
        'pass_hash': hashlib.sha256('Password123!'.encode()).hexdigest(),
        'rol': 'Conserje',
        'estado': 'activo',
    },
    'residente@ediconnect.cl': {
        'id': 3,
        'nombre': 'María Residente',
        'pass_hash': hashlib.sha256('Password123!'.encode()).hexdigest(),
        'rol': 'Residente',
        'estado': 'activo',
    },
    'inactivo@ediconnect.cl': {
        'id': 4,
        'nombre': 'Usuario Inactivo',
        'pass_hash': hashlib.sha256('Password123!'.encode()).hexdigest(),
        'rol': 'Residente',
        'estado': 'inactivo',
    },
}

tokens_db = {}

def autenticar(email, password):
    user = usuarios_db.get(email)
    if not user:
        return False, 'Credenciales inválidas', None
    hashed = hashlib.sha256(password.encode()).hexdigest()
    if user['pass_hash'] != hashed:
        return False, 'Credenciales inválidas', None
    if user['estado'] != 'activo':
        return False, 'La cuenta se encuentra inactiva. Contacta al administrador.', None
    
    rol = user['rol'].lower()
    rutas = {
        'administrador': '/admin/dashboard',
        'conserje': '/conserje/dashboard',
        'residente': '/residente/dashboard',
    }
    return True, 'Acceso exitoso', rutas.get(rol, '/login')

def verificar_rbac(usuario, ruta):
    rol = usuario['rol'].lower()
    if ruta.startswith('/admin') and rol != 'administrador':
        return 403, 'Acceso denegado: rol no autorizado'
    if ruta.startswith('/conserje') and rol != 'conserje':
        return 403, 'Acceso denegado: rol no autorizado'
    if ruta.startswith('/residente') and rol != 'residente':
        return 403, 'Acceso denegado: rol no autorizado'
    return 200, 'Permitido'

def solicitar_recuperacion(email):
    user = usuarios_db.get(email)
    if not user:
        return False, 'Correo no registrado'
    token = secrets.token_hex(16)
    tokens_db[email] = {'token': token, 'expira': time.time() + 3600}
    return True, f'/restablecer-contrasena/{token}?email={email}'

def restablecer_clave(email, token, nueva_clave):
    reg = tokens_db.get(email)
    if not reg or reg['token'] != token:
        return False, 'Token inválido'
    if time.time() > reg['expira']:
        return False, 'Token expirado'
    usuarios_db[email]['pass_hash'] = hashlib.sha256(nueva_clave.encode()).hexdigest()
    del tokens_db[email]
    return True, 'Contraseña actualizada'

if __name__ == '__main__':
    print('========================================')
    print('  EJECUTANDO VERIFICACIÓN DE REQUISITOS ')
    print('========================================\n')

    # HU-01
    print('Pruebas HU-01 (Inicio de Sesión):')
    ok, msg, ruta = autenticar('admin@ediconnect.cl', 'Password123!')
    assert ok and ruta == '/admin/dashboard', 'Fallo al autenticar Administrador'
    print(' [PASS] Escenario 1 (Admin): Redirige a /admin/dashboard')

    ok, msg, ruta = autenticar('conserje@ediconnect.cl', 'Password123!')
    assert ok and ruta == '/conserje/dashboard', 'Fallo al autenticar Conserje'
    print(' [PASS] Escenario 1 (Conserje): Redirige a /conserje/dashboard')

    ok, msg, ruta = autenticar('residente@ediconnect.cl', 'Password123!')
    assert ok and ruta == '/residente/dashboard', 'Fallo al autenticar Residente'
    print(' [PASS] Escenario 1 (Residente): Redirige a /residente/dashboard')

    ok, msg, ruta = autenticar('residente@ediconnect.cl', 'ClaveErronea')
    assert not ok and msg == 'Credenciales inválidas', 'Fallo en validación de credenciales inválidas'
    print(' [PASS] Escenario 2 (Contraseña errónea): Muestra "Credenciales inválidas"')

    ok, msg, ruta = autenticar('inactivo@ediconnect.cl', 'Password123!')
    assert not ok and 'inactiva' in msg, 'Fallo en cuenta inactiva'
    print(' [PASS] Escenario 2 (Cuenta inactiva): Muestra bloqueo por cuenta desactivada')

    # HU-02
    print('\nPruebas HU-02 (Control de Acceso RBAC):')
    code, msg = verificar_rbac(usuarios_db['residente@ediconnect.cl'], '/admin/dashboard')
    assert code == 403, 'Residente no debe acceder a dashboard admin'
    print(' [PASS] Residente intenta acceder a /admin/dashboard: Retorna HTTP 403 (Bloqueado)')

    code, msg = verificar_rbac(usuarios_db['conserje@ediconnect.cl'], '/admin/dashboard')
    assert code == 403, 'Conserje no debe acceder a dashboard admin'
    print(' [PASS] Conserje intenta acceder a /admin/dashboard: Retorna HTTP 403 (Bloqueado)')

    code, msg = verificar_rbac(usuarios_db['admin@ediconnect.cl'], '/admin/dashboard')
    assert code == 200, 'Administrador debe acceder a dashboard admin'
    print(' [PASS] Administrador accede a /admin/dashboard: Retorna HTTP 200 (Autorizado)')

    # HU-38
    print('\nPruebas HU-38 (Recuperación de Contraseña):')
    ok, url = solicitar_recuperacion('residente@ediconnect.cl')
    assert ok and '/restablecer-contrasena/' in url, 'Fallo al generar token de recuperación'
    print(f' [PASS] Generación de token y URL temporal: {url}')

    token = url.split('/restablecer-contrasena/')[1].split('?')[0]
    ok, msg = restablecer_clave('residente@ediconnect.cl', token, 'NuevaClaveSegura2026!')
    assert ok, 'Fallo al restablecer contraseña'
    print(' [PASS] Contraseña actualizada y token invalidado exitosamente')

    ok, msg, ruta = autenticar('residente@ediconnect.cl', 'NuevaClaveSegura2026!')
    assert ok, 'Fallo al iniciar sesión con la nueva contraseña'
    print(' [PASS] Inicio de sesión exitoso con la nueva clave')

    print('\n========================================')
    print('  RESULTADO: 100% DE LAS PRUEBAS PASADAS')
    print('========================================')
