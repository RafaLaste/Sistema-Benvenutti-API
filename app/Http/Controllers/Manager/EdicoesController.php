<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Edicao;
use App\Services\EdicaoService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class EdicoesController extends Controller
{
    protected $edicaoService;

    public function __construct(EdicaoService $edicaoService)
    {
        $this->edicaoService = $edicaoService;
    }

    public function getEdicoes()
    {
        $edicao = Edicao::query()
            ->where([
                'excluido' => NULL,
            ])
            ->orderBy('ano', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($edicao) {
                return [
                    'id' => $edicao->id,
                    'nome' => $edicao->destino . '-' . $edicao->ano,
                ];
            });

        return response()->json([
            'edicoes' => $edicao

        ]);
    }

    public function getEdicao($id)
    {
        $edicao = Edicao::query()
            ->where([
                'id' => $id,
                'excluido' => NULL,
            ])
            ->first();

        $edicaoData = [
            'id' => $edicao->id,
            'destino' => $edicao->destino,
            'ano' => $edicao->ano
        ];

        return response()->json([
            'edicao' => $edicaoData
        ]);
    }

    public function createEdicao(Request $request)
    {
        $anoAtual = date('Y');

        $this->validate($request, [
            'destino' => 'required|max:100',
            'ano' =>  'required|integer|min:1900|max:' . $anoAtual
        ], [
            'destino.required' => 'Por favor, informe o destino.',
            'destino.max' => 'O destino deve ter no máximo 100 caracteres.',
            'ano.required' => 'Por favor, informe o ano.',
            'ano.integer' => 'Valor inválido para o ano.',
            'ano.min' => 'O ano deve ser no mínimo de 1900.',
            'ano.max' => 'O ano deve ser no máximo de atualmente: ' . $anoAtual
        ]);

        $dados = $request->only(['ano', 'destino']);

        try {
            $response = $this->edicaoService->cadastrarEdicao($dados);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Edicao criada com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar edicao',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateEdicao(Request $request, $id)
    {
        $anoAtual = date('Y');

        $this->validate($request, [
            'destino' => 'required|max:100',
            'ano' =>  'required|integer|min:1900|max:' . $anoAtual
        ], [
            'destino.required' => 'Por favor, informe o destino.',
            'destino.max' => 'O destino deve ter no máximo 100 caracteres.',
            'ano.required' => 'Por favor, informe o ano.',
            'ano.integer' => 'Valor inválido para o ano.',
            'ano.min' => 'O ano deve ser no mínimo de 1900.',
            'ano.max' => 'O ano deve ser no máximo de atualmente: ' . $anoAtual
        ]);

        $dados = $request->only(['ano', 'destino']);

        try {
            $response = $this->edicaoService->atualizarEdicao($dados, $id);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Edicao atualizada com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar edicao',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteEdicao($id)
    {
        try {
            $this->edicaoService->excluirEdicao($id);

            return response()->json([
                'success' => true,
                'message' => 'Edicao excluida com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir edicao.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
