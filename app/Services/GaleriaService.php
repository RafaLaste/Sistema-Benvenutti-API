<?php

namespace App\Services;

use App\Models\Galeria;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GaleriaService
{
    public function cadastrarGaleria($request)
    {
        DB::beginTransaction();

        try {
            $galeria = Galeria::create([
                'destino' => $request['destino'],
                'ano' => $request['ano']
            ]);

            DB::commit();

            return [
                'galeria' => [
                    'id' => $galeria->id,
                    'destino' => $galeria->destino,
                    'ano' => $galeria->ano
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarGaleria($request, $id)
    {
        DB::beginTransaction();

        try {
            $galeria = Galeria::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$galeria) {
                throw new \Exception('Galeria não encontrado!');
            }

            $galeria->update([
                'destino' => $request['destino'],
                'ano' => $request['ano']
            ]);

            DB::commit();

            return [
                'galeria' => $galeria,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function excluirGaleria($id)
    {
        DB::beginTransaction();

        try {
            $galeria = Galeria::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$galeria) {
                throw new \Exception('Galeria não encontrada!');
            }

            $galeria->excluido = Carbon::now();
            $galeria->save();

            DB::commit();

            return [
                'galeria' => $galeria,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
}
