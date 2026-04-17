<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Support\Carbon;

class RelatoriosController extends Controller
{
    public function __invoke()
    {
        $participantes = Usuario::query()
            ->whereNull('excluido')
            ->where('funcao', 'participante')
            ->whereHas('participante', function ($q) {
                $q->whereNull('excluido')
                    ->where('etapa_cadastro', '!=', 'convidado');
            })
            ->with([
                'participante',
                'logs' => function ($q) {
                    $q->latest('criado')->take(1);
                },
            ])
            ->orderBy('nome', 'ASC')
            ->get()
            ->map(function ($usuario) {

                return [
                    'nome' => $usuario->nome,
                    'cpf' => vsprintf("%s%s%s.%s%s%s.%s%s%s-%s%s", str_split($usuario->participante->cpf)),
                    'data_nascimento' => Carbon::parse($usuario->participante->data_nascimento)->format('d/m/Y'),
                    'fone_celular' => $usuario->participante->fone_celular,
                    'restricao_alimentar' => $usuario->participante->restricao_alimentar_qual,
                    'limitacao' => $usuario->participante->limitacao_qual,
                    'medicamento' => $usuario->participante->medicamento_qual,
                    'problema_saude' => $usuario->participante->problema_saude_qual,
                    'etapa_cadastro' => $usuario->participante->etapa_cadastro
                ];
            });

        return response()->json([
            'participantes' => $participantes,
        ]);
    }
}
