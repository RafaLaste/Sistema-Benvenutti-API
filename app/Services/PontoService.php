<?php

namespace App\Services;

use App\Models\Ponto;
use App\Models\Participante;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PontoService
{
    public function novoPonto($dadosPonto, $participanteId, $tipo)
    {
        DB::beginTransaction();

        try {
            $participante = Participante::query()
                ->where([
                    'usuario_id' => $participanteId,
                    'excluido'   => NULL,
                ])
                ->with('usuario', function ($q) {
                    $q->where('excluido', NULL);
                })
                ->first();

            if (!$participante) {
                throw new \Exception('Participante não encontrado.');
            }

            $ponto = Ponto::create([
                'quantidade' => $dadosPonto['quantidade'],
                'descricao' => $dadosPonto['descricao'] ?? null,
                'tipo' => $tipo,
                'categoria' => $dadosPonto['categoria'] ?? null,
                'participante_id' => $participante->id,
            ]);

            DB::commit();

            return $ponto;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao inserir pontuação: ' . $e->getMessage());
        }
    }

    public function atualizarPonto($dadosPonto, $id)
    {
        DB::beginTransaction();

        try {
            $ponto = Ponto::query()
                ->where([
                    'id' => $id,
                    'excluido' => NULL,
                ])
                ->first();

            if (!$ponto) {
                throw new \Exception('Pontuação não encontrada.');
            }

            $ponto->update([
                'quantidade' => $dadosPonto['quantidade'],
                'descricao' => $dadosPonto['descricao'] ?? null,
            ]);

            DB::commit();

            return $ponto;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao atualizar pontuação: ' . $e->getMessage());
        }
    }

    public function excluirPontos($ids)
    {
        DB::beginTransaction();

        try {
            $response = Ponto::query()
                ->where('excluido', NULL)
                ->whereIn('id', $ids)
                ->update([
                    'excluido' => Carbon::now(),
                ]);

            if ($response === 0) {
                throw new \Exception('Nenhuma pontuação encontrada para exclusão.');
            }

            DB::commit();

            return [
                'excluidos' => $ids,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao excluir pontuações: ' . $e->getMessage());
        }
    }
}
