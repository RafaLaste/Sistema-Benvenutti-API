<?php

namespace App\Services;

use App\Models\Edicao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EdicaoService
{
    public function cadastrarEdicao($request)
    {
        DB::beginTransaction();

        try {
            $edicao = Edicao::create([
                'destino' => $request['destino'],
                'ano' => $request['ano']
            ]);

            DB::commit();

            return [
                'edicao' => [
                    'id' => $edicao->id,
                    'destino' => $edicao->destino,
                    'ano' => $edicao->ano
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarEdicao($request, $id)
    {
        DB::beginTransaction();

        try {
            $edicao = Edicao::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$edicao) {
                throw new \Exception('Edicao não encontrado!');
            }

            $edicao->update([
                'destino' => $request['destino'],
                'ano' => $request['ano']
            ]);

            DB::commit();

            return [
                'edicao' => $edicao,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function excluirEdicao($id)
    {
        DB::beginTransaction();

        try {
            $edicao = Edicao::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$edicao) {
                throw new \Exception('Edicao não encontrada!');
            }

            $edicao->excluido = Carbon::now();
            $edicao->save();

            DB::commit();

            return [
                'edicao' => $edicao,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
}
