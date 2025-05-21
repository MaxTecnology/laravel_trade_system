<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FundoPermuta;
use App\Models\Conta;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FundoPermutaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FundoPermuta::query();

        // Filtros
        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->has('tipo')) {
            if ($request->tipo === 'credito') {
                $query->where('valor', '>', 0);
            } elseif ($request->tipo === 'debito') {
                $query->where('valor', '<', 0);
            }
        }

        if ($request->has('data_inicio') && $request->has('data_fim')) {
            $query->whereBetween('created_at', [$request->data_inicio, $request->data_fim]);
        } elseif ($request->has('data_inicio')) {
            $query->where('created_at', '>=', $request->data_inicio);
        } elseif ($request->has('data_fim')) {
            $query->where('created_at', '<=', $request->data_fim);
        }

        // Relacionamentos
        $query->with('usuario');

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        // Calcular totais
        $totalCreditos = FundoPermuta::when($request->has('usuario_id'), function ($q) use ($request) {
            $q->where('usuario_id', $request->usuario_id);
        })
            ->where('valor', '>', 0)
            ->sum('valor');

        $totalDebitos = FundoPermuta::when($request->has('usuario_id'), function ($q) use ($request) {
            $q->where('usuario_id', $request->usuario_id);
        })
            ->where('valor', '<', 0)
            ->sum('valor');

        $saldoTotal = $totalCreditos + $totalDebitos;

        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma movimentação de fundo de permuta encontrada',
                'data' => $results,
                'resumo' => [
                    'totalCreditos' => 0,
                    'totalDebitos' => 0,
                    'saldoTotal' => 0,
                    'filtros_aplicados' => [
                        'usuario_id' => $request->usuario_id ?? 'todos',
                        'tipo' => $request->tipo ?? 'todos',
                        'periodo' => $request->has('data_inicio') || $request->has('data_fim') ? 'filtrado' : 'todos'
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Movimentações de fundo de permuta encontradas',
            'data' => $results,
            'resumo' => [
                'totalCreditos' => $totalCreditos,
                'totalDebitos' => $totalDebitos,
                'saldoTotal' => $saldoTotal,
                'totalRegistros' => $results->total(),
                'paginaAtual' => $results->currentPage(),
                'totalPaginas' => $results->lastPage()
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
                'valor' => 'required|numeric|min:0.01',
                'usuario_id' => 'required|exists:usuarios,id_usuario',
                'tipo' => 'required|in:credito,debito',
                'descricao' => 'nullable|string|max:255'
            ]);

            // Buscar conta do usuário
            $conta = Conta::where('usuario_id', $validated['usuario_id'])->first();

            if (!$conta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não possui conta',
                    'erro' => 'Não existe conta associada ao usuário ID ' . $validated['usuario_id']
                ], 400);
            }

            // Buscar informações do usuário
            $usuario = Usuario::find($validated['usuario_id']);

            // Verificar se é uma adição ou remoção de fundo
            if ($validated['tipo'] === 'debito') {
                // Verificar se há saldo suficiente
                if ($conta->saldo_permuta < $validated['valor']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saldo de permuta insuficiente',
                        'saldo_atual' => $conta->saldo_permuta,
                        'valor_solicitado' => $validated['valor'],
                        'diferenca' => $validated['valor'] - $conta->saldo_permuta
                    ], 400);
                }

                // Armazenar saldo anterior
                $saldoAnterior = $conta->saldo_permuta;

                // Inverter o valor para salvar como negativo
                $valorFundo = -$validated['valor'];

                // Atualizar saldo da conta
                $conta->saldo_permuta -= $validated['valor'];

                $mensagemSucesso = 'Débito realizado com sucesso no fundo de permuta';
            } else {
                // Armazenar saldo anterior
                $saldoAnterior = $conta->saldo_permuta;

                $valorFundo = $validated['valor'];

                // Atualizar saldo da conta
                $conta->saldo_permuta += $validated['valor'];

                $mensagemSucesso = 'Crédito adicionado com sucesso ao fundo de permuta';
            }

            $conta->save();

            // Criar registro de fundo
            $fundo = FundoPermuta::create([
                'valor' => $valorFundo,
                'usuario_id' => $validated['usuario_id'],
                'descricao' => $validated['descricao'] ?? ($validated['tipo'] === 'credito' ? 'Crédito adicionado' : 'Débito realizado')
            ]);

            return response()->json([
                'success' => true,
                'message' => $mensagemSucesso,
                'fundo' => $fundo,
                'detalhes' => [
                    'usuario' => $usuario ? $usuario->nome : 'Usuário ID ' . $validated['usuario_id'],
                    'tipo' => $validated['tipo'],
                    'valor' => 'R$ ' . number_format(abs($valorFundo), 2, ',', '.'),
                    'saldo_anterior' => 'R$ ' . number_format($saldoAnterior, 2, ',', '.'),
                    'saldo_atual' => 'R$ ' . number_format($conta->saldo_permuta, 2, ',', '.'),
                    'descricao' => $validated['descricao'] ?? ($validated['tipo'] === 'credito' ? 'Crédito adicionado' : 'Débito realizado')
                ]
            ], 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $fundo = FundoPermuta::with('usuario')->find($id);

        if (!$fundo) {
            return response()->json([
                'success' => false,
                'message' => 'Movimentação de fundo de permuta não encontrada',
                'erro' => 'Não existe movimentação com o ID ' . $id
            ], 404);
        }

        // Determinar tipo baseado no valor
        $tipo = $fundo->valor > 0 ? 'Crédito' : 'Débito';

        return response()->json([
            'success' => true,
            'message' => 'Movimentação de fundo de permuta encontrada',
            'movimentacao' => $fundo,
            'detalhes' => [
                'id' => $fundo->id_fundo_permuta,
                'valor' => 'R$ ' . number_format(abs($fundo->valor), 2, ',', '.'),
                'tipo' => $tipo,
                'usuario' => $fundo->usuario ? $fundo->usuario->nome : 'N/A',
                'data' => Carbon::parse($fundo->created_at)->format('d/m/Y H:i:s')
            ]
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $fundo = FundoPermuta::find($id);

        if (!$fundo) {
            return response()->json([
                'success' => false,
                'message' => 'Movimentação de fundo de permuta não encontrada',
                'erro' => 'Não existe movimentação com o ID ' . $id
            ], 404);
        }

        // Validar os dados de entrada
        $validated = $request->validate([
            'descricao' => 'nullable|string|max:255',
        ]);

        // Não permitimos alterar valor ou usuario_id, apenas a descrição
        if ($request->has('valor') || $request->has('usuario_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Não é permitido alterar o valor ou o usuário da movimentação',
                'campos_permitidos' => ['descricao']
            ], 400);
        }

        // Armazenar descrição anterior
        $descricaoAnterior = $fundo->descricao;

        // Atualizar apenas a descrição
        $fundo->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Descrição da movimentação atualizada com sucesso',
            'movimentacao' => $fundo,
            'alteracoes' => [
                'descricao_anterior' => $descricaoAnterior,
                'descricao_atual' => $fundo->descricao
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $fundo = FundoPermuta::find($id);

        if (!$fundo) {
            return response()->json([
                'success' => false,
                'message' => 'Movimentação de fundo de permuta não encontrada',
                'erro' => 'Não existe movimentação com o ID ' . $id
            ], 404);
        }

        return DB::transaction(function () use ($fundo) {
            // Buscar conta do usuário
            $conta = Conta::where('usuario_id', $fundo->usuario_id)->first();

            if (!$conta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conta do usuário não encontrada',
                    'erro' => 'Não foi possível encontrar a conta associada a esta movimentação'
                ], 400);
            }

            // Armazenar informações da movimentação para resposta
            $movimentacaoInfo = [
                'id' => $fundo->id_fundo_permuta,
                'tipo' => $fundo->valor > 0 ? 'Crédito' : 'Débito',
                'valor' => abs($fundo->valor),
                'usuario_id' => $fundo->usuario_id,
                'usuario_nome' => $fundo->usuario ? $fundo->usuario->nome : 'N/A',
                'data_criacao' => $fundo->created_at
            ];

            // Armazenar saldo anterior
            $saldoAnterior = $conta->saldo_permuta;

            // Reverter a operação no saldo
            $conta->saldo_permuta -= $fundo->valor; // Se era positivo, subtrai; se era negativo, adiciona
            $conta->save();

            $fundo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro de fundo de permuta removido com sucesso',
                'movimentacao_removida' => $movimentacaoInfo,
                'saldo' => [
                    'anterior' => 'R$ ' . number_format($saldoAnterior, 2, ',', '.'),
                    'atual' => 'R$ ' . number_format($conta->saldo_permuta, 2, ',', '.'),
                    'diferenca' => 'R$ ' . number_format(abs($fundo->valor), 2, ',', '.')
                ]
            ]);
        });
    }

    /**
     * Obter saldo total de fundo de permuta de um usuário
     */
    public function saldoUsuario(int $usuarioId)
    {
        $usuario = Usuario::find($usuarioId);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado',
                'erro' => 'Não existe usuário com o ID ' . $usuarioId
            ], 404);
        }

        $saldoTotal = FundoPermuta::where('usuario_id', $usuarioId)
            ->sum('valor');

        $totalCreditos = FundoPermuta::where('usuario_id', $usuarioId)
            ->where('valor', '>', 0)
            ->sum('valor');

        $totalDebitos = FundoPermuta::where('usuario_id', $usuarioId)
            ->where('valor', '<', 0)
            ->sum('valor');

        $movimentacoes = FundoPermuta::where('usuario_id', $usuarioId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Formatar movimentações para a resposta
        $movimentacoesFormatadas = $movimentacoes->map(function ($mov) {
            return [
                'id' => $mov->id_fundo_permuta,
                'valor' => 'R$ ' . number_format(abs($mov->valor), 2, ',', '.'),
                'tipo' => $mov->valor > 0 ? 'Crédito' : 'Débito',
                'data' => Carbon::parse($mov->created_at)->format('d/m/Y H:i:s')
            ];
        });

        // Obter saldo da conta (deve ser igual ao saldo calculado)
        $conta = Conta::where('usuario_id', $usuarioId)->first();
        $saldoConta = $conta ? $conta->saldo_permuta : 0;

        return response()->json([
            'success' => true,
            'message' => 'Saldo de fundo de permuta do usuário obtido com sucesso',
            'usuario' => [
                'id' => $usuario->id_usuario,
                'nome' => $usuario->nome
            ],
            'saldos' => [
                'total' => 'R$ ' . number_format($saldoTotal, 2, ',', '.'),
                'total_creditos' => 'R$ ' . number_format($totalCreditos, 2, ',', '.'),
                'total_debitos' => 'R$ ' . number_format(abs($totalDebitos), 2, ',', '.'),
                'valor_conta' => 'R$ ' . number_format($saldoConta, 2, ',', '.')
            ],
            'ultimas_movimentacoes' => $movimentacoesFormatadas
        ]);
    }

    /**
     * Transferir saldo entre usuários
     */
    public function transferir(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                'valor_transferencia' => 'required|numeric|min:0.01',
                'usuario_origem_id' => 'required|exists:usuarios,id_usuario',
                'usuario_destino_id' => 'required|exists:usuarios,id_usuario|different:usuario_origem_id',
                'descricao' => 'nullable|string|max:255',
            ]);

            // Buscar contas dos usuários
            $contaOrigem = Conta::where('usuario_id', $validated['usuario_origem_id'])->first();
            $contaDestino = Conta::where('usuario_id', $validated['usuario_destino_id'])->first();

            if (!$contaOrigem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário de origem não possui conta',
                    'usuario_origem_id' => $validated['usuario_origem_id']
                ], 400);
            }

            if (!$contaDestino) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário de destino não possui conta',
                    'usuario_destino_id' => $validated['usuario_destino_id']
                ], 400);
            }

            // Buscar informações dos usuários
            $usuarioOrigem = Usuario::find($validated['usuario_origem_id']);
            $usuarioDestino = Usuario::find($validated['usuario_destino_id']);

            // Verificar se há saldo suficiente
            if ($contaOrigem->saldo_permuta < $validated['valor_transferencia']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo de permuta insuficiente para transferência',
                    'saldo_disponivel' => 'R$ ' . number_format($contaOrigem->saldo_permuta, 2, ',', '.'),
                    'valor_solicitado' => 'R$ ' . number_format($validated['valor_transferencia'], 2, ',', '.'),
                    'diferenca' => 'R$ ' . number_format($validated['valor_transferencia'] - $contaOrigem->saldo_permuta, 2, ',', '.')
                ], 400);
            }

            // Armazenar saldos anteriores
            $saldoAnteriorOrigem = $contaOrigem->saldo_permuta;
            $saldoAnteriorDestino = $contaDestino->saldo_permuta;

            // Atualizar saldos
            $contaOrigem->saldo_permuta -= $validated['valor_transferencia'];
            $contaDestino->saldo_permuta += $validated['valor_transferencia'];

            $contaOrigem->save();
            $contaDestino->save();

            // Usar descrição fornecida ou criar padrão
            $descricao = $validated['descricao'] ?? 'Transferência de saldo';

            // Registro de débito
            $fundoDebito = FundoPermuta::create([
                'valor' => -$validated['valor_transferencia'],
                'usuario_id' => $validated['usuario_origem_id'],
                'descricao' => $descricao . ' para ' . ($usuarioDestino ? $usuarioDestino->nome : 'Usuário ID ' . $validated['usuario_destino_id'])
            ]);

            // Registro de crédito
            $fundoCredito = FundoPermuta::create([
                'valor' => $validated['valor_transferencia'],
                'usuario_id' => $validated['usuario_destino_id'],
                'descricao' => $descricao . ' de ' . ($usuarioOrigem ? $usuarioOrigem->nome : 'Usuário ID ' . $validated['usuario_origem_id'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transferência realizada com sucesso',
                'detalhes' => [
                    'valor_transferido' => 'R$ ' . number_format($validated['valor_transferencia'], 2, ',', '.'),
                    'origem' => [
                        'usuario' => $usuarioOrigem ? $usuarioOrigem->nome : 'Usuário ID ' . $validated['usuario_origem_id'],
                        'saldo_anterior' => 'R$ ' . number_format($saldoAnteriorOrigem, 2, ',', '.'),
                        'saldo_atual' => 'R$ ' . number_format($contaOrigem->saldo_permuta, 2, ',', '.')
                    ],
                    'destino' => [
                        'usuario' => $usuarioDestino ? $usuarioDestino->nome : 'Usuário ID ' . $validated['usuario_destino_id'],
                        'saldo_anterior' => 'R$ ' . number_format($saldoAnteriorDestino, 2, ',', '.'),
                        'saldo_atual' => 'R$ ' . number_format($contaDestino->saldo_permuta, 2, ',', '.')
                    ],
                    'descricao' => $descricao,
                    'data' => now()->format('d/m/Y H:i:s')
                ]
            ]);
        });
    }

    /**
     * Listar movimentações de um usuário
     */
    public function movimentacoes(int $usuarioId, Request $request)
    {
        $usuario = Usuario::find($usuarioId);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado',
                'erro' => 'Não existe usuário com o ID ' . $usuarioId
            ], 404);
        }

        $query = FundoPermuta::where('usuario_id', $usuarioId);

        // Filtros de período
        if ($request->has('data_inicio') && $request->has('data_fim')) {
            $query->whereBetween('created_at', [$request->data_inicio, $request->data_fim]);
        } elseif ($request->has('data_inicio')) {
            $query->where('created_at', '>=', $request->data_inicio);
        } elseif ($request->has('data_fim')) {
            $query->where('created_at', '<=', $request->data_fim);
        }

        // Filtros de tipo (crédito/débito)
        if ($request->has('tipo')) {
            if ($request->tipo === 'credito') {
                $query->where('valor', '>', 0);
            } elseif ($request->tipo === 'debito') {
                $query->where('valor', '<', 0);
            }
        }

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);
        $movimentacoes = $query->paginate($perPage);

        // Verificar se há movimentações
        if ($movimentacoes->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma movimentação encontrada para os filtros aplicados',
                'usuario' => [
                    'id' => $usuario->id_usuario,
                    'nome' => $usuario->nome
                ],
                'filtros_aplicados' => [
                    'tipo' => $request->tipo ?? 'todos',
                    'periodo' => $request->has('data_inicio') || $request->has('data_fim') ? 'filtrado' : 'todos'
                ]
            ]);
        }

        // Calcular totais
        $totalCreditos = FundoPermuta::where('usuario_id', $usuarioId)
            ->where('valor', '>', 0)
            ->when($request->has('data_inicio') && $request->has('data_fim'), function ($q) use ($request) {
                $q->whereBetween('created_at', [$request->data_inicio, $request->data_fim]);
            })
            ->when($request->has('data_inicio') && !$request->has('data_fim'), function ($q) use ($request) {
                $q->where('created_at', '>=', $request->data_inicio);
            })
            ->when(!$request->has('data_inicio') && $request->has('data_fim'), function ($q) use ($request) {
                $q->where('created_at', '<=', $request->data_fim);
            })
            ->sum('valor');

        $totalDebitos = FundoPermuta::where('usuario_id', $usuarioId)
            ->where('valor', '<', 0)
            ->when($request->has('data_inicio') && $request->has('data_fim'), function ($q) use ($request) {
                $q->whereBetween('created_at', [$request->data_inicio, $request->data_fim]);
            })
            ->when($request->has('data_inicio') && !$request->has('data_fim'), function ($q) use ($request) {
                $q->where('created_at', '>=', $request->data_inicio);
            })
            ->when(!$request->has('data_inicio') && $request->has('data_fim'), function ($q) use ($request) {
                $q->where('created_at', '<=', $request->data_fim);
            })
            ->sum('valor');

        // Formatar movimentações para a resposta
        $movimentacoesFormatadas = collect($movimentacoes->items())->map(function ($mov) {
            return [
                'id' => $mov->id_fundo_permuta,
                'valor' => 'R$ ' . number_format(abs($mov->valor), 2, ',', '.'),
                'tipo' => $mov->valor > 0 ? 'Crédito' : 'Débito',
                'data' => Carbon::parse($mov->created_at)->format('d/m/Y H:i:s')
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Movimentações encontradas com sucesso',
            'usuario' => [
                'id' => $usuario->id_usuario,
                'nome' => $usuario->nome
            ],
            'resumo' => [
                'totalCreditos' => 'R$ ' . number_format($totalCreditos, 2, ',', '.'),
                'totalDebitos' => 'R$ ' . number_format(abs($totalDebitos), 2, ',', '.'),
                'saldoLiquido' => 'R$ ' . number_format($totalCreditos + $totalDebitos, 2, ',', '.'),
                'totalRegistros' => $movimentacoes->total(),
                'paginaAtual' => $movimentacoes->currentPage(),
                'totalPaginas' => $movimentacoes->lastPage()
            ],
            'movimentacoes' => $movimentacoesFormatadas,
            'paginacao' => [
                'total' => $movimentacoes->total(),
                'per_page' => $movimentacoes->perPage(),
                'current_page' => $movimentacoes->currentPage(),
                'last_page' => $movimentacoes->lastPage(),
                'from' => $movimentacoes->firstItem(),
                'to' => $movimentacoes->lastItem()
            ]
        ]);
    }
}
