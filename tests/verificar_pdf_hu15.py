"""Verifica los PDFs descargados por docker-hu15.php y genera PNGs para revisión.

Uso: python tests/verificar_pdf_hu15.py tmp/pdfs/hu15 [ruta-a-pdftoppm]
Requiere pypdf, pdfplumber y Poppler, disponibles en el runtime de Codex.
"""
from pathlib import Path
import sys
import subprocess
import pdfplumber
from pypdf import PdfReader

folder = Path(sys.argv[1])
renderer = sys.argv[2] if len(sys.argv) > 2 else 'pdftoppm'
for name, state in [('pendiente', 'Pendiente'), ('unidad-inactiva', 'Pendiente'), ('moroso', 'Moroso'), ('pagado', 'Al día (Pagado)')]:
    reader = PdfReader(folder / f'{name}.pdf')
    text = '\n'.join(page.extract_text() for page in reader.pages)
    assert len(reader.pages) == 1, (name, 'debe ocupar una página')
    for expected in ['03/2096', 'Á<101>', 'Fondo de reserva', 'Mantención de ascensores', 'Consumos básicos <script>alert(1)</script>', '$15.000', '$30.000', '$40.000', '$85.000', state, 'Página 1 de 1']:
        assert expected in text, (name, expected, text)
    assert 'PRIVADO-' not in text and 'REFERENCIA-INTERNA-HU15' not in text
    assert not reader.get_fields()
    assert '/JavaScript' not in str(reader.trailer['/Root'])
    print(f'PASS: contenido y estado del PDF {name}')

text = '\n'.join(p.extract_text() for p in PdfReader(folder / 'sin-desglose.pdf').pages)
assert 'Desglose aún no registrado' in text and '$1.000' in text
reader = PdfReader(folder / 'desglose-largo.pdf')
text = '\n'.join(p.extract_text() for p in reader.pages)
assert len(reader.pages) >= 2
for i in range(1, 51):
    assert f'Concepto {i:02d}:' in text
assert '$50.000' in text
for i, page in enumerate(reader.pages, 1):
    assert f'Página {i} de {len(reader.pages)}' in page.extract_text()
print(f'PASS: 50 conceptos completos y total en {len(reader.pages)} páginas')

for name in ['pendiente', 'pagado', 'sin-desglose', 'desglose-largo']:
    subprocess.run([renderer, '-r', '90', '-png', str(folder / f'{name}.pdf'), str(folder / name)], check=True)
    with pdfplumber.open(folder / f'{name}.pdf') as document:
        for i, page in enumerate(document.pages, 1):
            # El texto de contenido debe quedar dentro del papel y antes del pie.
            for char in page.chars:
                assert char['x0'] >= 35 and char['x1'] <= page.width - 35, (name, i, char)
                assert char['top'] >= 30 and char['bottom'] <= page.height - 20, (name, i, char)
print('HU-15: contenido, paginación y límites verificados; PNGs listos para revisión visual.')
