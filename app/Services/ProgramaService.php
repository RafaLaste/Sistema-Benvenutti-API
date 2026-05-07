<?php

namespace App\Services;

use App\Models\Programa;
use App\Models\Participante;
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
    
    public function resetarPrograma()
    {
        DB::beginTransaction();

        try {
            $this->backupBancoDados();

            Participante::query()
                ->where('excluido', null)
                ->update([
                    'etapa_cadastro' => 'convidado',
                    'termo_adesao' => null,
                ]);

            DB::commit();            

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('pontos')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao resetar o programa: ' . $e->getMessage());
        }
    }

    private function backupBancoDados()
    {
        $arquivoSql = 'backup_' . Carbon::now()->format('Y-m-d_H-i-s') . '.sql';
        $caminho = base_path('../media/backup/' . $arquivoSql);

        $pdo = DB::connection()->getPdo();
        $sql = '';

        $tabelas = DB::select('SHOW TABLES');
        $keyTabela = 'Tables_in_' . config('database.connections.mysql.database');

        foreach ($tabelas as $tabela) {
            $nomeTabela = $tabela->$keyTabela;

            $createTable = DB::select("SHOW CREATE TABLE `{$nomeTabela}`");
            $sql .= "\n\nDROP TABLE IF EXISTS `{$nomeTabela}`;\n";
            $sql .= $createTable[0]->{'Create Table'} . ";\n";

            $linhas = DB::table($nomeTabela)->get();

            if ($linhas->isEmpty()) continue;

            $sql .= "\nINSERT INTO `{$nomeTabela}` VALUES\n";

            $valores = $linhas->map(function ($linha) use ($pdo) {
                $campos = array_map(function ($valor) use ($pdo) {
                    return is_null($valor) ? 'NULL' : $pdo->quote($valor);
                }, (array) $linha);

                return '(' . implode(', ', $campos) . ')';
            });

            $sql .= $valores->implode(",\n") . ";\n";
        }

        file_put_contents($caminho, $sql);

        return $arquivoSql;
    }
}
