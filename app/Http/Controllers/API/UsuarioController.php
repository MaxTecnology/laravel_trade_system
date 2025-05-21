<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Usuario::query();

        // Filtros
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('bloqueado')) {
            $query->where('bloqueado', $request->bloqueado);
        }

        if ($request->has('matriz_id')) {
            $query->where('matriz_id', $request->matriz_id);
        }

        // Ordenação
        $orderBy = $request->input('order_by', 'id_usuario');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);

        return $query->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|unique:usuarios,cpf',
            'email' => 'required|string|email|max:255|unique:usuarios,email',
            'senha' => 'required|string|min:6',
            'imagem' => 'nullable|image|max:2048', // 2MB max
            'status_conta' => 'nullable|boolean',
            'reputacao' => 'nullable|numeric',
            'razao_social' => 'nullable|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|unique:usuarios,cnpj',
            'insc_estadual' => 'nullable|string',
            'insc_municipal' => 'nullable|string',
            'mostrar_no_site' => 'nullable|boolean',
            'descricao' => 'nullable|string',
            'tipo' => 'required|string',
            'tipo_de_moeda' => 'nullable|string',
            'status' => 'nullable|boolean',
            'restricao' => 'nullable|string',
            'nome_contato' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email_contato' => 'nullable|email|max:255',
            'email_secundario' => 'nullable|email|max:255',
            'site' => 'nullable|url|max:255',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|integer',
            'cep' => 'nullable|string|max:10',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'regiao' => 'nullable|string|max:255',
            'aceita_orcamento' => 'required|boolean',
            'aceita_voucher' => 'required|boolean',
            'tipo_operacao' => 'required|integer',
            'categoria_id' => 'nullable|exists:categorias,id_categoria',
            'sub_categoria_id' => 'nullable|exists:sub_categorias,id_sub_categoria',
            'taxa_comissao_gerente' => 'nullable|integer',
            'permissoes_do_usuario' => 'nullable|json',
            'bloqueado' => 'nullable|boolean',
            'usuario_criador_id' => 'nullable|exists:usuarios,id_usuario',
            'matriz_id' => 'nullable|exists:usuarios,id_usuario',
        ]);

        // Upload de imagem
        if ($request->hasFile('imagem')) {
            $imagemPath = $request->file('imagem')->store('usuarios', 'public');
            $validated['imagem'] = $imagemPath;
        }

        // Hash da senha
        $validated['senha'] = Hash::make($validated['senha']);

        // Criar usuário
        $usuario = Usuario::create($validated);

        return response()->json($usuario, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $usuario = Usuario::with(['conta', 'categoria', 'sub_categoria'])->findOrFail($id);
        return response()->json($usuario);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $usuario = Usuario::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'cpf' => ['sometimes', 'string', Rule::unique('usuarios', 'cpf')->ignore($id, 'id_usuario')],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($id, 'id_usuario')],
            'senha' => 'sometimes|string|min:6',
            'imagem' => 'nullable|image|max:2048', // 2MB max
            'status_conta' => 'nullable|boolean',
            'reputacao' => 'nullable|numeric',
            'razao_social' => 'nullable|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'cnpj' => ['nullable', 'string', Rule::unique('usuarios', 'cnpj')->ignore($id, 'id_usuario')],
            'insc_estadual' => 'nullable|string',
            'insc_municipal' => 'nullable|string',
            'mostrar_no_site' => 'nullable|boolean',
            'descricao' => 'nullable|string',
            'tipo' => 'sometimes|string',
            'tipo_de_moeda' => 'nullable|string',
            'status' => 'nullable|boolean',
            'restricao' => 'nullable|string',
            'nome_contato' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email_contato' => 'nullable|email|max:255',
            'email_secundario' => 'nullable|email|max:255',
            'site' => 'nullable|url|max:255',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|integer',
            'cep' => 'nullable|string|max:10',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'regiao' => 'nullable|string|max:255',
            'aceita_orcamento' => 'sometimes|boolean',
            'aceita_voucher' => 'sometimes|boolean',
            'tipo_operacao' => 'sometimes|integer',
            'categoria_id' => 'nullable|exists:categorias,id_categoria',
            'sub_categoria_id' => 'nullable|exists:sub_categorias,id_sub_categoria',
            'taxa_comissao_gerente' => 'nullable|integer',
            'permissoes_do_usuario' => 'nullable|json',
            'bloqueado' => 'nullable|boolean',
            'matriz_id' => 'nullable|exists:usuarios,id_usuario',
        ]);

        // Upload de imagem
        if ($request->hasFile('imagem')) {
            // Remover imagem anterior se existir
            if ($usuario->imagem) {
                Storage::disk('public')->delete($usuario->imagem);
            }

            $imagemPath = $request->file('imagem')->store('usuarios', 'public');
            $validated['imagem'] = $imagemPath;
        }

        // Hash da senha se fornecida
        if (isset($validated['senha'])) {
            $validated['senha'] = Hash::make($validated['senha']);
        }

        // Atualizar usuário
        $usuario->update($validated);

        return response()->json($usuario);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $usuario = Usuario::findOrFail($id);

        // Verificar dependências críticas antes de excluir
        // Por exemplo, se o usuário tem transações, etc.

        // Remover imagem do usuário se existir
        if ($usuario->imagem) {
            Storage::disk('public')->delete($usuario->imagem);
        }

        $usuario->delete();

        return response()->json(null, 204);
    }

    /**
     * Bloquear/Desbloquear um usuário
     */
    public function toggleBloquear(int $id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->bloqueado = !$usuario->bloqueado;
        $usuario->save();

        return response()->json([
            'message' => $usuario->bloqueado ? 'Usuário bloqueado com sucesso' : 'Usuário desbloqueado com sucesso',
            'usuario' => $usuario
        ]);
    }

    /**
     * Redefinir senha do usuário
     */
    public function resetarSenha(Request $request, int $id)
    {
        $request->validate([
            'novaSenha' => 'required|string|min:6',
        ]);

        $usuario = Usuario::findOrFail($id);
        $usuario->senha = Hash::make($request->novaSenha);
        $usuario->token_reset_senha = null;
        $usuario->save();

        return response()->json([
            'message' => 'Senha redefinida com sucesso',
        ]);
    }
}
