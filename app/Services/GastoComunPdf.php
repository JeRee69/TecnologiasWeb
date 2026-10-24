<?php

namespace App\Services;

use App\Models\GastoComun;
use Dompdf\Dompdf;
use Dompdf\Options;

class GastoComunPdf
{
    public function generar(GastoComun $gasto): string
    {
        $opciones = new Options;
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('isPhpEnabled', false);
        $opciones->set('isJavascriptEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');
        $opciones->set('chroot', resource_path('views/pdf'));
        $pdf = new Dompdf($opciones);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view('pdf.gasto-comun', [
            'gasto' => $gasto, 'emitido' => now('America/Santiago'), 'estado' => $gasto->estadoVigente(),
        ])->render(), 'UTF-8');
        $pdf->render();
        $pdf->getCanvas()->page_text(42, 805, 'EdiConnect | Página {PAGE_NUM} de {PAGE_COUNT}', null, 8, [0.4, 0.45, 0.5]);

        return $pdf->output();
    }
}
