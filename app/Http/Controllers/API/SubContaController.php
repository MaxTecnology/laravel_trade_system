<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubConta;
use App\Models\Conta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SubContaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SubConta::query();

        // Filtros
        if ($request->has('conta_pai_id')) {
            $query->where('conta_pai_id', $request->conta_pai_id);
        } else {
            // Se não for fornecido conta_pai_id, filtrar pelas contas que o usuário atual tem acesso
            $usuario = $request->user();

            // Se o usuário for uma matriz ou admin, pode ver todas as subcontas
            if ($usuario->tipo !== 'admin' && $usuario->tipo !== 'matriz') {
                $contasId = Conta::where('usuario_id', $usuario->id_usuario)
                    ->orWhere('gerente_conta_id', $usuario->id_usuario)
                    ->pluck('id_conta');

                $query->whereIn('conta_pai_id', $contasId);
            }
        }

        if ($request->has('status_conta')) {
            $query->where('status_conta', $request->status_conta);
        }

        // Relacionamentos
        $query->with('contaPai');

        // Ordenação
        $orderBy = $request->input('order_by', 'nome');
        $orderDirection = $request->input('order_direction', 'asc');
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
            'email' => 'required|string|email|max:255|unique:sub_contas,email',
            'cpf' => 'required|string|max:14|unique:sub_contas,cpf',
            'numero_sub_conta' => 'required|string|max:20|unique:sub_contas,numero_sub_conta',
            'senha' => 'required|string|min:6',
            'imagem' => 'nullable|image|max:2048', // 2MB max
            'status_conta' => 'nullable|boolean',
            'reputacao' => 'nullable|numeric',
            'telefone' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email_contato' => 'nullable|email|max:255',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|integer',
            'cep' => 'nullable|string|max:10',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'conta_pai_id' => 'required|exists:contas,id_conta',
            'permissoes' => 'nullable|json',
        ]);

        // Upload de imagem
        if ($request->hasFile('imagem')) {
            $imagemPath = $request->file('imagem')->store('subcontas', 'public');
            $validated['imagem'] = $imagemPath;
        }

        // Hash da senha
        $validated['senha'] = Hash::make($validated['senha']);

        // Criar subconta
        $subconta = SubConta::create($validated);

        return response()->json($subconta, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $subconta = SubConta::with('contaPai')->findOrFail($id);
        return response()->json($subconta);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $subconta = SubConta::findOrFail($id);

        $atual_numero = $subconta->numero_sub_conta;

        $validated = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255',
                Rule::unique('sub_contas')->ignore($id, 'id_sub_contas')],
            'cpf' => ['sometimes', 'string', 'max:14',
                Rule::unique('sub_contas')->ignore($id, 'id_sub_contas')],
            // Se o número não estiver sendo alterado, não precisamos validar
            'numero_sub_conta' => [
                'sometimes', 'string', 'max:20',
                function ($attribute, $value, $fail) use ($atual_numero, $id) {
                    // Se o número não foi alterado, não precisa validar
                    if ($value == $atual_numero) {
                        return;
                    }

                    // Verifica manualmente se existe outro registro com o mesmo número
                    $exists = SubConta::where('numero_sub_conta', $value)
                        ->where('id_sub_contas', '!=', $id)
                        ->exists();

                    if ($exists) {
                        $fail('O número da subconta já está em uso.');
                    }
                }
            ],
            'senha' => 'nullable|string|min:6',
            'imagem' => 'nullable|image|max:2048',
            'status_conta' => 'nullable|boolean',
            'reputacao' => 'nullable|numeric',
            'telefone' => 'nullable|string|max:20',
            'celular' => 'nullable|string|max:20',
            'email_contato' => 'nullable|email|max:255',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|integer',
            'cep' => 'nullable|string|max:10',
            'complemento' => 'nullable|string|max:255',
            'bairro' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:2',
            'permissoes' => 'nullable|json',
        ]);

        // Upload de imagem
        if ($request->hasFile('imagem')) {
            // Remover imagem anterior se existir
            if ($subconta->imagem) {
                Storage::disk('public')->delete($subconta->imagem);
            }

            $imagemPath = $request->file('imagem')->store('subcontas', 'public');
            $validated['imagem'] = $imagemPath;
        }

        // Hash da senha se fornecida
        if (isset($validated['senha'])) {
            $validated['senha'] = Hash::make($validated['senha']);
        }

        // Atualizar subconta
        $subconta->update($validated);

        return response()->json($subconta);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $subconta = SubConta::findOrFail($id);

        // Salvar informações da subconta antes de excluir
        $nomeSubconta = $subconta->nome;
        $numeroSubconta = $subconta->numero_sub_conta;

        // Verificar se há transações ou ofertas associadas
        if ($subconta->transacoesComprador()->exists() || $subconta->transacoesVendedor()->exists() || $subconta->ofertas()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir esta subconta pois existem transações ou ofertas associadas',
            ], 400);
        }

        // Remover imagem se existir
        if ($subconta->imagem) {
            Storage::disk('public')->delete($subconta->imagem);
        }

        $subconta->delete();

        // Retornar mensagem de sucesso com código 200 em vez de 204
        return response()->json([
            'message' => "Subconta '{$nomeSubconta}' (Número: {$numeroSubconta}) excluída com sucesso",
            'success' => true,
            'id' => $id
        ], 200);
    }

    /**
     * Ativar/desativar subconta
     */
    public function toggleStatus(int $id)
    {
        $subconta = SubConta::findOrFail($id);
        $subconta->status_conta = !$subconta->status_conta;
        $subconta->save();

        return response()->json([
            'message' => $subconta->status_conta ? 'Subconta ativada com sucesso' : 'Subconta desativada com sucesso',
            'subconta' => $subconta
        ]);
    }

    /**
     * Redefinir senha da subconta
     */
    public function resetarSenha(Request $request, int $id)
    {
        $request->validate([
            'novaSenha' => 'required|string|min:6',
        ]);

        $subconta = SubConta::findOrFail($id);
        $subconta->senha = Hash::make($request->novaSenha);
        $subconta->token_reset_senha = null;
        $subconta->save();

        return response()->json([
            'message' => 'Senha redefinida com sucesso',
        ]);
    }

    /**
     * Atualizar permissões da subconta
     */
    public function atualizarPermissoes(Request $request, int $id)
    {
        // Verificamos o tipo de dado recebido
        if (is_array($request->permissoes)) {
            // Se for um array, validamos como array
            $request->validate([
                'permissoes' => 'required|array',
            ]);
            // Convertemos para JSON para salvar
            $permissoes = json_encode($request->permissoes);
        } else {
            // Se for uma string, validamos como JSON
            $request->validate([
                'permissoes' => 'required|json',
            ]);
            $permissoes = $request->permissoes;
        }

        $subconta = SubConta::findOrFail($id);
        $subconta->permissoes = $permissoes;
        $subconta->save();

        return response()->json([
            'message' => 'Permissões atualizadas com sucesso',
            'success' => true,
            'subconta' => $subconta
        ]);
    }
}
