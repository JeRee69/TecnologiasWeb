<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <meta content="web_blank" name="shell-type"/>
  <title>Iniciar Sesión - EdiConnect</title>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
  <style>
    @layer base {
      html, body {
        margin: 0;
        padding: 0;
      }
      body {
        overscroll-behavior: none;
      }
      main > :first-child {
        margin-top: 0 !important;
      }
      main > :last-child {
        margin-bottom: 0 !important;
      }
    }
    ::-webkit-scrollbar {
      display: none;
    }
  </style>
  <script src="https://cdn.tailwindcss.com"></script>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
            "on-error": "#ffffff",
            "inverse-surface": "#303032",
            "on-surface-variant": "#45474c",
            "on-secondary-container": "#fefcff",
            "on-error-container": "#93000a",
            "on-secondary-fixed": "#001a42",
            "error-container": "#ffdad6",
            "on-surface": "#1b1b1d",
            "inverse-primary": "#bcc7de",
            "tertiary-container": "#35260c",
            "outline-variant": "#c5c6cd",
            "primary-fixed-dim": "#bcc7de",
            "on-tertiary-fixed-variant": "#564427",
            "on-tertiary-fixed": "#271902",
            "surface-dim": "#dcd9db",
            "surface-tint": "#545f73",
            "tertiary-fixed": "#fadfb8",
            "surface-bright": "#fbf8fa",
            "tertiary": "#1e1200",
            "surface": "#fbf8fa",
            "tertiary-fixed-dim": "#ddc39d",
            "on-primary-fixed": "#111c2d",
            "primary-container": "#1e293b",
            "on-primary-container": "#8590a6",
            "error": "#ba1a1a",
            "secondary-fixed": "#d8e2ff",
            "on-background": "#1b1b1d",
            "surface-variant": "#e4e2e3",
            "outline": "#75777d",
            "secondary": "#0058be",
            "on-primary-fixed-variant": "#3c475a",
            "on-secondary-fixed-variant": "#004395",
            "surface-container-highest": "#e4e2e3",
            "secondary-container": "#2170e4",
            "inverse-on-surface": "#f3f0f2",
            "primary-fixed": "#d8e3fb",
            "surface-container-low": "#f5f3f4",
            "on-tertiary-container": "#a38c6a",
            "on-primary": "#ffffff",
            "secondary-fixed-dim": "#adc6ff",
            "surface-container": "#f0edef",
            "surface-container-high": "#eae7e9",
            "primary": "#091426",
            "on-tertiary": "#ffffff",
            "surface-container-lowest": "#ffffff",
            "background": "#fbf8fa",
            "on-secondary": "#ffffff"
          },
          "borderRadius": {
            "DEFAULT": "0.25rem",
            "lg": "0.5rem",
            "xl": "0.75rem",
            "full": "9999px"
          },
          "spacing": {
            "lg": "24px",
            "margin-desktop": "48px",
            "gutter": "24px",
            "sm": "8px",
            "xs": "4px",
            "md": "16px",
            "margin-mobile": "16px",
            "base": "4px",
            "xl": "32px"
          },
          "fontFamily": {
            "label-md": ["Inter"],
            "title-lg": ["Inter"],
            "body-lg": ["Inter"],
            "headline-lg-mobile": ["Inter"],
            "body-md": ["Inter"],
            "headline-lg": ["Inter"],
            "headline-md": ["Inter"],
            "display-lg": ["Inter"]
          },
          "fontSize": {
            "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.02em", "fontWeight": "600" }],
            "title-lg": ["18px", { "lineHeight": "24px", "fontWeight": "600" }],
            "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
            "headline-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "600" }],
            "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
            "headline-lg": ["28px", { "lineHeight": "36px", "letterSpacing": "-0.01em", "fontWeight": "600" }],
            "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
            "display-lg": ["36px", { "lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700" }]
          }
        }
      }
    };
  </script>
