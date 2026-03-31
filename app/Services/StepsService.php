<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StepsService
{
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
                'data_nascimento' => Carbon::createFromFormat('d/m/Y', $dadosParticipante['data_nascimento'])->format('Y-m-d'),
                'rg' => $dadosParticipante['rg'],
                'data_expedicao_rg' => Carbon::createFromFormat('d/m/Y', $dadosParticipante['data_expedicao_rg'])->format('Y-m-d'),
                'fone_celular' => $dadosParticipante['fone_celular'],
                'fone_emergencia' => $dadosParticipante['fone_emergencia'],
                'etapa_cadastro' => 'concluido',
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
}
