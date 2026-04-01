<?php

namespace App\Services;

use App\Models\Foto;
use App\Models\Edicao;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Services\ImagemCompressorService;

class ImagemService
{
    protected $compressor;

    public function __construct(ImagemCompressorService $compressor)
    {
        $this->compressor = $compressor;
    }

    public function cadastrarFoto($arquivo, $idEdicao)
    {
        DB::beginTransaction();

        try {
            $imagem = md5(uniqid(rand(), true)) . '.' . strtolower($arquivo->extension());

            $edicao = Edicao::query()
                ->where([
                    'id' => $idEdicao,
                    'excluido' => NULL,
                ])
                ->first();

            if (!$edicao) {
                throw new \Exception('Edicao não encontrado.');
            }

            $ultimaOrdem = Foto::query()
                ->where([
                    'edicao_id' => $idEdicao,
                    'excluido' => NULL,
                ])
                ->max('ordem');


            $ordem = $ultimaOrdem ? $ultimaOrdem + 1 : 1;

            $foto = Foto::create([
                'ordem' => $ordem,
                'visivel' => true,
                'imagem' => $imagem,
                'edicao_id' => $idEdicao
            ]);

            DB::commit();

            $this->compressor->compactarOuReverter($arquivo->getRealPath(), base_path('../media/content/editions/thumbs/imagem/' . $imagem));

            return [
                'foto' => $foto
            ];
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
