<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

use App\Services\AdminUsuarioService;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Usuario;

use Carbon\Carbon;

class UsuariosController extends Controller
{
    protected $adminUsuarioService;

    public function __construct(AdminUsuarioService $adminUsuarioService)
    {
        $this->adminUsuarioService = $adminUsuarioService;
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

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $usuario = Usuario::query()
            ->where([
                'email' => $request->email,
                'excluido' => NULL
            ])
            ->first();

        if (!$usuario || !$usuario->ativo || !$usuario->isAdmin()) {
            return response()->json(['error' => 'unauthorized_user'], 403);
        }

        $payload = JWTAuth::setToken($token)->getPayload();
        $expiresAt = Carbon::createFromTimestamp($payload->get('exp'));

        return response()->json([
            'token' => $token,
            'usuario' => $usuario,
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function getUsuario($id = NULL)
    {
        if ($id) {
            $usuario = Usuario::query()
                ->where([
                    'excluido' => NULL,
                    'funcao' => 'administrador',
                    'id' => $id
                ])
                ->orderBy('nome', 'ASC')
                ->first();

            if (!$usuario) {
                return response()->json([
                    'error' => 'Usuário não encontrado.'
                ], 404);
            }

            return response()->json([
                'usuario' => [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome,
                    'email' => $usuario->email,
                    'data' => $usuario->criado->format('d/m/Y'),
                    'ativo' => $usuario->ativo ? true : false
                ]
            ]);
        } else {
            $usuario = auth()->user();
            return response()->json($usuario);
        }
    }

    public function getUsuarios()
    {
        $usuarios = Usuario::query()
            ->where([
                'excluido' => NULL,
                'funcao' => 'administrador',
            ])
            ->orderBy('nome', 'ASC')
            ->get()
            ->map(function ($usuario) {
                return [
                    'id' => $usuario->id,
                    'nome' => $usuario->nome,
                    'email' => $usuario->email,
                    'data' => $usuario->criado->format('d/m/Y'),
                ];
            });

        return response()->json([
            'usuarios' => $usuarios,
        ]);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function createUsuario(Request $request)
    {
        $this->validate($request, [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email|max:255',
            'password' => 'required|string|min:6',
            'ativo' => 'required|boolean',
        ], [
            'nome.required' => 'Por favor, informe seu nome.',
            'nome_completo.required' => 'Por favor, informe seu nome completo.',
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
            'password.required' => 'Por favor, informe sua senha.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            // 'password.confirmed' => 'As senhas não conferem.',
            'ativo.required' => 'Por favor, informe se o usuário está ativo.',
            'ativo.boolean' => 'Valor inválido para ativação do usuário, atualize a página.',
        ]);

        $dadosUsuario = $request->only(['nome', 'email', 'password', 'ativo']);

        try {
            $response = $this->adminUsuarioService->cadastrarUsuario($dadosUsuario);

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Cadastro realizado com sucesso.'
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar cadastro.',
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

    public function updateUsuario(Request $request, $id)
    {
        $usuario = Usuario::query()
            ->where([
                'id' => $id,
                'excluido' => NULL
            ])
            ->with([
                'participante' => function ($q) use ($id) {
                    $q->where('excluido', NULL);
                }
            ])
            ->first();

        if (!$usuario) {
            return response()->json([
                'error' => 'Participante não encontrado.'
            ], 404);
        }

        $this->validate($request, [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email,' . $usuario->id,
            'password' => 'nullable|string|min:6',
            'ativo' => 'required|boolean',
        ], [
            'nome.required' => 'Por favor, informe seu nome.',
            'nome_completo.required' => 'Por favor, informe seu nome completo.',
            'email.required' => 'Por favor, informe seu e-mail.',
            'email.email' => 'Por favor, informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está registrado no programa.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            // 'password.confirmed' => 'As senhas não conferem.',
            'ativo.required' => 'Por favor, informe se o usuário está ativo.',
            'ativo.boolean' => 'Valor inválido para ativação do usuário, atualize a página.',
        ]);

        $dadosUsuario = $request->only(['nome', 'email', 'password', 'ativo']);

        try {
            $response = $this->adminUsuarioService->atualizarUsuario($dadosUsuario, $id);

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

    public function deleteUsuarios($ids)
    {
        $explodeIds = explode(',', $ids);

        try {
            $response = $this->adminUsuarioService->excluirUsuarios($explodeIds);

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
}
