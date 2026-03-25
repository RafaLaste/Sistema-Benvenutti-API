<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

use App\Models\Usuario;
use App\Models\Ponto;

use App\Services\ScoreService;

use Illuminate\Http\Request;
use Carbon\Carbon;

class PontosController extends Controller
{
    protected $scoreService;

    public function __construct(ScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    public function createPonto(Request $request, $id, $tipo) {
        $this->validate($request, [
            'quantidade' => 'required|numeric|min:0|max:2000000',
            'descricao' => 'nullable'
        ], [
            'quantidade.required' => 'Por favor, informe a quantidade de pontos.',
            'quantidade.numeric' => 'A quantidade precisa ser um número.',
            'quantidade.min' => 'A quantidade não pode ser negativa.',
            'quantidade.max' => 'A quantidade não pode ser maior que 2 milhões.',
        ]);

        $dadosPonto = $request->only(['quantidade', 'descricao']);

        try {
            $response = $this->scoreService->novoPonto($dadosPonto, $id, $tipo);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Pontuação inserida com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao inserir pontuação.',
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

    public function updatePonto(Request $request, $id) {
        $ponto = Ponto::query()
            ->where([
                'id' => $id,
                'excluido' => NULL
            ])
            ->first();

        if (!$ponto) {
            return response()->json([
                'error' => 'Pontuação não encontrada.'
            ], 404);
        }
        
        $this->validate($request, [
            'quantidade' => 'required|numeric|min:0|max:2000000',
            'descricao' => 'nullable'
        ], [
            'quantidade.required' => 'Por favor, informe a quantidade de pontos.',
            'quantidade.numeric' => 'A quantidade precisa ser um número.',
            'quantidade.min' => 'A quantidade não pode ser negativa.',
            'quantidade.max' => 'A quantidade não pode ser maior que 2 milhões.',
        ]);

        $dadosPonto = $request->only(['quantidade', 'descricao']);

        try {
            $response = $this->scoreService->atualizarPonto($dadosPonto, $id);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Pontuação atualizada com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar a pontuação.',
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

    public function deletePontos($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->scoreService->excluirPontos($explodeIds);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Pontuações excluidas com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir pontuações.',
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