<?php

namespace App\Services;

use Carbon\Carbon;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfGeradorService
{
    public function gerar(string $view, string $html, string $titulo)
    {
        $conteudo = view($view, [
            'dataGeracao' => Carbon::now()->format('d/m/Y H:i'),
            'titulo' => $titulo,
            'regulamento' => $html
        ])->render();

        $pdf = Pdf::loadHTML($conteudo);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }
}
