<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

use App\Services\ProgramaService;

use App\Models\Participante;
use App\Models\Programa;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class ProgramaController extends Controller
{
    protected $programaService;

    public function __construct(ProgramaService $programaService)
    {
        $this->programaService = $programaService;
    }

    public function getStats()
    {
        $ranking = Usuario::query()
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
            ->map(function ($usuario) {
                $pontos = $usuario->participante->pontos ?? collect();

                $totalPontos = $pontos->sum(function ($ponto) {
                    return $ponto->tipo === 'adicao'
                        ? $ponto->quantidade
                        : -$ponto->quantidade;
                });

                return [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome,
                    'pontos' => $totalPontos,
                ];
            })
            ->sortByDesc('pontos')
            ->take(10)
            ->values()
            ->map(function ($usuario, $index) {
                $usuario['posicao'] = $index + 1;
                return $usuario;
            });

        $participantesCadastradosNaSemana = Participante::query()
            ->whereNull('excluido')
            ->whereBetween('criado', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->count();


        $participantesCadastroFinalizado = Participante::query()
            ->where([
                'excluido' => NULL,
                'etapa_cadastro' => 'concluido'
            ])
            ->count();

        $participantesCadastroNaoFinalizado = Participante::query()
            ->where([
                'excluido' => NULL,
                'etapa_cadastro' => 'convidado'
            ])
            ->count();

        return response()->json([
            'ranking_top_10' => $ranking,
            'estatisticas' => [
                'cadastros_semana_atual' => $participantesCadastradosNaSemana,
                'cadastros_totais' => $participantesCadastroFinalizado + $participantesCadastroNaoFinalizado,
            ],
            'cadastros_nao_finalizados' => [
                ['tipo' => 'Não Finalizados', 'total' => $participantesCadastroNaoFinalizado],
                ['tipo' => 'Finalizados', 'total' => $participantesCadastroFinalizado]
            ]
        ]);
    }

    public function getData()
    {
        $programa = Programa::where('id', 1)->first();

        if (!$programa) {
            return response()->json([
                'error' => 'Programa não encontrado.'
            ], 404);
        }

        return response()->json([
            'programa' => [
                'titulo' => $programa->titulo,
                'descricao' => $programa->descricao,
                'data_inicio' => $programa->data_inicio,
                'data_final' => $programa->data_final,
                'regulamento' => $programa->regulamento
                    ? config('services.site.storage') . '/content/files/' . $programa->regulamento
                    : null,
            ]
        ]);
    }

    public function postData(Request $request)
    {
        $this->validate($request, [
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string|max:255',
            'data_inicio' => 'required|date_format:Y-m-d H:i:s',
            'data_final' => 'required|date_format:Y-m-d H:i:s|after:data_inicio',
            'arq' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ], [
            'titulo.required' => 'Por favor, informe o titulo do programa.',
            'titulo.string' => 'Valor inválido para o titulo.',
            'titulo.max' => 'O título deve ter no máximo 255 caracteres',
            'descricao.required' => 'Por favor, informe o descrição do programa.',
            'descricao.string' => 'Valor inválido para a descrição.',
            'descricao.max' => 'A descrição deve ter no máximo 255 caracteres',
            'data_inicio.required' => 'Por favor, informe a data de inicio do programa',
            'data_inicio.date_format' => 'O formato da data inicial é inválido',
            'data_final.required' => 'Por favor, informe a data do final do programa',
            'data_final.date_format' => 'O formato da data final é inválido',
            'data_final.after' => 'A data final precisa ser após a data inicial',
            'arq.file' => 'Valor inválido para o arquivo',
            'arq.mimes' => 'Os formatos aceitos para o arquivo são PDF, DOC e DOCX.',
            'arq.max' => 'O tamanho máximo de upload é 10MB.',
        ]);

        $dados = $request->only('titulo', 'descricao', 'data_inicio', 'data_final');

        $arquivo = $request->file('arq');

        try {
            $response = $this->programaService->cadastrarDados($dados, $arquivo);

            return response()->json([
                'success' => true,
                'message' => 'Dados alterados com sucesso.',
                'data' => $response
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar os dados',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
