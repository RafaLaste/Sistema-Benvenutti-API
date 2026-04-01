<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\Usuario;
use App\Models\Token;

class SenhaService
{
    public function solicitarSenha($dadosUsuario)
    {
        DB::beginTransaction();

        try {
            $usuario = Usuario::query()
                ->where([
                    'email' => $dadosUsuario['email'],
                    'excluido' => NULL
                ])
                ->first();

            if (!$usuario) {
                throw new \Exception('Não há registro desse e-mail no programa!');
            }

            $now = Carbon::now();
            $expires = $now->modify('+3 hours');
            $tokenString = md5(uniqid((string) rand(), true));

            $token = Token::create([
                'token' => $tokenString,
                'usuario_id' => $usuario->id,
                'expira' => $expires,
            ]);

            $data['nome'] = $usuario['nome'];
            $data['email'] = $usuario['email'];
            $data['token'] = $tokenString;

            Mail::send('emails.password', $data, function ($message) use ($data) {
                $message->from('naoresponda@todeschini.viaggiotur.com.br', 'Todeschini')
                    ->to($data['email'])
                    ->bcc('rafael@8poroito.com.br')
                    ->subject('Você solicitou uma recuperação de senha.');
            });

            DB::commit();

            return [
                'token' => $token,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function atualizarSenha($dadosSenha, $token)
    {
        DB::beginTransaction();

        try {
            $token = Token::query()
                ->where([
                    'utilizado' => NULL,
                    ['expira', '>', Carbon::now()->format('Y-m-d H:i:s')],
                    'token' => $token,
                ])
                ->first();

            if (!$token) {
                throw new \Exception('Este link expirou ou já foi utilizado. Solicite a alteração de senha novamente.');
            }

            $usuario = Usuario::query()
                ->where([
                    'id' => $token->usuario_id,
                    'excluido' => NULL
                ])
                ->first();

            $token->update([
                'utilizado' => Carbon::now()
            ]);

            $usuario->update([
                'password' => Hash::make($dadosSenha['password'])
            ]);

            DB::commit();

            return [
                'usuario' => $usuario,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao atualizar a senha: ' . $e->getMessage());
        }
    }
}
