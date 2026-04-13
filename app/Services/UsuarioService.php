<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioService
{
    public function atualizarCadastro($participanteId, $dadosSenha)
    {
        try {
            DB::beginTransaction();

            $usuario = Usuario::query()
                ->where([
                    'id' => $participanteId,
                    'excluido' => NULL
                ])
                ->first();

            if (!$usuario) {
                throw new \Exception('Não há registro desse participante no programa!');
            }

            $usuario->update([
                'password' => Hash::make($dadosSenha['password'])
            ]);

            DB::commit();

            return $usuario->load('participante');
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception('Ocorreu um erro ao atualizar o perfil: ' . $e->getMessage());
        }
    }
}
