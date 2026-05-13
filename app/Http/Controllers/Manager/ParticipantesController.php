<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

use App\Models\Usuario;

use App\Services\ParticipanteService;

use Illuminate\Http\Request;
use Carbon\Carbon;

class ParticipantesController extends Controller
{
    protected $participanteService;

    public function __construct(ParticipanteService $participanteService)
    {
        $this->participanteService = $participanteService;
    }

    public function getParticipantes()
    {
        $participantes = Usuario::query()
            ->where([
                'excluido' => NULL,
                'funcao' => 'participante',
            ])
            ->with([
                'participante' => function ($q) {
                    $q->where('excluido', NULL)
                        ->with(['pontos' => function ($query) {
                            $query->where('excluido', NULL);
                        }]);
                },
                'logs' => function ($q) {
                    $q->latest('criado')
                        ->take(1);
                },
            ])
            ->get()
            ->map(function ($usuario) {
                $pontos = $usuario->participante->pontos ?? collect();

                $totalPontos = $pontos->sum(function ($ponto) {
                    return $ponto->tipo === 'adicao'
                        ? $ponto->quantidade
                        : -$ponto->quantidade;
                });

                return [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome,
                    'email' => $usuario->email,
                    'etapa_cadastro' => $usuario->participante->etapa_cadastro,
                    'termo_adesao' => $usuario->participante->termo_adesao
                        ? config('services.site.storage') . '/content/files/terms/' . $usuario->participante->termo_adesao
                        : null,
                    'pontos' => $totalPontos,
                    'ativo' => $usuario->ativo ? true : false,

                    'ultimo_acesso' => $usuario->logs->isNotEmpty()
                        ? $usuario->logs->first()->criado->toDateTimeString()
                        : 'Nunca',

                    'ultimo_acesso_ha' => $usuario->logs->isNotEmpty()
                        ? Carbon::parse($usuario->logs->first()->criado)->diffForHumans()
                        : 'Nunca',
                ];
            })
            ->sortByDesc('pontos')
            ->values()
            ->map(function ($usuario, $index) {
                $usuario['posicao'] = $index + 1;
                return $usuario;
            });

        return response()->json([
            'participantes' => $participantes,
        ]);
    }

