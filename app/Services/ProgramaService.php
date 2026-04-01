<?php

namespace App\Services;

use App\Models\Programa;

use Illuminate\Support\Facades\DB;

class ProgramaService
{
    public function atualizarDados($dadosPrograma)
    {
        DB::beginTransaction();

        try {
            $programa = Programa::where('excluido', NULL)->first();

            if (!$programa) {
                throw new \Exception('Dados originais não encontrados');
            }

            $programa->update([
                'nome_site' => $dadosPrograma['nome'],
                'email_contato' => $dadosPrograma['email_contato'],
                'telefone' => $dadosPrograma['telefone'],
                'cadastros_ativos' => $dadosPrograma['cadastros_ativos'],
            ]);

            DB::commit();

            return [
                'programa' => $programa
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Erro ao atualizar os dados: ' . $e->getMessage());
        }
    }
}
