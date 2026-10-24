<section class="ui-card module-card">
  <span class="material-symbols-outlined" aria-hidden="true">{{ $icono }}</span>
  <h2>{{ $titulo }}</h2>
  <p>{{ $descripcion }}</p>
  <a class="ui-link" href="{{ route($destino) }}">{{ $accion }} <span aria-hidden="true">→</span></a>
</section>
