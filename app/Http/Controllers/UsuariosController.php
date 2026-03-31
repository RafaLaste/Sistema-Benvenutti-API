<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Usuario;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsuariosController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Por favor, insira seu e-mail.',
            'email.email' => 'Por favor, insira um e-mail válido.',
            'password.required' => 'Por favor, insira sua senha.',
        ]);

        $usuario = Usuario::query()
            ->where([
                'email' => $request->email,
                'excluido' => NULL
            ])
            ->first();

        if (!$usuario || !$usuario->isParticipante()) {
            return response()->json(['error' => 'unauthorized_user'], 403);
        }

        $credentials = $request->only('email', 'password');
        $lembrar = $request->input('lembrar', false);

        $expiresAt = $lembrar ? Carbon::now()->addDays(7) : Carbon::now()->addDay();

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $log = new Log;
        $log->usuario_id = $usuario->id;
        $log->save();

        $token = JWTAuth::claims(['exp' => $expiresAt])->attempt($credentials);

        return response()->json([
            'token' => $token,
            'usuario' => $usuario,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function getUsuario()
    {
        $participanteAutenticado = auth()->user();

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
            if ($item['id'] == $participanteAutenticado->id) {
                $posicao = $index + 1;
                break;
            }
        }

        $pontos = $participanteAutenticado->participante->pontos ?? collect();
        $totalPontos = $pontos->sum(function ($ponto) {
            return $ponto->tipo === 'adicao'
                ? $ponto->quantidade
                : -$ponto->quantidade;
        });

        $formattedUsuario = [
            'nome' => $participanteAutenticado->nome ?? '',
            'total_pontos' => $totalPontos ?? '',
            'posicao' => $posicao ?? '',

        ];

        return response()->json($formattedUsuario);
    }

    public function setPrimeiroAcesso(Request $request)
    {
        $usuario = auth()->user()->load([
            'participante.destinos' => function ($query) {
                $query->where('ano_vigente', 2024);
            },
            'participante.passaporte' => function ($query) {
                $query->where('excluido', NULL);
            },
            'participante.destinos' => function ($query) {
                $query->where('excluido', NULL);
            },
        ]);

        if (!$usuario->participante->primeiro_acesso) {
            $usuario->ativo = true;
            $usuario->participante->primeiro_acesso = true;
            $usuario->save();
            $usuario->participante->save();
        }
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function updateUsuario(Request $request)
    {
        $usuario = auth()->user();

        $participanteId = $usuario->participante->id;

        $this->validate($request, [
            'rg' => 'required|string|max:20',
            'data_expedicao_rg' => 'required|date_format:d/m/Y',
            'cpf' => 'required|cpf|unique:cadastros_participantes,cpf,' . $participanteId,
            'data_nascimento' => 'required|date_format:d/m/Y',
            'fone_celular' => 'required|celular_com_ddd',
            'fone_emergencia' => 'required|celular_com_ddd',
            'restricao_alimentar' => 'required|boolean',
            'restricao_alimentar_qual' => 'nullable|max:72',
            'limitacao' => 'required|boolean',
            'limitacao_qual' => 'nullable|max:72',
            'medicamento' => 'required|boolean',
            'medicamento_qual' => 'nullable|max:72',
            'medicamento_dosagem' => 'nullable|max:24',
            'problema_saude' => 'required|boolean',
            'problema_saude_qual' => 'nullable|max:72',
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:usuarios,email,' . $usuario->id,
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
        ], [
            'rg.required' => 'Por favor, informe seu RG.',
            'data_expedicao_rg.required' => 'Por favor, informe a data de expedição do seu RG.',
            'data_expedicao_rg.date' => 'Por favor, informe uma data válida para a expedição do RG.',
            'data_nascimento.required' => 'Por favor, informe sua data de nascimento.',
            'data_nascimento.date' => 'Por favor, informe uma data de nascimento válida.',
            'fone_celular.required' => 'Por favor, informe seu telefone.',
            'fone_celular.celular_com_ddd' => 'Por favor, informe um telefone válido.',
            'fone_fixo.telefone_com_ddd' => 'Por favor, informe um telefone fixo válido.',
            'fone_comercial.required' => 'Por favor, informe seu telefone comercial.',
            'fone_comercial.celular_com_ddd' => 'Por favor, informe um telefone comercial válido.',
            'fone_emergencia.required' => 'Por favor, informe um contato para emergências.',
            'fone_emergencia.celular_com_ddd' => 'Por favor, informe um contato para emergências válido.',
            'restricao_alimentar.required' => 'Por favor, informe se há restrição alimentar.',
            'restricao_alimentar.boolean' => 'Valor inválido para restrição alimentar, atualize a página.',
            'restricao_alimentar_qual.max' => 'A restrição alimentar deve ter no máximo 72 caracteres.',
            'limitacao.required' => 'Por favor, informe se há limitação.',
            'limitacao.boolean' => 'Valor inválido para limitação, atualize a página.',
            'limitacao_qual.max' => 'A limitação deve ter no máximo 72 caracteres.',
            'medicamento.required' => 'Por favor, informe se há medicamento.',
            'medicamento.boolean' => 'Valor inválido para medicamento, atualize a página.',
            'medicamento_qual.max' => 'O medicamento deve ter no máximo 72 caracteres.', //Criar validacao para dosagem
            'medicamento_dosagem.max' => 'A dosagem do medicamento deve ter no máximo 24 caracteres.',
            'problema_saude.required' => 'Por favor, informe se há problema de saúde.',
            'problema_saude.boolean' => 'Valor inválido para problema de saúde, atualize a página.',
            'problema_saude_qual.max' => 'O problema de saúde deve ter no máximo 72 caracteres.',
            'nome.required' => 'Por favor, informe seu nome.',
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
            'password.required' => 'Por favor, informe sua senha.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
            'password_confirmation.required' => 'Por favor, confirme sua senha.',
            'cpf.required' => 'Por favor, informe seu CPF.',
            'cpf.cpf' => 'Por favor, informe um CPF válido.',
            'cpf.unique' => 'Este CPF já está registrado no programa.',
        ]);

        $dadosUsuario = $request->only(['nome', 'email', 'password']);
        $dadosParticipante = $request->only(['cpf', 'data_nascimento', 'rg', 'data_expedicao_rg', 'fone_celular', 'fone_emergencia', 'restricao_alimentar', 'restricao_alimentar_qual', 'limitacao', 'limitacao_qual', 'medicamento', 'medicamento_qual', 'medicamento_dosagem', 'problema_saude', 'problema_saude_qual']);

        try {
            $response = $this->userService->atualizarCadastro($usuario, $dadosUsuario, $dadosParticipante);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastro atualizado com sucesso.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar cadastro.',
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
