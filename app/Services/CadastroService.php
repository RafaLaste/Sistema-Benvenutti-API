<?php

namespace App\Services;

use App\Models\Programa;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CadastroService
{
    protected $pdfGeradorService;

    public function __construct(PdfGeradorService $pdfGeradorService)
    {
        $this->pdfGeradorService = $pdfGeradorService;
    }

    public function completarCadastro($usuario, $dadosUsuario, $dadosParticipante)
    {
        try {
            DB::beginTransaction();

            $participante = $usuario->participante;

            $usuario->update([
                'nome' => $dadosUsuario['nome'],
                'email' => $dadosUsuario['email'],
                'password' => Hash::make($dadosUsuario['password']),
            ]);

            $participante->update([
                'cpf' => preg_replace('/\D/', '', $dadosParticipante['cpf']),
                'data_nascimento' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_nascimento'])->format('Y-m-d'),
                'rg' => $dadosParticipante['rg'],
                'data_expedicao_rg' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_expedicao_rg'])->format('Y-m-d'),
                'fone_celular' => $dadosParticipante['fone_celular'],
                'fone_emergencia' => $dadosParticipante['fone_emergencia'],
                'etapa_cadastro' => 'termo_adesao',
                'restricao_alimentar' => $dadosParticipante['restricao_alimentar'],
                'restricao_alimentar_qual' => $dadosParticipante['restricao_alimentar'] ? $dadosParticipante['restricao_alimentar_qual'] : null,
                'limitacao' => $dadosParticipante['limitacao'],
                'limitacao_qual' => $dadosParticipante['limitacao'] ? $dadosParticipante['limitacao_qual'] : null,
                'medicamento' => $dadosParticipante['medicamento'],
                'medicamento_qual' => $dadosParticipante['medicamento'] ? $dadosParticipante['medicamento_qual'] : null,
                'medicamento_dosagem' => $dadosParticipante['medicamento'] ? $dadosParticipante['medicamento_dosagem'] : null,
                'problema_saude' => $dadosParticipante['problema_saude'],
                'problema_saude_qual' => $dadosParticipante['problema_saude'] ? $dadosParticipante['problema_saude_qual'] : null,
            ]);

            DB::commit();

            return $usuario->load('participante');
        } catch (\Exception $e) {
            DB::rollback();

            throw new \Exception('Ocorreu um erro ao completar o cadastro: ' . $e->getMessage());
        }
    }

    public function termoAdesao($usuario)
    {
        try {
            DB::beginTransaction();

            $programa = Programa::findOrFail(1);

            if (empty($programa->termo_adesao)) {
                throw new \Exception('O termo de adesão ainda não foi configurado.');
            }

            $termoPdf = $this->pdfGeradorService
                ->gerar('pdf.termo_adesao', $programa->termo_adesao, $usuario->nome)
                ->output();


            $nomeArquivo = md5(uniqid(rand(), true)) . '.pdf';
            $caminho = base_path('../media/content/files/terms/' . $nomeArquivo);

            file_put_contents($caminho, $termoPdf);


            $usuario->participante->update([
                'etapa_cadastro' => 'regulamento',
                'termo_adesao'   => $nomeArquivo
            ]);

            DB::commit();

            return $usuario->load('participante');
        } catch (\Exception $e) {
            DB::rollback();

            throw new \Exception('Ocorreu um erro ao aceitar o termo de adesão: ' . $e->getMessage());
        }
    }

    public function regulamento($usuario)
    {
        try {
            DB::beginTransaction();

            $usuario->participante->update([
                'etapa_cadastro' => 'concluido'
            ]);

            DB::commit();

            return $usuario->load('participante');
        } catch (\Exception $e) {
            DB::rollback();

            throw new \Exception('Ocorreu um erro ao aceitar o regulamento: ' . $e->getMessage());
        }
    }
}
