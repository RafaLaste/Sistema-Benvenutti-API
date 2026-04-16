<?php

namespace App\Services;

use App\Models\Programa;
use App\Services\PdfGeradorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProgramaService
{
    protected $pdfGeradorService;

    public function __construct(PdfGeradorService $pdfGeradorService)
    {
        $this->pdfGeradorService = $pdfGeradorService;
    }

    public function cadastrarDados($dados)
    {
        DB::beginTransaction();

        try {
            $programa = Programa::updateOrCreate(
                ['id' => 1],
                [
                    'titulo' => $dados['titulo'],
                    'descricao' => $dados['descricao'],
                    'data_inicio' => Carbon::createFromFormat('Y-m-d H:i:s', $dados['data_inicio']),
                    'data_final' => Carbon::createFromFormat('Y-m-d H:i:s', $dados['data_final']),
                    'regulamento' => $dados['regulamento'],
                    'termo_adesao' => $dados['termo_adesao'],
                ]
            );

            $regulamentoPdf = $this->pdfGeradorService->gerar('pdf.regulamento', $dados['regulamento'], $dados['titulo'])->output();


            $nomeArquivo = md5(uniqid(rand(), true)) . '.pdf';
            $caminho = base_path('../media/content/files/' . $nomeArquivo);

            file_put_contents($caminho, $regulamentoPdf);

            $programa->update([
                'regulamento_arquivo' => $nomeArquivo,
            ]);

            DB::commit();

            return [
                'programa' => [
                    'id' => $programa->id,
                    'titulo' => $programa->titulo,
                    'descricao' => $programa->descricao,
                    'data_inicio' => $programa->data_inicio,
                    'data_final' => $programa->data_final,
                    'regulamento' =>  $programa->regulamento_arquivo ? config('services.site.storage') . '/content/files/' . $programa->regulamento_arquivo : null
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao atualizar os dados: ' . $e->getMessage());
        }
    }
}
