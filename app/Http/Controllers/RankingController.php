<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Http\Controllers\Controller;

class RankingController extends Controller
{
    public function __invoke()
    {
        $usuarioAutenticado = auth()->user();

        $participantes = Usuario::query()
            ->where([
                'excluido' => NULL,
                'funcao' => 'participante',
            ])
            ->with([
                'participante' => function ($q) {
                    $q->where('excluido', NULL)
                        ->with(['pontos' => function ($query) {
                            $query->where('excluido', NULL);
                        }]);
                },
                'logs' => function ($q) {
                    $q->latest('criado')
                        ->take(1);
                },
            ])
            ->get()
            ->map(function ($usuario) use ($usuarioAutenticado) {
                $pontos = $usuario->participante->pontos ?? collect();

                $totalPontos = $pontos->sum(function ($ponto) {
                    return $ponto->tipo === 'adicao'
                        ? $ponto->quantidade
                        : -$ponto->quantidade;
                });

                $isCurrent = $usuarioAutenticado->id === $usuario->id;

                return [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome ?? $usuario->email,
                    'pontos' => $totalPontos,
                    'isCurrent' => $isCurrent
                ];
            })
            ->sortByDesc('pontos')
            ->values()
            ->map(function ($usuario, $index) {
                $usuario['posicao'] = $index + 1;
                return $usuario;
            });

        $indexUsuario = $participantes->search(function ($item) use ($usuarioAutenticado) {
            return $item['id'] === $usuarioAutenticado->id;
        });

        $meio = floor(5 / 2);

        $inicio = max(0, $indexUsuario - $meio);
        $fim = $inicio + 5 - 1;

        if ($fim >= $participantes->count()) {
            $fim = $participantes->count() - 1;
            $inicio = max(0, $fim - 5 + 1);
        }

        $participantesAoRedor = $participantes->slice($inicio, $fim - $inicio + 1)->values();

        return response()->json([
            'participantes' => $participantesAoRedor,
        ]);
    }
}
