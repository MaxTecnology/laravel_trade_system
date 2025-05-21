<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar um novo usuário
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|unique:usuarios,cpf',
            'email' => 'required|string|email|max:255|unique:usuarios,email',
            'senha' => 'required|string|min:6',
            'tipo' => 'required|string',
            'aceita_orcamento' => 'required|boolean',
            'aceita_voucher' => 'required|boolean',
            'tipo_operacao' => 'required|integer',
        ]);

        $usuario = Usuario::create([
            'nome' => $request->nome,
            'cpf' => $request->cpf,
            'email' => $request->email,
            'senha' => Hash::make($request->senha),
            'tipo' => $request->tipo,
            'aceita_orcamento' => $request->aceita_orcamento,
            'aceita_voucher' => $request->aceita_voucher,
            'tipo_operacao' => $request->tipo_operacao,
            'usuario_criador_id' => $request->usuario_criador_id,
            'matriz_id' => $request->matriz_id,
            'permissoes_do_usuario' => '[]',
        ]);

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'usuario' => $usuario
        ], 201);
    }

    /**
     * Login de usuário
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'senha' => 'required|string',
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->senha, $usuario->senha)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Verifica se o usuário está bloqueado
        if ($usuario->bloqueado) {
            return response()->json([
                'message' => 'Usuário bloqueado. Entre em contato com o suporte.',
            ], 403);
        }

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'usuario' => $usuario
        ]);
    }

    /**
     * Logout de usuário
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }

    /**
     * Obter usuário autenticado
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $usuario = $request->user();

        // Carrega relacionamentos necessários
        $usuario->load(['conta', 'categoria', 'sub_categoria']);

        return response()->json($usuario);
    }
}
