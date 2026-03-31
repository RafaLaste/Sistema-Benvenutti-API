<?php

namespace App\Services;

use App\Models\Foto;
use App\Models\Galeria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImagemService
{
    public function cadastrarFoto($request, $idGaleria)
    {
        DB::beginTransaction();

        try {
            $imagem = md5(uniqid(rand(), true)) . '.' . strtolower($request['imagem']->extension());

            $galeria = Galeria::query()
                ->where([
                    'id' => $idGaleria,
                    'excluido' => NULL,
                ])
                ->first();

            if (!$galeria) {
                throw new \Exception('Galeria não encontrado.');
            }

            $ultimaOrdem = Foto::query()
                ->where([
                    'galeria_id' => $idGaleria,
                    'excluido' => NULL,
                ])
                ->max('ordem');


            $ordem = $ultimaOrdem ? $ultimaOrdem + 1 : 1;

            $foto = Foto::create([
                'ordem' => $request['ordem'] ?? $ordem,
                'visivel' => $request['visivel'] ?? true,
                'imagem' => $imagem,
                'galeria_id' => $idGaleria
            ]);

            DB::commit();

            $request['imagem']->move(base_path('../intranet-media/uploads/slides/d/'), $imagem);

            return [
                'fotos' => [
                    'id' => $foto->id,
                    'imagem' => $foto->imagem,
                    'ordem' => $foto->ordem,
                    'visivel' => $foto->visivel,
                    'galeria_id' => $idGaleria
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarFoto($request, $idFoto)
    {
        DB::beginTransaction();

        try {
            $foto = Foto::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $idFoto,
                ])
                ->first();

            if (!$foto) {
                throw new \Exception('Foto não encontrada!');
            }

            $dadosUpdate = [
                'visivel' => $request['visivel'] ?? $foto->visivel,
                'ordem' => $request['ordem'] ?? $foto->ordem,
            ];

            if (!empty($request['imagem'])) {
                $imagem = md5(uniqid(rand(), true)) . '.' . strtolower($request['imagem']->extension());

                $request['imagem']->move(base_path('../intranet-media/uploads/slides/d/'), $imagem);

                $dadosUpdate['imagem'] = $imagem;
            }

            $foto->update($dadosUpdate);

            DB::commit();

            return $foto;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarOrdem($request)
    {
        DB::beginTransaction();

        try {
            foreach ($request as $odr) {
                if (!isset($odr['id']) || !isset($odr['ordem'])) {
                    throw new \Exception('O formato do request está inválido. É necessário um campo id e ordem.');
                }

                $foto = Foto::query()
                    ->where([
                        'excluido' => NULL,
                        'id' => $odr['id']
                    ])
                    ->first();

                if (!$foto) {
                    throw new \Exception("Registro com ID {$odr['id']} não encontrado!");
                }

                $foto->update([
                    'ordem' => $odr['ordem'],
                ]);
            }

            DB::commit();

            return [
                'fotos' => 'Ordem atualizada com sucesso!',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarVisibilidade($request, $idFoto)
    {
        DB::beginTransaction();

        try {
            $foto = Foto::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $idFoto,
                ])
                ->first();

            if (!$foto) {
                throw new \Exception('Foto não encontrada!');
            }

            $foto->update([
                'visivel' => $request['visivel'],
            ]);

            DB::commit();

            return  $foto;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function excluirFoto($id)
    {
        DB::beginTransaction();

        try {
            $foto = Foto::query()
                ->where([
                    'excluido' => NULL,
                    'id' => $id,
                ])
                ->first();

            if (!$foto) {
                throw new \Exception('Foto não encontrada!');
            }

            $foto->excluido = Carbon::now();
            $foto->save();

            DB::commit();

            return $foto;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }
}
