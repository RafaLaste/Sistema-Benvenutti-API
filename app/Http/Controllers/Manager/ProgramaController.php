<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

use App\Services\ProgramaService;

use App\Models\Participante;
use App\Models\Destino;

use Carbon\Carbon;

class ProgramaController extends Controller
{
    protected $programaService;

    public function __construct(ProgramaService $programaService)
    {
        $this->programaService = $programaService;
    }

    public function getStats()
    {
        $participantesTotal = Participante::query()
            ->where([
                'excluido' => NULL
            ])
            ->count();

        $participantesCompletosTotal = Participante::query()
            ->where([
                'excluido' => NULL,
                'etapa_cadastro' => 'concluido'
            ])
            ->count();

        $participantesConfirmadosTotal = Participante::query()
            ->where([
                'excluido' => NULL,
                'confirmado' => true
            ])
            ->count();

        $destinosParticipantes = Destino::query()
            ->where([
                'excluido' => NULL,
                'ano_vigente' => Carbon::now()->year
            ])
            ->withCount(['participantes' => function ($q) {
                $q->where([
                    'excluido' => NULL
                ]);
            }])
            ->get()
            ->map(function ($destino) {
                return [
                    'id' => $destino->id,
                    'destino' => $destino->destino,
                    'participantes_count' => $destino->participantes_count,
                ];
            });

        return response()->json([
            'participantesTotal' => $participantesTotal,
            'participantesCompletosTotal' => $participantesCompletosTotal,
            'participantesConfirmadosTotal' => $participantesConfirmadosTotal,
            'destinosParticipantes' => $destinosParticipantes
        ]);
    }

    public function getData()
    {
        $programa = Programa::where('excluido', NULL)->first();

        return response()->json([
            'programa' => $programa
        ]);
    }

    public function postData(Request $request)
    {
        $this->validate($request, [
            'nome_site' => 'required|string|max:255',
            'email_contato' => 'required|email:max:255',
            'telefone' => 'required|string|max:255',
            'cadastros_ativos' => 'boolean',
        ], [
            'nome_site.required' => 'Por favor, informe o nome do programa.',
            'email_contato.required' => 'Por favor, informe o e-mail de contato do programa.',
            'email_contato.email' => 'Por favor, informe um e-mail válido.',
            'telefone.required' => 'Por favor, informe o telefone de contato.',
            'telefone.required' => 'Por favor, informe seu telefone.',
            'telefone.celular_com_ddd' => 'Por favor, informe um telefone válido.',
            'mensagem.required' => 'Por favor, informe sua mensagem.',
        ]);

        try {
            $response = $this->programaService->atualizarDados($request->all());

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
