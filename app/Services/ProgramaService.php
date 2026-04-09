<?php

namespace App\Services;

use App\Models\Programa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProgramaService
{
    public function cadastrarDados($dados, $arquivo)
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
                ]
            );

            if ($arquivo) {
                $regulamento = md5(uniqid(rand(), true)) . '.' . strtolower($arquivo->extension());
                $arquivo->move(base_path('../media/content/files/'), $regulamento);

                $programa->update([
                    'regulamento' => $regulamento,
                ]);
            }

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
