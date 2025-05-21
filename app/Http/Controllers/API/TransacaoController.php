<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transacao;
use App\Models\Parcelamento;
use App\Models\Conta;
use App\Models\Oferta;
use App\Models\Usuario;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransacaoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado'
            ], 401);
        }

        $query = Transacao::query();

        // Filtrar transações baseado no tipo de usuário
        if ($request->has('tipo') && in_array($request->tipo, ['comprador', 'vendedor', 'todos'])) {
            if ($request->tipo === 'comprador') {
                $query->where('comprador_id', $usuario->id_usuario);
            } elseif ($request->tipo === 'vendedor') {
                $query->where('vendedor_id', $usuario->id_usuario);
            } else {
                $query->where(function ($q) use ($usuario) {
                    $q->where('comprador_id', $usuario->id_usuario)
                        ->orWhere('vendedor_id', $usuario->id_usuario);
                });
            }
        } else {
            // Por padrão, mostrar todas as transações do usuário
            $query->where(function ($q) use ($usuario) {
                $q->where('comprador_id', $usuario->id_usuario)
                    ->orWhere('vendedor_id', $usuario->id_usuario);
            });
        }

        // Filtrar por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por período
        if ($request->has('data_inicio') && $request->has('data_fim')) {
            $query->whereBetween('created_at', [$request->data_inicio, $request->data_fim]);
        }

        // Relacionamentos
        $query->with(['comprador', 'vendedor', 'oferta', 'parcelamento']);

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        // Verificar se há transações
        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma transação encontrada para os filtros aplicados',
                'data' => $results,
                'summary' => [
                    'total_transacoes' => 0,
                    'filtros_aplicados' => [
                        'tipo' => $request->input('tipo', 'todos'),
                        'status' => $request->input('status'),
                        'periodo' => $request->has('data_inicio') && $request->has('data_fim')
                            ? $request->data_inicio . ' até ' . $request->data_fim
                            : null
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transações encontradas com sucesso',
            'data' => $results,
            'summary' => [
                'total_transacoes' => $results->total(),
                'transacoes_pagina' => $results->count(),
                'pagina_atual' => $results->currentPage(),
                'total_paginas' => $results->lastPage()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                'oferta_id' => 'required|exists:ofertas,id_oferta',
                'comprador_id' => 'required|exists:usuarios,id_usuario',
                'numeroParcelas' => 'required|integer|min:1',
                'saldo_utilizado' => 'required|in:permuta,crédito,dinheiro',
                'valor_adicional' => 'nullable|numeric|min:0',
                'descricao' => 'required|string',
                'emiteVoucher' => 'nullable|boolean',
                'codigo_voucher' => 'nullable|string|max:255|unique:vouchers,codigo', // Campo para código personalizado
            ]);

            // Verificar se código de voucher foi informado mas emiteVoucher é false
            if (isset($validated['codigo_voucher']) && !($validated['emiteVoucher'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Para usar código personalizado de voucher, \'emiteVoucher\' deve ser true',
                    'codigo_informado' => $validated['codigo_voucher']
                ], 400);
            }

            // Obter dados da oferta
            $oferta = Oferta::findOrFail($validated['oferta_id']);

            // Verificar se a oferta está ativa
            if (!$oferta->status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta oferta não está mais disponível'
                ], 400);
            }

            // Verificar se há quantidade disponível
            if ($oferta->quantidade <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta oferta não possui mais quantidade disponível'
                ], 400);
            }

            $validated['vendedor_id'] = $oferta->usuario_id;
            $validated['nome_comprador'] = Usuario::findOrFail($validated['comprador_id'])->nome;
            $validated['nome_vendedor'] = Usuario::findOrFail($oferta->usuario_id)->nome;
            $validated['valor_rt'] = $oferta->valor;
            $validated['valor_adicional'] = $validated['valor_adicional'] ?? 0;
            $validated['notaAtendimento'] = 0;
            $validated['observacaoNota'] = '';
            $validated['status'] = 'pendente';
            $validated['emiteVoucher'] = $validated['emiteVoucher'] ?? false;

            // Verificar se comprador e vendedor são diferentes
            if ($validated['comprador_id'] == $oferta->usuario_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não pode comprar sua própria oferta'
                ], 400);
            }

            // Cálculo de comissão
            $vendedor = Usuario::findOrFail($oferta->usuario_id);
            $compradorConta = Conta::where('usuario_id', $validated['comprador_id'])->first();
            $vendedorConta = Conta::where('usuario_id', $oferta->usuario_id)->first();

            if (!$compradorConta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comprador não possui conta ativa no sistema'
                ], 400);
            }

            if (!$vendedorConta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendedor não possui conta ativa no sistema'
                ], 400);
            }

            // Determinar comissão
            $comissao = $this->calcularComissao($oferta, $vendedor);
            $validated['comissao'] = $comissao;
            $validated['comissaoParcelada'] = $comissao / $validated['numeroParcelas'];

            // Verificar e atualizar saldos conforme o tipo de pagamento
            if ($validated['saldo_utilizado'] === 'permuta') {
                if ($compradorConta->saldo_permuta < $oferta->valor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saldo de permuta insuficiente',
                        'saldo_atual' => $compradorConta->saldo_permuta,
                        'valor_necessario' => $oferta->valor,
                        'diferenca' => $oferta->valor - $compradorConta->saldo_permuta
                    ], 400);
                }

                $validated['saldo_anterior_comprador'] = $compradorConta->saldo_permuta;
                $validated['saldo_apos_comprador'] = $compradorConta->saldo_permuta - $oferta->valor;
                $validated['saldo_anterior_vendedor'] = $vendedorConta->saldo_permuta;
                $validated['saldoAposVendedor'] = $vendedorConta->saldo_permuta + ($oferta->valor - $comissao);

                $compradorConta->saldo_permuta -= $oferta->valor;
                $vendedorConta->saldo_permuta += ($oferta->valor - $comissao);

            } elseif ($validated['saldo_utilizado'] === 'crédito') {
                if ($compradorConta->limite_disponivel < $oferta->valor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Limite de crédito insuficiente',
                        'limite_disponivel' => $compradorConta->limite_disponivel,
                        'valor_necessario' => $oferta->valor,
                        'diferenca' => $oferta->valor - $compradorConta->limite_disponivel
                    ], 400);
                }

                $validated['limiteCreditoAnteriorComprador'] = $compradorConta->limite_disponivel;
                $validated['limiteCreditoAposComprador'] = $compradorConta->limite_disponivel - $oferta->valor;
                $validated['saldo_anterior_vendedor'] = $vendedorConta->saldo_permuta;
                $validated['saldoAposVendedor'] = $vendedorConta->saldo_permuta + ($oferta->valor - $comissao);

                $compradorConta->limite_utilizado += $oferta->valor;
                $compradorConta->limite_disponivel -= $oferta->valor;
                $vendedorConta->saldo_permuta += ($oferta->valor - $comissao);

            } elseif ($validated['saldo_utilizado'] === 'dinheiro') {
                if ($compradorConta->saldo_dinheiro < $oferta->valor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saldo em dinheiro insuficiente',
                        'saldo_atual' => $compradorConta->saldo_dinheiro,
                        'valor_necessario' => $oferta->valor,
                        'diferenca' => $oferta->valor - $compradorConta->saldo_dinheiro
                    ], 400);
                }

                $validated['saldo_anterior_comprador'] = $compradorConta->saldo_dinheiro;
                $validated['saldo_apos_comprador'] = $compradorConta->saldo_dinheiro - $oferta->valor;
                $validated['saldo_anterior_vendedor'] = $vendedorConta->saldo_dinheiro;
                $validated['saldoAposVendedor'] = $vendedorConta->saldo_dinheiro + ($oferta->valor - $comissao);

                $compradorConta->saldo_dinheiro -= $oferta->valor;
                $vendedorConta->saldo_dinheiro += ($oferta->valor - $comissao);
            }

            // Salvar as alterações nas contas
            $compradorConta->save();
            $vendedorConta->save();

            // Remover codigo_voucher dos dados da transação (não é campo da transação)
            $codigoVoucherPersonalizado = $validated['codigo_voucher'] ?? null;
            unset($validated['codigo_voucher']);

            // Criar a transação
            $transacao = Transacao::create($validated);

            // Criar as parcelas
            $this->criarParcelas($transacao);

            // Criar voucher se necessário
            $voucher = null;
            if ($validated['emiteVoucher']) {
                $voucher = $this->criarVoucher($transacao, $codigoVoucherPersonalizado);
            }

            // Atualizar quantidade da oferta
            $oferta->quantidade -= 1;
            if ($oferta->quantidade <= 0) {
                $oferta->status = false;
            }
            $oferta->save();

            return response()->json([
                'success' => true,
                'message' => 'Transação realizada com sucesso',
                'transacao' => $transacao->load(['parcelamento', 'voucher']),
                'summary' => [
                    'valor_transacao' => $oferta->valor,
                    'comissao' => $comissao,
                    'parcelas' => $validated['numeroParcelas'],
                    'tipo_pagamento' => $validated['saldo_utilizado'],
                    'voucher_emitido' => $validated['emiteVoucher'],
                    'codigo_voucher' => $voucher ? $voucher->codigo : null
                ]
            ], 201);
        });
    }

    public function show(int $id)
    {
        $usuario = request()->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado'
            ], 401);
        }

        $transacao = Transacao::with(['comprador', 'vendedor', 'oferta', 'parcelamento', 'voucher'])
            ->find($id);

        if (!$transacao) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada',
                'transacao_id' => $id
            ], 404);
        }

        // Verificar permissão: permitir se for admin ou se participou da transação
        $isAdmin = $usuario->tipo === 'admin' || $usuario->admin === true || $usuario->is_admin === true;
        $isParticipante = $transacao->comprador_id === $usuario->id_usuario ||
            $transacao->vendedor_id === $usuario->id_usuario;

        if (!$isAdmin && !$isParticipante) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para visualizar esta transação'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transação encontrada com sucesso',
            'transacao' => $transacao
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $transacao = Transacao::find($id);

        if (!$transacao) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada',
                'transacao_id' => $id
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|string|in:pendente,concluida,cancelada',
            'notaAtendimento' => 'sometimes|integer|min:0|max:5',
            'observacaoNota' => 'nullable|string',
        ]);

        $statusAnterior = $transacao->status;
        $transacao->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transação atualizada com sucesso',
            'transacao' => $transacao,
            'alteracoes' => [
                'status_anterior' => $statusAnterior,
                'status_atual' => $transacao->status
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $transacao = Transacao::find($id);

        if (!$transacao) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada',
                'transacao_id' => $id
            ], 404);
        }

        if ($transacao->status !== 'pendente') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas transações pendentes podem ser canceladas',
                'status_atual' => $transacao->status,
                'statuses_permitidos' => ['pendente']
            ], 400);
        }

        // Armazenar informações antes de cancelar
        $informacoesTransacao = [
            'id' => $transacao->id_transacao,
            'valor' => $transacao->valor_rt,
            'comprador' => $transacao->nome_comprador,
            'vendedor' => $transacao->nome_vendedor,
            'status_anterior' => $transacao->status
        ];

        $transacao->status = 'cancelada';
        $transacao->data_cancelamento = now();
        $transacao->save();

        return response()->json([
            'success' => true,
            'message' => "Transação #{$id} cancelada com sucesso",
            'transacao_cancelada' => $informacoesTransacao,
            'cancelada_em' => now()->toISOString()
        ]);
    }

    /**
     * Estornar transação
     */
    public function estornar(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $transacao = Transacao::find($id);

            if (!$transacao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transação não encontrada',
                    'transacao_id' => $id
                ], 404);
            }

            if ($transacao->status === 'cancelada') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transação já está cancelada',
                    'status_atual' => $transacao->status,
                    'cancelada_em' => $transacao->data_do_estorno ?? $transacao->updated_at
                ], 400);
            }

            $validated = $request->validate([
                'motivo' => 'required|string|max:500'
            ]);

            // Obter contas relacionadas
            $compradorConta = Conta::where('usuario_id', $transacao->comprador_id)->first();
            $vendedorConta = Conta::where('usuario_id', $transacao->vendedor_id)->first();

            if (!$compradorConta || !$vendedorConta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao encontrar contas dos usuários para estorno'
                ], 400);
            }

            // Reverter os saldos com base no tipo de pagamento
            if ($transacao->saldo_utilizado === 'permuta') {
                $compradorConta->saldo_permuta += $transacao->valor_rt;
                $vendedorConta->saldo_permuta -= ($transacao->valor_rt - $transacao->comissao);
            } elseif ($transacao->saldo_utilizado === 'crédito') {
                $compradorConta->limite_utilizado -= $transacao->valor_rt;
                $compradorConta->limite_disponivel += $transacao->valor_rt;
                $vendedorConta->saldo_permuta -= ($transacao->valor_rt - $transacao->comissao);
            } elseif ($transacao->saldo_utilizado === 'dinheiro') {
                $compradorConta->saldo_dinheiro += $transacao->valor_rt;
                $vendedorConta->saldo_dinheiro -= ($transacao->valor_rt - $transacao->comissao);
            }

            // Salvar alterações nas contas
            $compradorConta->save();
            $vendedorConta->save();

            // Cancelar vouchers relacionados
            if ($transacao->emiteVoucher) {
                $vouchersAtualizados = Voucher::where('transacao_id', $transacao->id_transacao)
                    ->update([
                        'status' => 'Cancelado',
                        'data_cancelamento' => now()
                    ]);
            }

            // Atualizar transação
            $transacao->status = 'cancelada';
            $transacao->data_do_estorno = now();
            $transacao->motivo_estorno = $validated['motivo'];
            $transacao->save();

            // Restaurar quantidade da oferta
            if ($transacao->oferta_id) {
                $oferta = Oferta::find($transacao->oferta_id);
                if ($oferta) {
                    $oferta->quantidade += 1;
                    if (!$oferta->status && $oferta->quantidade > 0) {
                        $oferta->status = true;
                    }
                    $oferta->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Transação estornada com sucesso',
                'transacao' => $transacao,
                'estorno_detalhes' => [
                    'valor_estornado' => $transacao->valor_rt,
                    'tipo_pagamento' => $transacao->saldo_utilizado,
                    'motivo' => $validated['motivo'],
                    'estornado_em' => now()->toISOString(),
                    'vouchers_cancelados' => $transacao->emiteVoucher ? 'Sim' : 'Não'
                ]
            ]);
        });
    }

    /**
     * Avaliar transação
     */
    public function avaliar(Request $request, int $id)
    {
        $validated = $request->validate([
            'notaAtendimento' => 'required|integer|min:1|max:5',
            'observacaoNota' => 'nullable|string|max:500',
        ]);

        $transacao = Transacao::find($id);

        if (!$transacao) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada',
                'transacao_id' => $id
            ], 404);
        }

        if ($transacao->status !== 'concluida') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas transações concluídas podem ser avaliadas',
                'status_atual' => $transacao->status,
                'status_necessario' => 'concluida'
            ], 400);
        }

        if ($transacao->notaAtendimento > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Esta transação já foi avaliada',
                'avaliacao_existente' => [
                    'nota' => $transacao->notaAtendimento,
                    'observacao' => $transacao->observacaoNota
                ]
            ], 400);
        }

        $transacao->update($validated);

        // Atualizar reputação do vendedor
        $vendedor = Usuario::find($transacao->vendedor_id);
        if ($vendedor) {
            $mediaAvaliacoes = Transacao::where('vendedor_id', $vendedor->id_usuario)
                ->whereNotNull('notaAtendimento')
                ->where('notaAtendimento', '>', 0)
                ->avg('notaAtendimento');

            $vendedor->reputacao = round($mediaAvaliacoes, 2);
            $vendedor->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Avaliação registrada com sucesso',
            'transacao' => $transacao,
            'avaliacao' => [
                'nota' => $validated['notaAtendimento'],
                'observacao' => $validated['observacaoNota'] ?? null,
                'nova_reputacao_vendedor' => $vendedor->reputacao ?? null
            ]
        ]);
    }

    // ... (métodos privados permanecem iguais)
    private function calcularComissao(Oferta $oferta, Usuario $vendedor)
    {
        $vendedorConta = Conta::where('usuario_id', $vendedor->id_usuario)->first();

        if (!$vendedorConta || !$vendedorConta->plano) {
            $taxaComissao = 10;
        } else {
            $taxaComissao = $vendedorConta->plano->taxa_comissao;
        }

        return ($oferta->valor * $taxaComissao) / 100;
    }

    private function criarParcelas(Transacao $transacao)
    {
        $valorParcela = $transacao->valor_rt / $transacao->numeroParcelas;
        $comissaoParcela = $transacao->comissao / $transacao->numeroParcelas;

        for ($i = 1; $i <= $transacao->numeroParcelas; $i++) {
            Parcelamento::create([
                'numero_parcela' => $i,
                'valor_parcela' => $valorParcela,
                'comissao_parcela' => $comissaoParcela,
                'transacao_id' => $transacao->id_transacao,
            ]);
        }
    }

    /**
     * Criar voucher
     */
    private function criarVoucher(Transacao $transacao, $codigoPersonalizado = null)
    {
        // Usar código personalizado se fornecido
        if ($codigoPersonalizado && !empty($codigoPersonalizado)) {
            $codigo = $codigoPersonalizado;
        } else {
            // Código padrão com prefixo VCH + ID da transação
            $codigo = 'VCH' . str_pad($transacao->id_transacao, 6, '0', STR_PAD_LEFT)
                . strtoupper(substr(uniqid(), -4));
        }

        // Criar o voucher com o código definido
        return Voucher::create([
            'transacao_id' => $transacao->id_transacao,
            'codigo' => $codigo,
            'status' => 'Ativo'
        ]);
    }
}
