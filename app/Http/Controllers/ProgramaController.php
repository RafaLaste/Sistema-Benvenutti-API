<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Programa;
use Illuminate\Support\Carbon;

class ProgramaController extends Controller
{
    public function __invoke()
    {
        $programa = Programa::first();

        if (!$programa) {
            return response()->json([
                'error' => 'Programa não encontrado.'
            ], 404);
        }

        $cadastrosAtivos = Carbon::now()->between(
            Carbon::parse($programa->data_inicio),
            Carbon::parse($programa->data_final)
        );

        return response()->json([
            'programa' => [
                'titulo' => $programa->titulo,
                'descricao' => $programa->descricao,
                'data_inicio' => $programa->data_inicio,
                'data_final' => $programa->data_final,
            ],
            'cadastros_ativos' => $cadastrosAtivos
        ]);
    }
}
