<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Foto;
use App\Services\ImagemService;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class FotosController extends Controller
{
    protected $imagemService;

    public function __construct(ImagemService $imagemService)
    {
        $this->imagemService = $imagemService;
    }

    public function getFotos($idEdicao)
    {
        $foto = Foto::query()
            ->where([
                'edicao_id' => $idEdicao,
                'excluido' => NULL,
            ])
            ->orderBy('ordem', 'ASC')
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($foto) {
                return [
                    'id' => $foto->id,
                    'img' => config('services.site_storage') . '/media/content/editions/thumbs/imagem/' . $foto->imagem,
                    'ordem' => $foto->ordem,
                    'visivel' => $foto->visivel ? true : false,
                    'edicao_id' => $foto->edicao_id
                ];
            });

        return response()->json([
            'fotos' => $foto
        ]);
    }

    public function getFoto($idFoto)
    {
        $foto = Foto::query()
            ->where([
                'id' => $idFoto,
                'excluido' => NULL,
            ])->first();

        $fotoData = [
            'id' => $foto->id,
            'img' => config('services.site_storage') . '/media/content/editions/thumbs/imagem/' . $foto->imagem,
            'ordem' => $foto->ordem,
            'visivel' => $foto->visivel,
            'edicao_id' => $foto->edicao_id
        ];

        return response()->json([
            'foto' => $fotoData
        ]);
    }

    public function createFoto(Request $request, $idEdicao)
    {
        $this->validate($request, [
            'imagem' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'imagem.required' => 'Por favor, insira uma imagem.',
            'imagem.image' => 'O arquivo deve ser uma imagem!',
            'imagem.mimes' => 'A imagem deve ser jpeg, png ou jpg!',
            'imagem.max' => 'A imagem não pode ter mais de 5MB!',
        ]);


        $imagem = $request->file('imagem');

        try {
            $response = $this->imagemService->cadastrarFoto($imagem, $idEdicao);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Foto criada com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar foto',
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

    public function orderFoto(Request $request)
    {
        $this->validate($request, [
            '*.id' => 'required|integer',
            '*.ordem' => 'required|integer',
        ], [
            'ordem.required' => 'Por favor, informe a ordem da foto.',
            'ordem.integer' => 'A ordem da imagem é um valor inválido!'
        ]);

        $dados = $request->all();

        try {
            $response = $this->imagemService->atualizarOrdem($dados);

            return response()->json([
                'success' => true,
                'data' => $response,
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar a ordem das fotos.',
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

    public function visibleFoto(Request $request, $idFoto)
    {
        $this->validate($request, [
            'visivel' => 'required|boolean',
        ], [
            'visivel.required' => 'Por favor, informe a visibilidade da foto.',
            'visivel.boolean' => 'A visibilidade da foto é um valor inválido!'
        ]);

        $dados = $request->only(['visivel']);

        try {
            $response = $this->imagemService->atualizarVisibilidade($dados, $idFoto);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Visibilidade alterada com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar a visibilidade da foto.',
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

    public function deleteFoto($idFoto)
    {
        try {
            $response = $this->imagemService->excluirFoto($idFoto);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Foto excluida com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir foto.',
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
