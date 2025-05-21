<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cobranca;
use App\Models\Transacao;
use App\Models\Conta;
use App\Models\Usuario;
use App\Models\SubConta;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CobrancaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cobranca::query();

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->has('conta_id')) {
            $query->where('conta_id', $request->conta_id);
        }

        if ($request->has('sub_conta_id')) {
            $query->where('sub_conta_id', $request->sub_conta_id);
        }

        if ($request->has('gerente_conta_id')) {
            $query->where('gerente_conta_id', $request->gerente_conta_id);
        }

        if ($request->has('vencimento_inicio') && $request->has('vencimento_fim')) {
            $query->whereBetween('vencimento_fatura', [$request->vencimento_inicio, $request->vencimento_fim]);
        } elseif ($request->has('vencimento_inicio')) {
            $query->where('vencimento_fatura', '>=', $request->vencimento_inicio);
        } elseif ($request->has('vencimento_fim')) {
            $query->where('vencimento_fatura', '<=', $request->vencimento_fim);
        }

        // Relacionamentos
        $query->with(['transacao', 'usuario', 'conta', 'subConta', 'gerente']);

        // Ordenação
        $orderBy = $request->input('order_by', 'vencimento_fatura');
        $orderDirection = $request->input('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        // Verificar se há resultados
        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma cobrança encontrada para os filtros aplicados',
                'data' => $results,
                'filtros' => [
                    'status' => $request->status ?? 'todos',
                    'usuario_id' => $request->usuario_id ?? 'todos',
                    'conta_id' => $request->conta_id ?? 'todos',
                    'sub_conta_id' => $request->sub_conta_id ?? 'todos',
                    'gerente_conta_id' => $request->gerente_conta_id ?? 'todos',
                    'vencimento' => $request->has('vencimento_inicio') || $request->has('vencimento_fim') ?
                        'filtrado' : 'todos'
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cobranças encontradas com sucesso',
            'data' => $results,
            'total' => $results->total(),
            'pagina_atual' => $results->currentPage(),
            'total_paginas' => $results->lastPage()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'valor_fatura' => 'required|numeric|min:0',
            'referencia' => 'required|string',
            'status' => 'nullable|string|in:Pendente,Pago,Atrasado,Cancelado',
            'transacao_id' => 'nullable|exists:transacoes,id_transacao',
            'usuario_id' => 'nullable|exists:usuarios,id_usuario',
            'conta_id' => 'nullable|exists:contas,id_conta',
            'vencimento_fatura' => 'required|date',
            'sub_conta_id' => 'nullable|exists:sub_contas,id_sub_contas',
            'gerente_conta_id' => 'nullable|exists:usuarios,id_usuario',
        ]);

        // Status padrão
        $validated['status'] = $validated['status'] ?? 'Pendente';

        // Data de criação
        $validated['created_at'] = now();

        // Criar cobrança
        $cobranca = Cobranca::create($validated);

        // Dados relacionados para retorno mais informativo
        $detalhes = [
            'usuario' => $cobranca->usuario ? $cobranca->usuario->nome : null,
            'conta' => $cobranca->conta ? 'Conta #' . $cobranca->conta->id_conta : null,
            'transacao' => $cobranca->transacao ? 'Transação #' . $cobranca->transacao->id_transacao : null,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Cobrança criada com sucesso',
            'cobranca' => $cobranca,
            'detalhes' => $detalhes
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $cobranca = Cobranca::with(['transacao', 'usuario', 'conta', 'subConta', 'gerente'])
            ->find($id);

        if (!$cobranca) {
            return response()->json([
                'success' => false,
                'message' => 'Cobrança não encontrada',
                'erro' => 'Não existe cobrança com o ID ' . $id,
                'sugestoes' => [
                    'Verifique se o ID está correto',
                    'Use o endpoint GET /api/cobrancas para listar todas as cobranças disponíveis',
                    'Tente criar uma nova cobrança com POST /api/cobrancas'
                ]
            ], 404);
        }

        // Formatar dados para retorno mais informativo
        $dataVencimento = Carbon::parse($cobranca->vencimento_fatura);
        $hoje = Carbon::today();
        $diasRestantes = $hoje->diffInDays($dataVencimento, false);
        $situacaoVencimento = $diasRestantes < 0 ? 'vencida' : ($diasRestantes === 0 ? 'vence hoje' : 'a vencer');

        return response()->json([
            'success' => true,
            'message' => 'Cobrança encontrada com sucesso',
            'cobranca' => $cobranca,
            'resumo' => [
                'id' => $cobranca->id_cobranca,
                'valor' => 'R$ ' . number_format($cobranca->valor_fatura, 2, ',', '.'),
                'status' => $cobranca->status,
                'vencimento' => $dataVencimento->format('d/m/Y'),
                'situacao' => $situacaoVencimento,
                'dias_restantes' => $diasRestantes >= 0 ? $diasRestantes : 'vencido há ' . abs($diasRestantes) . ' dia(s)',
                'cliente' => $cobranca->usuario ? $cobranca->usuario->nome : 'N/A',
                'referencia' => $cobranca->referencia
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $cobranca = Cobranca::find($id);

        if (!$cobranca) {
            return response()->json([
                'success' => false,
                'message' => 'Cobrança não encontrada',
                'erro' => 'Não existe cobrança com o ID ' . $id,
                'sugestoes' => [
                    'Verifique se o ID está correto',
                    'Use o endpoint GET /api/cobrancas para listar todas as cobranças disponíveis'
                ]
            ], 404);
        }

        $validated = $request->validate([
            'valor_fatura' => 'sometimes|numeric|min:0',
            'referencia' => 'sometimes|string',
            'status' => 'sometimes|string|in:Pendente,Pago,Atrasado,Cancelado',
            'vencimento_fatura' => 'sometimes|date',
        ]);

        $statusAnterior = $cobranca->status;
        $valorAnterior = $cobranca->valor_fatura;
        $vencimentoAnterior = $cobranca->vencimento_fatura;

        $cobranca->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cobrança atualizada com sucesso',
            'cobranca' => $cobranca,
            'alteracoes' => [
                'status' => $statusAnterior !== $cobranca->status ?
                    ['anterior' => $statusAnterior, 'atual' => $cobranca->status] : null,
                'valor' => $valorAnterior !== $cobranca->valor_fatura ?
                    ['anterior' => $valorAnterior, 'atual' => $cobranca->valor_fatura] : null,
                'vencimento' => $vencimentoAnterior !== $cobranca->vencimento_fatura ?
                    ['anterior' => $vencimentoAnterior, 'atual' => $cobranca->vencimento_fatura] : null
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $cobranca = Cobranca::find($id);

        if (!$cobranca) {
            return response()->json([
                'success' => false,
                'message' => 'Cobrança não encontrada',
                'erro' => 'Não existe cobrança com o ID ' . $id
            ], 404);
        }

        // Armazenar informações antes de cancelar
        $infoCobranca = [
            'id' => $cobranca->id_cobranca,
            'valor' => $cobranca->valor_fatura,
            'referencia' => $cobranca->referencia,
            'status_anterior' => $cobranca->status,
            'cliente' => $cobranca->usuario ? $cobranca->usuario->nome : 'N/A'
        ];

        // Em vez de excluir, marcar como cancelada
        $cobranca->status = 'Cancelado';
        $cobranca->save();

        return response()->json([
            'success' => true,
            'message' => 'Cobrança cancelada com sucesso',
            'cobranca_cancelada' => $infoCobranca,
            'cancelada_em' => now()->toISOString()
        ]);
    }

    /**
     * Gerar cobrança de transação
     */
    public function gerarCobrancaTransacao(Request $request)
    {
        $validated = $request->validate([
            'transacao_id' => 'required|exists:transacoes,id_transacao',
            'vencimento_fatura' => 'required|date',
            'referencia' => 'required|string',
        ]);

        $transacao = Transacao::find($validated['transacao_id']);

        if (!$transacao) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada',
                'transacao_id' => $validated['transacao_id']
            ], 404);
        }

        // Verificar se já existe cobrança para esta transação
        $existente = Cobranca::where('transacao_id', $transacao->id_transacao)->first();
        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => 'Já existe uma cobrança para esta transação',
                'cobranca_existente' => [
                    'id' => $existente->id,
                    'status' => $existente->status,
                    'valor' => $existente->valor_fatura,
                    'vencimento' => Carbon::parse($existente->vencimento_fatura)->format('d/m/Y')
                ]
            ], 400);
        }

        // Criar cobrança
        $cobranca = Cobranca::create([
            'valor_fatura' => $transacao->valor_rt,
            'referencia' => $validated['referencia'],
            'created_at' => now(),
            'status' => 'Pendente',
            'transacao_id' => $transacao->id_transacao,
            'usuario_id' => $transacao->comprador_id,
            'vencimento_fatura' => $validated['vencimento_fatura'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cobrança gerada com sucesso',
            'cobranca' => $cobranca,
            'detalhes' => [
                'valor' => 'R$ ' . number_format($cobranca->valor_fatura, 2, ',', '.'),
                'vencimento' => Carbon::parse($cobranca->vencimento_fatura)->format('d/m/Y'),
                'cliente' => $transacao->nome_comprador,
                'transacao' => 'Transação #' . $transacao->id_transacao
            ]
        ], 201);
    }

    /**
     * Atualizar status de cobrança
     */
    public function atualizarStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Pendente,Pago,Atrasado,Cancelado',
        ]);

        $cobranca = Cobranca::find($id);

        if (!$cobranca) {
            return response()->json([
                'success' => false,
                'message' => 'Cobrança não encontrada',
                'erro' => 'Não existe cobrança com o ID ' . $id
            ], 404);
        }

        if ($cobranca->status === $validated['status']) {
            return response()->json([
                'success' => false,
                'message' => 'A cobrança já está com este status',
                'status_atual' => $cobranca->status
            ], 400);
        }

        $statusAnterior = $cobranca->status;
        $cobranca->status = $validated['status'];
        $cobranca->save();

        return response()->json([
            'success' => true,
            'message' => 'Status atualizado com sucesso',
            'cobranca' => $cobranca,
            'alteracao' => [
                'status_anterior' => $statusAnterior,
                'status_atual' => $cobranca->status,
                'alterado_em' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Gerar cobranças mensais
     */
    public function gerarCobrancasMensais(Request $request)
    {
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000',
            'diaVencimento' => 'required|integer|min:1|max:31',
        ]);

        $contas = Conta::all();

        if ($contas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Não há contas cadastradas no sistema',
                'sugestao' => 'Cadastre contas antes de gerar cobranças mensais'
            ], 400);
        }

        $cobrancasGeradas = [];
        $contasIgnoradas = [];
        $mesNome = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];

        foreach ($contas as $conta) {
            // Verificar se a conta já tem cobrança para este mês/ano
            $referencia = "Mensalidade {$mesNome[$validated['mes']]}/{$validated['ano']}";

            $existente = Cobranca::where('conta_id', $conta->id_conta)
                ->where('referencia', $referencia)
                ->first();

            if ($existente) {
                $contasIgnoradas[] = [
                    'conta_id' => $conta->id_conta,
                    'motivo' => 'Já possui cobrança para este mês/ano',
                    'cobranca_existente' => $existente->id
                ];
                continue; // Pular esta conta
            }

            // Criar data de vencimento
            $vencimento = Carbon::create($validated['ano'], $validated['mes'], $validated['diaVencimento']);

            // Criar cobrança
            $cobranca = Cobranca::create([
                'valor_fatura' => 100, // Valor padrão, ajustar conforme necessário
                'referencia' => $referencia,
                'created_at' => now(),
                'status' => 'Pendente',
                'conta_id' => $conta->id_conta,
                'usuario_id' => $conta->usuario_id,
                'vencimento_fatura' => $vencimento,
                'gerente_conta_id' => $conta->gerente_conta_id,
            ]);

            $cobrancasGeradas[] = [
                'id' => $cobranca->id,
                'conta_id' => $conta->id_conta,
                'valor' => $cobranca->valor_fatura,
                'vencimento' => $vencimento->format('d/m/Y')
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Cobranças mensais geradas com sucesso',
            'resumo' => [
                'periodo' => "{$mesNome[$validated['mes']]}/{$validated['ano']}",
                'vencimento' => $validated['diaVencimento'],
                'total_geradas' => count($cobrancasGeradas),
                'total_ignoradas' => count($contasIgnoradas)
            ],
            'cobrancas_geradas' => $cobrancasGeradas,
            'contas_ignoradas' => $contasIgnoradas
        ]);
    }

    /**
     * Cobranças vencidas
     */
    public function cobrancasVencidas()
    {
        $hoje = Carbon::today();

        $cobrancas = Cobranca::where('status', 'Pendente')
            ->where('vencimento_fatura', '<', $hoje)
            ->with(['usuario', 'conta'])
            ->get();

        if ($cobrancas->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Não há cobranças vencidas pendentes',
                'data_referencia' => $hoje->format('d/m/Y')
            ]);
        }

        // Atualizar status para Atrasado
        $atualizadas = [];
        foreach ($cobrancas as $cobranca) {
            $diasAtraso = $hoje->diffInDays(Carbon::parse($cobranca->vencimento_fatura));

            $atualizadas[] = [
                'id' => $cobranca->id,
                'valor' => $cobranca->valor_fatura,
                'vencimento' => Carbon::parse($cobranca->vencimento_fatura)->format('d/m/Y'),
                'dias_atraso' => $diasAtraso,
                'cliente' => $cobranca->usuario ? $cobranca->usuario->nome : 'N/A'
            ];

            $cobranca->status = 'Atrasado';
            $cobranca->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cobranças atualizadas para Atrasado',
            'resumo' => [
                'data_referencia' => $hoje->format('d/m/Y'),
                'total_atualizadas' => count($atualizadas)
            ],
            'cobrancas_atualizadas' => $atualizadas
        ]);
    }
}
