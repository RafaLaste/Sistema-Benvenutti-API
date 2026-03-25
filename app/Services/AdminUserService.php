<?php

namespace App\Services;

use App\Models\Usuario;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function cadastrarUsuario($dadosUsuario)
    {
        DB::beginTransaction();

        try {
            $usuario = Usuario::create([
                'nome' => $dadosUsuario['nome'],
                'email' => $dadosUsuario['email'],
                'password' => Hash::make($dadosUsuario['password']),
                'ativo' => $dadosUsuario['ativo'] ? true : false,
                'funcao' => 'administrador',
            ]);

            DB::commit();

            return response()->json([
                'usuario' => $usuario,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocorreu um erro!',
                'errors' => [
                    'general' => [$e->getMessage()]
                ]
            ], 500);
        }
    }

    public function atualizarUsuario($dadosUsuario, $id)
    {

        DB::beginTransaction();

        try {
            $usuario = Usuario::query()
                ->where([
                    'id' => $id,
                    'funcao' => 'administrador',
                    'excluido' => NULL
                ])
                ->first();

            if (!$usuario) {
                throw new \Exception('Não há registro desse e-mail no programa!');
            }

            $usuario->update([
                'nome' => $dadosUsuario['nome'],
                'email' => $dadosUsuario['email'],
                'password' => isset($dadosUsuario['password']) ? Hash::make($dadosUsuario['password']) : $usuario->password,
                'ativo' => $dadosUsuario['ativo'] ? true : false
            ]);

            DB::commit();

            return response()->json([
                'usuario' => $usuario,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao solicitar a alteração: ' . $e->getMessage());
        }
    }

    public function excluirUsuarios($ids)
    {
        DB::beginTransaction();

        try {
            $response = Usuario::query()
                ->where([
                    'excluido' => NULL,
                    'funcao' => 'administrador'
                ])
                ->whereIn('id', $ids)
                ->update([
                    'excluido' => Carbon::now()
                ]);

            if ($response === 0) {
                return response()->json([
                    'message' => 'Nenhum registro encontrado para exclusão.',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'message' => 'Registros excluídos com sucesso.',
                'excluidos' => $ids,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocorreu um erro ao excluir os registros!',
                'errors' => [
                    'general' => [$e->getMessage()],
                ],
            ], 500);
        }
    }
}