<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\Transacao;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Voucher::query();

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Relacionamentos
        $query->with('transacao');

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');

        // Verificar se a coluna existe antes de ordenar
        if ($orderBy === 'created_at' && !$query->getModel()->timestamps) {
            $orderBy = 'id_voucher'; // Usar primary key se não houver timestamps
        }
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        // Verificar se há vouchers
        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhum voucher encontrado para os filtros aplicados',
                'data' => $results,
                'summary' => [
                    'total_vouchers' => 0,
                    'filtros_aplicados' => [
                        'status' => $request->input('status')
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vouchers encontrados com sucesso',
            'data' => $results,
            'summary' => [
                'total_vouchers' => $results->total(),
                'vouchers_pagina' => $results->count(),
                'pagina_atual' => $results->currentPage()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transacao_id' => 'required|exists:transacoes,id_transacao',
            'status' => 'nullable|string|in:Ativo,Utilizado,Cancelado',
        ]);

        // Verificar se a transação existe
        $transacao = Transacao::find($validated['transacao_id']);
        if (!$transacao) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada',
                'transacao_id' => $validated['transacao_id']
            ], 404);
        }

        // Verificar se a transação já tem voucher
        $existente = Voucher::where('transacao_id', $validated['transacao_id'])->first();
        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => 'Esta transação já possui um voucher ativo',
                'voucher_existente' => [
                    'id' => $existente->id_voucher,
                    'codigo' => $existente->codigo,
                    'status' => $existente->status
                ]
            ], 400);
        }

        // Verificar se a transação está habilitada para emitir voucher
        if (!$transacao->emiteVoucher) {
            return response()->json([
                'success' => false,
                'message' => 'Esta transação não está configurada para emitir voucher',
                'transacao' => [
                    'id' => $transacao->id_transacao,
                    'emite_voucher' => $transacao->emiteVoucher
                ]
            ], 400);
        }

        // Verificar se a transação foi concluída
        if ($transacao->status !== 'concluida') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas transações concluídas podem gerar vouchers',
                'status_atual' => $transacao->status,
                'status_necessario' => 'concluida'
            ], 400);
        }

        // Gerar código único
        $validated['codigo'] = Str::uuid();

        // Status padrão se não fornecido
        $validated['status'] = $validated['status'] ?? 'Ativo';

        // Criar voucher
        $voucher = Voucher::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher criado com sucesso',
            'voucher' => $voucher,
            'detalhes' => [
                'codigo_gerado' => $voucher->codigo,
                'status' => $voucher->status,
                'transacao_vinculada' => $validated['transacao_id']
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $voucher = Voucher::with(['transacao', 'transacao.oferta', 'transacao.comprador', 'transacao.vendedor'])
            ->find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher não encontrado',
                'voucher_id' => $id
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher encontrado com sucesso',
            'voucher' => $voucher
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher não encontrado',
                'voucher_id' => $id
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:Ativo,Utilizado,Cancelado',
            'data_cancelamento' => 'nullable|date',
        ]);

        // Verificar transições de status válidas
        if ($voucher->status === 'Utilizado' && $validated['status'] !== 'Utilizado') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível alterar o status de um voucher já utilizado',
                'status_atual' => $voucher->status,
                'status_solicitado' => $validated['status']
            ], 400);
        }

        if ($voucher->status === 'Cancelado' && $validated['status'] === 'Ativo') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível reativar um voucher cancelado',
                'status_atual' => $voucher->status
            ], 400);
        }

        // Se status for Cancelado, definir data de cancelamento automaticamente
        if ($validated['status'] === 'Cancelado' && !isset($validated['data_cancelamento'])) {
            $validated['data_cancelamento'] = now();
        }

        $statusAnterior = $voucher->status;
        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher atualizado com sucesso',
            'voucher' => $voucher,
            'alteracoes' => [
                'status_anterior' => $statusAnterior,
                'status_atual' => $voucher->status,
                'data_cancelamento' => $voucher->data_cancelamento
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher não encontrado',
                'voucher_id' => $id
            ], 404);
        }

        if ($voucher->status === 'Utilizado') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível cancelar um voucher já utilizado',
                'status_atual' => $voucher->status,
                'codigo' => $voucher->codigo
            ], 400);
        }

        if ($voucher->status === 'Cancelado') {
            return response()->json([
                'success' => false,
                'message' => 'Este voucher já está cancelado',
                'status_atual' => $voucher->status,
                'cancelado_em' => $voucher->data_cancelamento
            ], 400);
        }

        // Armazenar informações antes de cancelar
        $informacoesVoucher = [
            'id' => $voucher->id_voucher,
            'codigo' => $voucher->codigo,
            'status_anterior' => $voucher->status,
            'transacao_id' => $voucher->transacao_id
        ];

        $voucher->status = 'Cancelado';
        $voucher->data_cancelamento = now();
        $voucher->save();

        return response()->json([
            'success' => true,
            'message' => "Voucher '{$voucher->codigo}' cancelado com sucesso",
            'voucher_cancelado' => $informacoesVoucher,
            'cancelado_em' => now()->toISOString()
        ]);
    }

    /**
     * Get vouchers by transacao
     */
    public function getByTransacao(int $transacaoId)
    {
        $transacao = Transacao::find($transacaoId);

        if (!$transacao) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada',
                'transacao_id' => $transacaoId
            ], 404);
        }

        $vouchers = Voucher::where('transacao_id', $transacaoId)
            ->orderBy('id_voucher', 'desc')
            ->get();

        if ($vouchers->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhum voucher encontrado para esta transação',
                'vouchers' => $vouchers,
                'transacao_id' => $transacaoId
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vouchers encontrados para a transação',
            'vouchers' => $vouchers,
            'total' => $vouchers->count()
        ]);
    }

    /**
     * Validar voucher
     */
    public function validar(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string',
        ]);

        $voucher = Voucher::where('codigo', $request->codigo)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'valido' => false,
                'message' => 'Voucher não encontrado',
                'codigo_informado' => $request->codigo
            ], 404);
        }

        if ($voucher->status !== 'Ativo') {
            $mensagemStatus = [
                'Utilizado' => 'Voucher já foi utilizado',
                'Cancelado' => 'Voucher foi cancelado'
            ];

            return response()->json([
                'success' => false,
                'valido' => false,
                'message' => $mensagemStatus[$voucher->status] ?? 'Voucher não está ativo',
                'status_atual' => $voucher->status,
                'codigo' => $voucher->codigo
            ], 400);
        }

        // Carregar dados relacionados
        $voucher->load(['transacao', 'transacao.oferta', 'transacao.comprador', 'transacao.vendedor']);

        return response()->json([
            'success' => true,
            'valido' => true,
            'message' => 'Voucher válido e ativo',
            'voucher' => $voucher
        ]);
    }

    /**
     * Utilizar voucher
     */
    public function utilizar(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string',
        ]);

        $voucher = Voucher::where('codigo', $request->codigo)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher não encontrado',
                'codigo_informado' => $request->codigo
            ], 404);
        }

        if ($voucher->status !== 'Ativo') {
            $mensagemStatus = [
                'Utilizado' => 'Voucher já foi utilizado anteriormente',
                'Cancelado' => 'Voucher foi cancelado e não pode ser utilizado'
            ];

            return response()->json([
                'success' => false,
                'message' => $mensagemStatus[$voucher->status] ?? 'Voucher não está disponível para uso',
                'status_atual' => $voucher->status,
                'codigo' => $voucher->codigo
            ], 400);
        }

        // Marcar como utilizado
        $voucher->status = 'Utilizado';
        $voucher->save();

        return response()->json([
            'success' => true,
            'message' => 'Voucher utilizado com sucesso',
            'voucher' => $voucher->fresh(),
            'detalhes' => [
                'codigo' => $voucher->codigo,
                'utilizado_em' => now()->toISOString(),
                'transacao_id' => $voucher->transacao_id
            ]
        ]);
    }
}
