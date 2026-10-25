# Revisión visual del frontend

Se revisaron 35 vistas: 15 de administración, 6 de conserjería, 9 de residente, el perfil compartido, las 3 pantallas de autenticación y el error 403. La referencia fue `code.html`, `screen.png` y la paleta de `DESIGN.md` del ZIP de prototipos proporcionado.

El frontend mantiene el lenguaje visual del prototipo: Inter, navegación lateral por rol, selección azul marino, acciones azules, fondo claro, tarjetas blancas, bordes suaves y estados semánticos. Los datos y las acciones corresponden al sistema real.

## Inconsistencias corregidas

| Problema encontrado | Resultado |
|---|---|
| Ocho vistas todavía tenían cabecera azul y documento HTML propios | Las siete vistas de incidencias y el mural del residente usan ahora el layout común y el menú activo del rol |
| Reglas globales pintaban de azul todos los botones y enlaces | Se respetan los colores de acciones principales, secundarias, aprobación y rechazo; se conserva el contraste del texto |
| Padrón de departamentos desbordaba la pantalla móvil | Columnas adaptables, filtros que se apilan y tabla que se desplaza dentro de su contenedor |
| Formularios de visitas y vehículos ensanchaban la página | Columnas de una sola pista en móvil e inputs que pueden reducir su ancho |
| Disponibilidad y reservas conservaban otro azul en estilos propios | Acciones y enlaces utilizan el color compartido |
| Modal de carga podía exceder una pantalla de poca altura | Ambos modales de comprobantes tienen altura máxima y desplazamiento interior; cuentan con nombre y rol de diálogo |
| Formularios de avisos repetían el identificador del contenido principal | Identificadores únicos y etiquetas vinculadas al campo correcto |
| El navegador podía conservar CSS anterior | La URL de la hoja de estilo incluye su versión de archivo |

Solo los layouts y el documento PDF conservan un documento HTML completo; las demás vistas heredan los layouts compartidos.

## Comprobaciones

- Navegación real por las pantallas de los tres roles, formularios, listados, detalles, edición, estados vacíos, recuperación y acceso denegado.
- Revisión adaptable de los módulos en 1440 × 960, 390 × 844 y 320 × 568. Los desbordamientos detectados se corrigieron y comprobaron de nuevo. Las tablas conservan desplazamiento horizontal local.
- Apertura del menú móvil y de los modales de carga y rechazo, sin enviar pagos ni modificar registros reales. El modal de carga se verificó también a 320 × 568.
- Suite `docker-front.php`: **181 PASS**. Incluye las vistas antes omitidas, unicidad de identificadores, navegación por rol, recursos y acceso.
- Suite `docker-mural-incidencias.php`: **48 PASS**.
- Suite `docker-comprobantes.php`: **42 PASS**.
- Total de las tres suites ejecutadas en esta revisión: **271 PASS, sin fallos**. Las 28 suites de la auditoría anterior no se repitieron completas en esta revisión visual.

Las pruebas se ejecutaron en Docker QA, con datos de prueba. Los ajustes están aplicados también a la instancia habitual.

## Evidencia

- [Incidencias con el layout común](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/revision-front/incidencias-final.png)
- [Padrón adaptado a móvil](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/revision-front/departamentos-movil-final.png)
- [Resultados de frontend](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/revision-front/pruebas-front.log)
- [Resultados de mural e incidencias](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/revision-front/pruebas-mural-incidencias.log)
- [Resultados de comprobantes](C:/Users/iturr/.codex/visualizations/2026/10/24/01a16fd4-1d09-79a3-9dc1-c0e6bc9a5b5c/revision-front/pruebas-comprobantes.log)

La revisión confirma coherencia visual y navegación en los escenarios comprobados. El prototipo mezcla roles: se mantuvo la separación de permisos del software y no se copió su barra para alternar pantallas de demostración.