</head>
<body class="bg-background font-body-md text-on-surface antialiased min-h-screen flex items-center justify-center">
  <main class="w-full bg-background">
    <div class="flex flex-col w-full">
      <div class="w-full flex items-center justify-center p-sm md:p-lg relative overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-secondary-fixed/30 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-surface-container-high/60 blur-3xl pointer-events-none"></div>
        <div class="relative w-full max-w-[480px] bg-surface-container-lowest rounded-xl shadow-xl p-md md:p-lg flex flex-col gap-md z-10">
          
          <div class="flex flex-col items-center text-center gap-xs">
            <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center text-on-primary shadow-md mb-xs">
              <span class="material-symbols-outlined text-[26px]">apartment</span>
            </div>
            <div class="flex items-center gap-xs">
              <span class="font-headline-md text-headline-md tracking-tight text-on-surface">EdiConnect</span>
              <span class="font-label-md text-label-md bg-secondary-fixed text-on-secondary-fixed px-xs py-0.5 rounded-full">Portal</span>
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface mt-sm">Iniciar Sesión</h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-xs">
              Ingresa tus credenciales para acceder a EdiConnect
            </p>
          </div>

          {{-- Alertas de estado y mensajes de error según criterios de aceptación HU-01 --}}
          @if (session('estado'))
            <div class="p-sm bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-body-md flex items-center gap-xs">
              <span class="material-symbols-outlined text-[20px] text-emerald-600">check_circle</span>
              <span>{{ session('estado') }}</span>
            </div>
          @endif

          @if ($errors->any())
            <div class="p-sm bg-red-50 border border-red-200 text-red-700 rounded-lg text-body-md flex items-center gap-xs">
              <span class="material-symbols-outlined text-[20px] text-red-600">error</span>
              <div class="flex flex-col">
                @foreach ($errors->all() as $error)
                  <span>{{ $error }}</span>
                @endforeach
              </div>
            </div>
          @endif

          <form class="flex flex-col gap-md mt-xs" id="login-form" action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="flex flex-col gap-xs">
              <label class="font-label-md text-label-md text-on-surface" for="identifier">
                Correo electrónico o RUT
              </label>
              <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-md text-outline text-[20px] pointer-events-none">
                  badge
                </span>
                <input class="w-full bg-surface-container-low text-on-surface font-body-md text-body-md rounded-lg pl-11 pr-md py-sm focus:outline-none focus:bg-surface-container-lowest transition-colors border border-transparent focus:border-secondary" 
                       id="identifier" 
                       name="identifier" 
                       value="{{ old('identifier') }}" 
                       placeholder="ejemplo@correo.cl o 12.345.678-9" 
                       required 
                       type="text"/>
              </div>
            </div>

            <div class="flex flex-col gap-xs">
              <div class="flex items-center justify-between">
                <label class="font-label-md text-label-md text-on-surface" for="password">
                  Contraseña
                </label>
                <a class="font-label-md text-label-md text-secondary hover:text-on-secondary-fixed transition-colors" 
                   href="{{ route('password.request') }}">
                  ¿Olvidaste tu contraseña?
                </a>
              </div>
              <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-md text-outline text-[20px] pointer-events-none">
                  lock
                </span>
                <input class="w-full bg-surface-container-low text-on-surface font-body-md text-body-md rounded-lg pl-11 pr-11 py-sm focus:outline-none focus:bg-surface-container-lowest transition-colors border border-transparent focus:border-secondary" 
                       id="password" 
                       name="password" 
                       placeholder="••••••••••••" 
                       required 
                       type="password"/>
                <button aria-label="Ver u ocultar contraseña" 
                        class="absolute right-md text-outline hover:text-on-surface flex items-center justify-center transition-colors" 
                        id="toggle-password" 
                        onclick="togglePasswordVisibility()" 
                        type="button">
                  <span class="material-symbols-outlined text-[20px]" id="eye-icon">visibility</span>
                </button>
              </div>
            </div>

            <div class="flex items-center justify-between pt-xs">
              <label class="flex items-center gap-sm cursor-pointer select-none">
                <input class="w-4 h-4 rounded text-secondary accent-secondary cursor-pointer" 
                       id="remember" 
                       name="remember" 
                       type="checkbox" 
                       {{ old('remember') ? 'checked' : '' }}/>
                <span class="font-body-md text-body-md text-on-surface-variant">
                  Recordarme en este equipo
                </span>
              </label>
            </div>

            <button class="w-full bg-secondary hover:bg-on-secondary-fixed text-on-secondary font-label-md text-label-md py-md rounded-lg transition-all flex items-center justify-center gap-xs shadow-sm mt-xs cursor-pointer" 
                    id="submit-btn" 
                    type="submit">
              <span>Ingresar a mi cuenta</span>
              <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </button>
          </form>

          <div class="pt-sm mt-xs flex flex-col items-center gap-xs text-center">
            <p class="font-body-md text-body-md text-on-surface-variant flex items-center gap-xs">
              <span class="material-symbols-outlined text-[16px] text-outline">support_agent</span>
              ¿Problemas para acceder?
              <a class="font-label-md text-label-md text-secondary hover:underline" href="#">
                Soporte técnico
              </a>
            </p>
            <span class="font-label-md text-label-md text-outline">
              © 2026 EdiConnect. Todos los derechos reservados.
            </span>
          </div>

        </div>
      </div>
    </div>

    <script>
      function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        
        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          eyeIcon.textContent = 'visibility_off';
        } else {
          passwordInput.type = 'password';
          eyeIcon.textContent = 'visibility';
        }
      }
    </script>
  </main>
</body>
</html>
