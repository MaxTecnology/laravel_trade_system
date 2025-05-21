<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conta;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Conta::query();

        // Filtros
        if ($request->has('tipo_conta_id')) {
            $query->where('tipo_conta_id', $request->tipo_conta_id);
        }

        if ($request->has('plano_id')) {
            $query->where('plano_id', $request->plano_id);
        }

        if ($request->has('gerente_conta_id')) {
            $query->where('gerente_conta_id', $request->gerente_conta_id);
        }

        // Incluir relacionamentos
        $query->with(['usuario', 'tipo_da_conta', 'plano', 'gerente_conta']);

        // Ordenação
        $orderBy = $request->input('order_by', 'id_conta');
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
            'taxa_repasse_matriz' => 'nullable|integer',
            'limite_credito' => 'required|numeric',
            'limite_utilizado' => 'nullable|numeric',
            'limite_disponivel' => 'nullable|numeric',
            'saldo_permuta' => 'nullable|numeric',
            'saldo_dinheiro' => 'nullable|numeric',
            'limite_venda_mensal' => 'required|numeric',
            'limite_venda_total' => 'required|numeric',
            'limite_venda_empresa' => 'required|numeric',
            'valor_venda_mensal_atual' => 'nullable|numeric',
            'valor_venda_total_atual' => 'nullable|numeric',
            'dia_fechamento_fatura' => 'required|integer|min:1|max:31',
            'data_vencimento_fatura' => 'required|integer|min:1|max:31',
            'numero_conta' => 'required|string|unique:contas,numero_conta',
            'data_de_afiliacao' => 'nullable|date',
            'nome_franquia' => 'nullable|string|max:255',
            'tipo_conta_id' => 'nullable|exists:tipo_contas,id_tipo_conta',
            'usuario_id' => 'required|exists:usuarios,id_usuario|unique:contas,usuario_id',
            'plano_id' => 'nullable|exists:planos,id_plano',
            'gerente_conta_id' => 'nullable|exists:usuarios,id_usuario',
            'permissoes_especificas' => 'nullable|json',
        ]);

        // Calcular limite_disponivel se não fornecido
        if (!isset($validated['limite_disponivel'])) {
            $validated['limite_disponivel'] = $validated['limite_credito'] - ($validated['limite_utilizado'] ?? 0);
        }

        $conta = Conta::create($validated);

        return response()->json($conta, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $conta = Conta::with(['usuario', 'tipo_da_conta', 'plano', 'gerente_conta', 'sub_contas'])->findOrFail($id);
        return response()->json($conta);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $conta = Conta::findOrFail($id);

        // Definir os campos permitidos
        $allowedFields = [
            'taxa_repasse_matriz', 'limite_credito', 'limite_utilizado',
            'limite_disponivel', 'saldo_permuta', 'saldo_dinheiro',
            'limite_venda_mensal', 'limite_venda_total', 'limite_venda_empresa',
            'valor_venda_mensal_atual', 'valor_venda_total_atual',
            'dia_fechamento_fatura', 'data_vencimento_fatura', 'numero_conta',
            'data_de_afiliacao', 'nome_franquia', 'tipo_conta_id', 'plano_id',
            'gerente_conta_id', 'permissoes_especificas', 'status'
        ];

        // Verificar campos desconhecidos
        $unknownFields = array_diff(array_keys($request->all()), $allowedFields);

        if (!empty($unknownFields)) {
            return response()->json([
                'message' => 'Campos inválidos detectados',
                'invalid_fields' => $unknownFields
            ], 422);
        }

        // Proceder com a validação normal
        $validated = $request->validate([
            'taxa_repasse_matriz' => 'nullable|integer',
            'limite_credito' => 'sometimes|numeric',
            'limite_utilizado' => 'nullable|numeric',
            'limite_disponivel' => 'nullable|numeric',
            'saldo_permuta' => 'nullable|numeric',
            'saldo_dinheiro' => 'nullable|numeric',
            'limite_venda_mensal' => 'sometimes|numeric',
            'limite_venda_total' => 'sometimes|numeric',
            'limite_venda_empresa' => 'sometimes|numeric',
            'valor_venda_mensal_atual' => 'nullable|numeric',
            'valor_venda_total_atual' => 'nullable|numeric',
            'dia_fechamento_fatura' => 'sometimes|integer|min:1|max:31',
            'data_vencimento_fatura' => 'sometimes|integer|min:1|max:31',
            'numero_conta' => ['sometimes', 'string', Rule::unique('contas', 'numero_conta')->ignore($id, 'id_conta')],
            'data_de_afiliacao' => 'nullable|date',
            'nome_franquia' => 'nullable|string|max:255',
            'tipo_conta_id' => 'nullable|exists:tipo_contas,id_tipo_conta',
            'plano_id' => 'nullable|exists:planos,id_plano',
            'gerente_conta_id' => 'nullable|exists:usuarios,id_usuario',
            'permissoes_especificas' => 'nullable|json',
            'status' => 'sometimes|string|in:ativo,inativo,suspenso',
        ]);

        // Recalcular limite_disponivel se necessário
        if (isset($validated['limite_credito']) || isset($validated['limite_utilizado'])) {
            $limiteCredito = $validated['limite_credito'] ?? $conta->limite_credito;
            $limiteUtilizado = $validated['limite_utilizado'] ?? $conta->limite_utilizado;
            $validated['limite_disponivel'] = $limiteCredito - $limiteUtilizado;
        }

        $conta->update($validated);

        return response()->json($conta);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $conta = Conta::findOrFail($id);

        // Verificar dependências críticas antes de excluir

        $conta->delete();

        return response()->json(null, 204);
    }

    /**
     * Obter conta do usuário autenticado
     */
    public function minhaConta(Request $request)
    {
        $usuario = $request->user();

        $conta = Conta::with(['tipo_da_conta', 'plano', 'gerente_conta', 'sub_contas'])
            ->where('usuario_id', $usuario->id_usuario)
            ->firstOrFail();

        return response()->json($conta);
    }

    /**
     * Aumentar limite de crédito
     */
    public function aumentarLimite(Request $request, int $id)
    {
        $request->validate([
            'valorAumento' => 'required|numeric|min:0',
        ]);

        $conta = Conta::findOrFail($id);

        $novoLimite = $conta->limite_credito + $request->valorAumento;
        $novoLimiteDisponivel = $conta->limite_disponivel + $request->valorAumento;

        $conta->update([
            'limite_credito' => $novoLimite,
            'limite_disponivel' => $novoLimiteDisponivel,
        ]);

        return response()->json([
            'message' => 'Limite de crédito aumentado com sucesso',
            'conta' => $conta
        ]);
    }

    /**
     * Diminuir limite de crédito
     */
    public function diminuirLimite(Request $request, int $id)
    {
        $request->validate([
            'valorReducao' => 'required|numeric|min:0',
        ]);

        $conta = Conta::findOrFail($id);

        // Verificar se há saldo disponível suficiente para redução
        if ($conta->limite_disponivel < $request->valorReducao) {
            return response()->json([
                'message' => 'Limite disponível insuficiente para redução',
            ], 400);
        }

        $novoLimite = $conta->limite_credito - $request->valorReducao;
        $novoLimiteDisponivel = $conta->limite_disponivel - $request->valorReducao;

        $conta->update([
            'limite_credito' => $novoLimite,
            'limite_disponivel' => $novoLimiteDisponivel,
        ]);

        return response()->json([
            'message' => 'Limite de crédito reduzido com sucesso',
            'conta' => $conta
        ]);
    }

    /**
     * Atualizar saldo de permuta
     */
    public function atualizarSaldoPermuta(Request $request, int $id)
    {
        $request->validate([
            'valor' => 'required|numeric',
            'operacao' => 'required|in:adicionar,subtrair',
        ]);

        $conta = Conta::findOrFail($id);

        if ($request->operacao === 'adicionar') {
            $conta->saldo_permuta += $request->valor;
        } else {
            // Verificar se há saldo suficiente
            if ($conta->saldo_permuta < $request->valor) {
                return response()->json([
                    'message' => 'Saldo de permuta insuficiente',
                ], 400);
            }

            $conta->saldo_permuta -= $request->valor;
        }

        $conta->save();

        return response()->json([
            'message' => 'Saldo de permuta atualizado com sucesso',
            'conta' => $conta
        ]);
    }

    /**
     * Atualizar saldo em dinheiro
     */
    public function atualizarSaldoDinheiro(Request $request, int $id)
    {
        $request->validate([
            'valor' => 'required|numeric',
            'operacao' => 'required|in:adicionar,subtrair',
        ]);

        $conta = Conta::findOrFail($id);

        if ($request->operacao === 'adicionar') {
            $conta->saldo_dinheiro += $request->valor;
        } else {
            // Verificar se há saldo suficiente
            if ($conta->saldo_dinheiro < $request->valor) {
                return response()->json([
                    'message' => 'Saldo em dinheiro insuficiente',
                ], 400);
            }

            $conta->saldo_dinheiro -= $request->valor;
        }

        $conta->save();

        return response()->json([
            'message' => 'Saldo em dinheiro atualizado com sucesso',
            'conta' => $conta
        ]);
    }
}