    public function inviteParticipante(Request $request)
    {
        $this->validate($request, [
            'nome' => 'nullable',
            'email' => 'required|email|unique:usuarios,email,NULL,id,excluido,NULL|max:255',
        ], [
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
        ]);

        $dadosConvidado = $request->only(['nome', 'email']);

        try {
            $response = $this->participanteService->convidarParticipante($dadosConvidado);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Convite enviado com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar convite.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getParticipante($id)
    {
        $participante = Usuario::query()
            ->where([
                'excluido' => NULL,
                'funcao' => 'participante',
                'id' => $id
            ])
            ->with(['participante' => function ($q) {
                $q->where('excluido', NULL)
                    ->with(['pontos' => function ($query) {
                        $query->where('excluido', NULL)
                            ->orderBy('criado', 'ASC');
                    }]);
            }])
            ->first();

        if (!$participante) {
            return response()->json([
                'error' => 'Participante não encontrado.'
            ], 404);
        }

        $ranking = Usuario::query()
            ->where([
                'excluido' => NULL,
                'funcao' => 'participante',
            ])
            ->with([
                'participante' => function ($q) {
                    $q->where('excluido', NULL)
                        ->with(['pontos' => function ($query) {
                            $query->where('excluido', NULL);
                        }]);
                }
            ])
            ->get()
            ->map(function ($usuario) {
                $pontos = $usuario->participante->pontos ?? collect();

                $total = $pontos->sum(function ($ponto) {
                    return $ponto->tipo === 'adicao'
                        ? $ponto->quantidade
                        : -$ponto->quantidade;
                });

                return [
                    'id' => $usuario->id,
                    'pontos' => $total,
                ];
            })
            ->sortByDesc('pontos')
            ->values();

        $posicao = null;
        $totalPontos = 0;

        foreach ($ranking as $index => $item) {
            if ($item['id'] == $participante->id) {
                $posicao = $index + 1;
                break;
            }
        }

        $pontos = $participante->participante->pontos ?? collect();
        $totalPontos = $pontos->sum(function ($ponto) {
            return $ponto->tipo === 'adicao'
                ? $ponto->quantidade
                : -$ponto->quantidade;
        });

        $etapas_cadastro = [
            'convidado' => 'Convite enviado',
            'concluido' => 'Concluído',
        ];

        $participanteData = [
            'id' => $participante->id,
            'nome' => $participante->nome,
            'email' => $participante->email,
            'cpf' => $participante->participante->cpf ? vsprintf("%s%s%s.%s%s%s.%s%s%s-%s%s", str_split($participante->participante->cpf)) : NULL,
            'rg' => $participante->participante->rg,
            'data_expedicao_rg' => $participante->participante->data_expedicao_rg,
            'data_nascimento' => $participante->participante->data_nascimento,
            'fone_celular' => $participante->participante->fone_celular,
            'fone_emergencia' => $participante->participante->fone_emergencia,
            'restricao_alimentar' => $participante->participante->restricao_alimentar ? true : false,
            'restricao_alimentar_qual' => $participante->participante->restricao_alimentar_qual,
            'limitacao' => $participante->participante->limitacao ? true : false,
            'limitacao_qual' => $participante->participante->limitacao_qual,
            'medicamento' => $participante->participante->medicamento ? true : false,
            'medicamento_qual' => $participante->participante->medicamento_qual,
            'medicamento_dosagem' => $participante->participante->medicamento_dosagem,
            'problema_saude' => $participante->participante->problema_saude ? true : false,
            'problema_saude_qual' => $participante->participante->problema_saude_qual,
            'etapa_cadastro' => $participante->participante->etapa_cadastro,
            'ativo' => $participante->ativo,
            'posicao' => $posicao,
            'total_pontos' => $totalPontos,
            'pontos' => $participante->participante->pontos->map(function ($ponto) {
                return [
                    'id' => $ponto->id,
                    'quantidade' => $ponto->quantidade,
                    'tipo' => $ponto->tipo,
                    'descricao' => $ponto->descricao,
                    'data' => $ponto->criado->format('d-m-Y'),
                    'categoria' => $ponto->categoria
                ];
            })
        ];

        return response()->json([
            'participante' => $participanteData,
        ]);
    }

    public function updateParticipante(Request $request, $id)
    {
        $participante = Usuario::query()
            ->where([
                'id' => $id,
                'excluido' => NULL
            ])
            ->with([
                'participante' => function ($q) {
                    $q->where('excluido', NULL);
                }
            ])
            ->first();

        if (!$participante) {
            return response()->json([
                'error' => 'Participante não encontrado.'
            ], 404);
        }

        $this->validate($request, [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,' . $participante->id,
            'password' => 'nullable|string|min:6',
            'ativo' => 'required|boolean',
            'cpf' => 'required|cpf|unique:cadastros_participantes,cpf,' . $participante->participante->id,
            'data_nascimento' => 'required|date_format:Y-m-d|before_or_equal:today',
            'rg' => 'required|string|max:20',
            'data_expedicao_rg' => 'required|date_format:Y-m-d|before_or_equal:today',
            'fone_celular' => 'required|celular_com_ddd',
            // 'fone_fixo' => 'nullable|telefone_com_ddd',
            // 'fone_comercial' => 'required|celular_com_ddd',
            'fone_emergencia' => 'required|celular_com_ddd',
            'restricao_alimentar' => 'required|boolean',
            'restricao_alimentar_qual' => 'nullable|max:120',
            'limitacao' => 'required|boolean',
            'limitacao_qual' => 'nullable|max:120',
            'medicamento' => 'required|boolean',
            'medicamento_qual' => 'nullable|max:120',
            'problema_saude' => 'required|boolean',
            'problema_saude_qual' => 'nullable|max:120',
        ], [
            'nome.required' => 'Por favor, informe seu nome.',
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            // 'password.confirmed' => 'As senhas não conferem.',
            'ativo.required' => 'Por favor, informe se o usuário está ativo.',
            'ativo.boolean' => 'Valor inválido para ativação do usuário, atualize a página.',
            'cpf.required' => 'Por favor, informe seu CPF.',
            'cpf.cpf' => 'Por favor, informe um CPF válido.',
            'cpf.unique' => 'Este CPF já está registrado no programa.',
            'data_nascimento.required' => 'Por favor, informe sua data de nascimento.',
            'data_nascimento.date' => 'Por favor, informe uma data de nascimento válida.',
            'data_nascimento.before_or_equal' => 'A data de nascimento não pode ser uma data futura.',
            'rg.required' => 'Por favor, informe seu RG.',
            'data_expedicao_rg.required' => 'Por favor, informe a data de expedição do seu RG.',
            'data_expedicao_rg.date' => 'Por favor, informe uma data válida para a expedição do RG.',
            'data_expedicao_rg.before_or_equal' => 'A data de expedição do RG não pode ser uma data futura.',
            'fone_celular.required' => 'Por favor, informe seu telefone.',
            'fone_celular.celular_com_ddd' => 'Por favor, informe um telefone válido.',
            'fone_emergencia.required' => 'Por favor, informe um contato para emergências.',
            'fone_emergencia.celular_com_ddd' => 'Por favor, informe um contato para emergências válido.',
            'restricao_alimentar.required' => 'Por favor, informe se há restrição alimentar.',
            'restricao_alimentar.boolean' => 'Valor inválido para restrição alimentar, atualize a página.',
            'restricao_alimentar_qual.max' => 'A restrição alimentar deve ter no máximo 120 caracteres.',
            'limitacao.required' => 'Por favor, informe se há limitação.',
            'limitacao.boolean' => 'Valor inválido para limitação, atualize a página.',
            'limitacao_qual.max' => 'A limitação deve ter no máximo 120 caracteres.',
            'medicamento.required' => 'Por favor, informe se há medicamento.',
            'medicamento.boolean' => 'Valor inválido para medicamento, atualize a página.',
            'medicamento_qual.max' => 'O medicamento deve ter no máximo 120 caracteres.',
            'problema_saude.required' => 'Por favor, informe se há problema de saúde.',
            'problema_saude.boolean' => 'Valor inválido para problema de saúde, atualize a página.',
            'problema_saude_qual.max' => 'O problema de saúde deve ter no máximo 120 caracteres.',
        ]);

        $dadosUsuario = $request->only(['nome', 'email', 'password', 'ativo']);
        $dadosParticipante = $request->only(['cpf', 'data_nascimento', 'rg', 'data_expedicao_rg', 'fone_celular', 'fone_emergencia', 'restricao_alimentar', 'restricao_alimentar_qual', 'limitacao', 'limitacao_qual', 'medicamento', 'medicamento_qual', 'medicamento_dosagem', 'problema_saude', 'problema_saude_qual']);

        try {
            $response = $this->participanteService->atualizarParticipante($dadosUsuario, $dadosParticipante, $id);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastro atualizado com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar o cadastro.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteParticipantes($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->participanteService->excluirParticipantes($explodeIds);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastros excluidos com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir cadastros.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function activeParticipantes($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->participanteService->ativarParticipantes($explodeIds);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastros ativados com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao ativar cadastros.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deactiveParticipantes($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->participanteService->desativarParticipantes($explodeIds);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastros desativados com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao desativar cadastros.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar a solicitação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
