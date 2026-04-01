<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Participante;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ParticipanteService
{
    public function convidarParticipante($dadosConvidado)
    {
        DB::beginTransaction();

        try {
            $tokenString = md5(uniqid((string) rand(), true));

            $usuario = Usuario::create([
                'nome' => $dadosConvidado['nome'] ?? null,
                'email' => $dadosConvidado['email'],
                'funcao' => 'participante',
                'token' => $tokenString,
                'password' => null,
                'ativo' => true,
            ]);

            $participante = Participante::create([
                'usuario_id' => $usuario->id,
                'etapa_cadastro' => 'convidado',
            ]);

            $this->sendConviteEmail($usuario->email, $usuario->nome ?? null, $usuario->token);

            DB::commit();

            return [
                'usuario' => $usuario,
                'participante' => $participante,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao convidar participante: ' . $e->getMessage());
        }
    }

    public function atualizarParticipante($dadosUsuario, $dadosParticipante, $id)
    {
        DB::beginTransaction();

        try {
            $usuario = Usuario::query()
                ->where([
                    'id' => $id,
                    'funcao' => 'participante',
                    'excluido' => NULL,
                ])
                ->with(['participante' => function ($q) {
                    $q->where('excluido', NULL);
                }])
                ->first();

            if (!$usuario) {
                throw new \Exception('Participante não encontrado.');
            }

            $usuario->update([
                'nome' => $dadosUsuario['nome'],
                'email' => $dadosUsuario['email'],
                'password' => isset($dadosUsuario['password']) && $dadosUsuario['password']
                    ? Hash::make($dadosUsuario['password'])
                    : $usuario->password,
                'ativo' => $dadosUsuario['ativo'],
            ]);

            $participante = $usuario->participante;
            $participante->update([
                'cpf' => preg_replace('/\D/', '', $dadosParticipante['cpf']),
                'data_nascimento' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_nascimento'])->format('Y-m-d'),
                'rg' => $dadosParticipante['rg'],
                'data_expedicao_rg' => Carbon::createFromFormat('Y-m-d', $dadosParticipante['data_expedicao_rg'])->format('Y-m-d'),
                'fone_celular' => $dadosParticipante['fone_celular'],
                'fone_emergencia' => $dadosParticipante['fone_emergencia'],
                'restricao_alimentar' => $dadosParticipante['restricao_alimentar'],
                'restricao_alimentar_qual' => $dadosParticipante['restricao_alimentar']
                    ? ($dadosParticipante['restricao_alimentar_qual'] ?? null)
                    : null,
                'limitacao' => $dadosParticipante['limitacao'],
                'limitacao_qual' => $dadosParticipante['limitacao']
                    ? ($dadosParticipante['limitacao_qual'] ?? null)
                    : null,
                'medicamento' => $dadosParticipante['medicamento'],
                'medicamento_qual' => $dadosParticipante['medicamento']
                    ? ($dadosParticipante['medicamento_qual'] ?? null)
                    : null,
                'medicamento_dosagem' => ($dadosParticipante['medicamento'] && isset($dadosParticipante['medicamento_dosagem']))
                    ? $dadosParticipante['medicamento_dosagem']
                    : null,
                'problema_saude' => $dadosParticipante['problema_saude'],
                'problema_saude_qual' => $dadosParticipante['problema_saude']
                    ? ($dadosParticipante['problema_saude_qual'] ?? null)
                    : null,
                'etapa_cadastro' => 'concluido',
            ]);

            DB::commit();

            return [
                'usuario' => $usuario,
                'participante' => $participante,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao atualizar participante: ' . $e->getMessage());
        }
    }

    public function excluirParticipantes($ids)
    {
        DB::beginTransaction();

        try {
            $response = Usuario::query()
                ->where([
                    'excluido' => NULL,
                    'funcao' => 'participante',
                ])
                ->whereIn('id', $ids)
                ->update([
                    'excluido' => Carbon::now(),
                ]);

            if ($response === 0) {
                throw new \Exception('Nenhum registro encontrado para exclusão.');
            }

            DB::commit();

            return [
                'excluidos' => $ids,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao excluir participantes: ' . $e->getMessage());
        }
    }

    public function ativarParticipantes($ids)
    {
        DB::beginTransaction();

        try {
            $response = Usuario::query()
                ->where([
                    'excluido' => NULL,
                    'funcao' => 'participante',
                ])
                ->whereIn('id', $ids)
                ->update([
                    'ativo' => true,
                ]);

            if ($response === 0) {
                throw new \Exception('Nenhum registro encontrado para ativação.');
            }

            DB::commit();

            return [
                'ativados' => $ids,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao ativar participantes: ' . $e->getMessage());
        }
    }

    public function desativarParticipantes($ids)
    {
        DB::beginTransaction();

        try {
            $response = Usuario::query()
                ->where([
                    'excluido' => NULL,
                    'funcao' => 'participante',
                ])
                ->whereIn('id', $ids)
                ->update([
                    'ativo' => false,
                ]);

            if ($response === 0) {
                throw new \Exception('Nenhum registro encontrado para desativação.');
            }

            DB::commit();

            return [
                'desativados' => $ids,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao desativar participantes: ' . $e->getMessage());
        }
    }

    private function sendConviteEmail($email, $nome = null, $token)
    {
        $data = [
            'email' => $email,
            'nome' => $nome,
            'token' => $token
        ];

        Mail::send('emails.register', $data, function ($message) use ($data) {
            $message->from('naoresponda@benvenuttionline.com.br', 'Móveis Benvenutti')
                ->to($data['email'])
                ->bcc('rafael@8poroito.com.br')
                ->subject('Você foi convidado para o sistema de pontuações Benvenutti.');
        });
    }
}
