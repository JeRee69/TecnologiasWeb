<!DOCTYPE html>
<html lang="es">
<head>
  @include('layouts.assets')
  <title>@yield('title') - EdiConnect</title>
  @stack('styles')
</head>
<body class="auth-body"><div id="contenido" class="auth-content">@yield('content')</div>
@include('components.alertas')
@stack('scripts')
</body>
</html>
