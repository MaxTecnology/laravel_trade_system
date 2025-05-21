<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SolicitacaoCredito;
use App\Models\Conta;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitacaoCreditoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $query = SolicitacaoCredito::query();

        // Filtros com base no tipo de usuário
        if ($usuario->tipo === 'admin') {
            // Admin pode ver todas as solicitações
        } elseif ($usuario->tipo === 'matriz') {
            // Matriz pode ver solicitações onde é matriz ou criador
            $query->where(function ($q) use ($usuario) {
                $q->where('matriz_id', $usuario->id_usuario)
                    ->orWhere('usuario_criador_id', $usuario->id_usuario);
            });
        } else {
            // Outros usuários veem apenas suas próprias solicitações ou as que criaram
            $query->where(function ($q) use ($usuario) {
                $q->where('usuario_solicitante_id', $usuario->id_usuario)
                    ->orWhere('usuario_criador_id', $usuario->id_usuario);
            });
        }

        // Filtros adicionais
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('usuario_solicitante_id')) {
            $query->where('usuario_solicitante_id', $request->usuario_solicitante_id);
        }

        if ($request->has('matriz_id')) {
            $query->where('matriz_id', $request->matriz_id);
        }

        // Relacionamentos
        $query->with(['usuarioSolicitante', 'usuarioCriador', 'matriz']);

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma solicitação de crédito encontrada',
                'data' => $results,
                'filtros_aplicados' => [
                    'status' => $request->status ?? 'todos',
                    'usuario_solicitante_id' => $request->usuario_solicitante_id ?? 'todos',
                    'matriz_id' => $request->matriz_id ?? 'todos'
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitações de crédito encontradas',
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
            'valor_solicitado' => 'required|numeric|min:0',
            'usuario_solicitante_id' => 'required|exists:usuarios,id_usuario',
            'descricao_solicitante' => 'nullable|string',
            'matriz_id' => 'nullable|exists:usuarios,id_usuario',
        ]);

        $usuarioAtual = $request->user();

        // Definir valores padrão
        $validated['status'] = 'Pendente';
        $validated['usuario_criador_id'] = $usuarioAtual->id_usuario;

        // Verificar se usuário solicitante já possui solicitação pendente
        $solicitacaoPendente = SolicitacaoCredito::where('usuario_solicitante_id', $validated['usuario_solicitante_id'])
            ->where('status', 'Pendente')
            ->first();

        if ($solicitacaoPendente) {
            return response()->json([
                'success' => false,
                'message' => 'O usuário já possui uma solicitação de crédito pendente',
                'solicitacao_pendente' => [
                    'id' => $solicitacaoPendente->id_solicitacao_credito,
                    'valor_solicitado' => $solicitacaoPendente->valor_solicitado,
                    'data_solicitacao' => $solicitacaoPendente->created_at
                ]
            ], 400);
        }

        // Criar solicitação
        $solicitacao = SolicitacaoCredito::create($validated);

        // Obter informações do usuário solicitante e criador para resposta
        $usuarioSolicitante = Usuario::find($validated['usuario_solicitante_id']);
        $usuarioCriador = Usuario::find($usuarioAtual->id_usuario);

        return response()->json([
            'success' => true,
            'message' => 'Solicitação de crédito criada com sucesso',
            'solicitacao' => $solicitacao,
            'detalhes' => [
                'solicitante' => $usuarioSolicitante ? $usuarioSolicitante->nome : 'N/A',
                'criador' => $usuarioCriador ? $usuarioCriador->nome : 'N/A',
                'valor_formatado' => 'R$ ' . number_format($solicitacao->valor_solicitado, 2, ',', '.')
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $solicitacao = SolicitacaoCredito::with(['usuarioSolicitante', 'usuarioCriador', 'matriz'])
            ->find($id);

        if (!$solicitacao) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação de crédito não encontrada',
                'erro' => 'Não existe solicitação com o ID ' . $id,
                'sugestoes' => [
                    'Verifique se o ID está correto',
                    'Use o endpoint GET /api/solicitacoes-credito para listar solicitações disponíveis'
                ]
            ], 404);
        }

        // Verificar permissão
        $usuario = request()->user();
        $temPermissao =
            $usuario->tipo === 'admin' ||
            $usuario->tipo === 'matriz' && $solicitacao->matriz_id === $usuario->id_usuario ||
            $solicitacao->usuario_criador_id === $usuario->id_usuario ||
            $solicitacao->usuario_solicitante_id === $usuario->id_usuario;

        if (!$temPermissao) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para visualizar esta solicitação de crédito'
            ], 403);
        }

        // Formatar dados para resposta
        $statusTexto = [
            'Pendente' => 'Em análise',
            'Aprovado' => 'Crédito aprovado',
            'Rejeitado' => 'Crédito não aprovado',
            'Cancelada' => 'Solicitação cancelada'
        ][$solicitacao->status] ?? $solicitacao->status;

        return response()->json([
            'success' => true,
            'message' => 'Solicitação de crédito encontrada',
            'solicitacao' => $solicitacao,
            'resumo' => [
                'id' => $solicitacao->id_solicitacao_credito,
                'valor' => 'R$ ' . number_format($solicitacao->valor_solicitado, 2, ',', '.'),
                'status' => $solicitacao->status,
                'status_descricao' => $statusTexto,
                'solicitante' => $solicitacao->usuarioSolicitante ? $solicitacao->usuarioSolicitante->nome : 'N/A',
                'criador' => $solicitacao->usuarioCriador ? $solicitacao->usuarioCriador->nome : 'N/A',
                'matriz' => $solicitacao->matriz ? $solicitacao->matriz->nome : 'N/A',
                'matriz_aprovacao' => $solicitacao->matriz_aprovacao !== null ?
                    ($solicitacao->matriz_aprovacao ? 'Aprovado pela matriz' : 'Rejeitado pela matriz')
                    : 'Aguardando análise da matriz'
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $solicitacao = SolicitacaoCredito::find($id);

        if (!$solicitacao) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação de crédito não encontrada',
                'erro' => 'Não existe solicitação com o ID ' . $id
            ], 404);
        }

        $usuario = $request->user();

        // Verificar se solicitação está pendente
        if ($solicitacao->status !== 'Pendente') {
            return response()->json([
                'success' => false,
                'message' => 'Somente solicitações pendentes podem ser atualizadas',
                'status_atual' => $solicitacao->status
            ], 400);
        }

        // Verificar quem pode atualizar
        $podeAtualizar =
            $usuario->id_usuario === $solicitacao->usuario_criador_id ||
            $usuario->id_usuario === $solicitacao->usuario_solicitante_id ||
            $usuario->tipo === 'admin';

        if (!$podeAtualizar) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para atualizar esta solicitação',
                'usuario_atual' => [
                    'id' => $usuario->id_usuario,
                    'tipo' => $usuario->tipo
                ],
                'permissoes_necessarias' => [
                    'ser o criador da solicitação',
                    'ser o solicitante do crédito',
                    'ser um administrador'
                ]
            ], 403);
        }

        $validated = $request->validate([
            'valor_solicitado' => 'sometimes|numeric|min:0',
            'descricao_solicitante' => 'nullable|string',
            'comentario_agencia' => 'nullable|string',
        ]);

        $dadosAnteriores = [
            'valor_solicitado' => $solicitacao->valor_solicitado,
            'descricao_solicitante' => $solicitacao->descricao_solicitante,
            'comentario_agencia' => $solicitacao->comentario_agencia
        ];

        $solicitacao->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Solicitação de crédito atualizada com sucesso',
            'solicitacao' => $solicitacao,
            'alteracoes' => [
                'antes' => $dadosAnteriores,
                'depois' => [
                    'valor_solicitado' => $solicitacao->valor_solicitado,
                    'descricao_solicitante' => $solicitacao->descricao_solicitante,
                    'comentario_agencia' => $solicitacao->comentario_agencia
                ]
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $solicitacao = SolicitacaoCredito::find($id);

        if (!$solicitacao) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação de crédito não encontrada',
                'erro' => 'Não existe solicitação com o ID ' . $id
            ], 404);
        }

        // Verificar se solicitação está pendente
        if ($solicitacao->status !== 'Pendente') {
            return response()->json([
                'success' => false,
                'message' => 'Somente solicitações pendentes podem ser canceladas',
                'status_atual' => $solicitacao->status
            ], 400);
        }

        // Armazenar informações antes de cancelar
        $infoSolicitacao = [
            'id' => $solicitacao->id_solicitacao_credito,
            'valor_solicitado' => $solicitacao->valor_solicitado,
            'solicitante' => $solicitacao->usuarioSolicitante ? $solicitacao->usuarioSolicitante->nome : 'N/A',
            'status_anterior' => $solicitacao->status
        ];

        // Em vez de excluir, marcar como Cancelada
        $solicitacao->status = 'Cancelada';
        $solicitacao->save();

        return response()->json([
            'success' => true,
            'message' => 'Solicitação de crédito cancelada com sucesso',
            'solicitacao_cancelada' => $infoSolicitacao,
            'cancelada_em' => now()->toISOString()
        ]);
    }

    /**
     * Aprovar solicitação
     */
    public function aprovar(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $validated = $request->validate([
                'comentario_agencia' => 'nullable|string',
                'comentario_matriz' => 'nullable|string',
                'matriz_aprovacao' => 'nullable|boolean',
            ]);

            $solicitacao = SolicitacaoCredito::find($id);

            if (!$solicitacao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitação de crédito não encontrada',
                    'erro' => 'Não existe solicitação com o ID ' . $id
                ], 404);
            }

            if ($solicitacao->status !== 'Pendente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Somente solicitações pendentes podem ser aprovadas',
                    'status_atual' => $solicitacao->status
                ], 400);
            }

            // Verificar permissão
            $usuario = $request->user();
            $temPermissao = $usuario->tipo === 'admin' ||
                ($usuario->tipo === 'matriz' && $solicitacao->matriz_id === $usuario->id_usuario);

            if (!$temPermissao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para aprovar esta solicitação',
                    'usuario_atual' => [
                        'id' => $usuario->id_usuario,
                        'tipo' => $usuario->tipo
                    ],
                    'permissoes_necessarias' => [
                        'ser administrador',
                        'ser a matriz atribuída a esta solicitação'
                    ]
                ], 403);
            }

            // Atualizar solicitação
            $solicitacao->status = 'Aprovado';

            if (isset($validated['comentario_agencia'])) {
                $solicitacao->comentario_agencia = $validated['comentario_agencia'];
            }

            if (isset($validated['comentario_matriz'])) {
                $solicitacao->comentario_matriz = $validated['comentario_matriz'];
            }

            if (isset($validated['matriz_aprovacao'])) {
                $solicitacao->matriz_aprovacao = $validated['matriz_aprovacao'];
            }

            $solicitacao->save();

            // Atualizar saldo do usuário
            $conta = Conta::where('usuario_id', $solicitacao->usuario_solicitante_id)->first();

            if (!$conta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conta do usuário solicitante não encontrada',
                    'usuario_id' => $solicitacao->usuario_solicitante_id
                ], 404);
            }

            $valorAntigo = $conta->limite_credito;
            $novoLimite = $valorAntigo + $solicitacao->valor_solicitado;
            $novoLimiteDisponivel = $conta->limite_disponivel + $solicitacao->valor_solicitado;

            $conta->limite_credito = $novoLimite;
            $conta->limite_disponivel = $novoLimiteDisponivel;
            $conta->save();

            return response()->json([
                'success' => true,
                'message' => 'Solicitação de crédito aprovada com sucesso',
                'solicitacao' => $solicitacao,
                'atualizacao_conta' => [
                    'usuario_id' => $solicitacao->usuario_solicitante_id,
                    'limite_anterior' => 'R$ ' . number_format($valorAntigo, 2, ',', '.'),
                    'novo_limite' => 'R$ ' . number_format($novoLimite, 2, ',', '.'),
                    'incremento' => 'R$ ' . number_format($solicitacao->valor_solicitado, 2, ',', '.')
                ]
            ]);
        });
    }

    /**
     * Rejeitar solicitação
     */
    public function rejeitar(Request $request, int $id)
    {
        $validated = $request->validate([
            'motivo_rejeicao' => 'required|string',
            'comentario_agencia' => 'nullable|string',
            'comentario_matriz' => 'nullable|string',
            'matriz_aprovacao' => 'nullable|boolean',
        ]);

        $solicitacao = SolicitacaoCredito::find($id);

        if (!$solicitacao) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação de crédito não encontrada',
                'erro' => 'Não existe solicitação com o ID ' . $id
            ], 404);
        }

        if ($solicitacao->status !== 'Pendente') {
            return response()->json([
                'success' => false,
                'message' => 'Somente solicitações pendentes podem ser rejeitadas',
                'status_atual' => $solicitacao->status
            ], 400);
        }

        // Verificar permissão
        $usuario = $request->user();
        $temPermissao = $usuario->tipo === 'admin' ||
            ($usuario->tipo === 'matriz' && $solicitacao->matriz_id === $usuario->id_usuario);

        if (!$temPermissao) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para rejeitar esta solicitação',
                'usuario_atual' => [
                    'id' => $usuario->id_usuario,
                    'tipo' => $usuario->tipo
                ]
            ], 403);
        }

        // Atualizar solicitação
        $solicitacao->status = 'Rejeitado';
        $solicitacao->motivo_rejeicao = $validated['motivo_rejeicao'];

        if (isset($validated['comentario_agencia'])) {
            $solicitacao->comentario_agencia = $validated['comentario_agencia'];
        }

        if (isset($validated['comentario_matriz'])) {
            $solicitacao->comentario_matriz = $validated['comentario_matriz'];
        }

        if (isset($validated['matriz_aprovacao'])) {
            $solicitacao->matriz_aprovacao = $validated['matriz_aprovacao'];
        }

        $solicitacao->save();

        return response()->json([
            'success' => true,
            'message' => 'Solicitação de crédito rejeitada com sucesso',
            'solicitacao' => $solicitacao,
            'detalhes_rejeicao' => [
                'motivo' => $solicitacao->motivo_rejeicao,
                'comentario_agencia' => $solicitacao->comentario_agencia,
                'comentario_matriz' => $solicitacao->comentario_matriz,
                'rejeitada_em' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Solicitações de matriz
     */
    public function solicitacoesMatriz(Request $request)
    {
        $usuario = $request->user();

        // Verificar se o usuário é uma matriz
        if ($usuario->tipo !== 'matriz') {
            return response()->json([
                'success' => false,
                'message' => 'Somente matrizes podem acessar esta funcionalidade',
                'usuario_tipo' => $usuario->tipo
            ], 403);
        }

        $query = SolicitacaoCredito::where('matriz_id', $usuario->id_usuario);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Relacionamentos
        $query->with(['usuarioSolicitante', 'usuarioCriador']);

        // Ordenação
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginação
        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        if ($results->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma solicitação de crédito encontrada para esta matriz',
                'matriz_id' => $usuario->id_usuario,
                'filtros_aplicados' => [
                    'status' => $request->status ?? 'todos'
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitações de crédito da matriz encontradas',
            'data' => $results,
            'resumo' => [
                'matriz_id' => $usuario->id_usuario,
                'total' => $results->total(),
                'pendentes' => $results->where('status', 'Pendente')->count(),
                'aprovadas' => $results->where('status', 'Aprovado')->count(),
                'rejeitadas' => $results->where('status', 'Rejeitado')->count(),
                'canceladas' => $results->where('status', 'Cancelada')->count()
            ]
        ]);
    }

    /**
     * Resposta da matriz
     */
    public function respostaMatriz(Request $request, int $id)
    {
        $validated = $request->validate([
            'matriz_aprovacao' => 'required|boolean',
            'comentario_matriz' => 'nullable|string',
        ]);

        $usuario = $request->user();

        // Verificar se o usuário é uma matriz
        if ($usuario->tipo !== 'matriz') {
            return response()->json([
                'success' => false,
                'message' => 'Somente matrizes podem acessar esta funcionalidade',
                'usuario_tipo' => $usuario->tipo
            ], 403);
        }

        $solicitacao = SolicitacaoCredito::find($id);

        if (!$solicitacao) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação de crédito não encontrada',
                'erro' => 'Não existe solicitação com o ID ' . $id
            ], 404);
        }

        // Verificar se o usuário é a matriz da solicitação
        if ($solicitacao->matriz_id !== $usuario->id_usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Você não é a matriz desta solicitação',
                'matriz_atual' => [
                    'id' => $solicitacao->matriz_id,
                    'nome' => $solicitacao->matriz ? $solicitacao->matriz->nome : 'N/A'
                ],
                'usuario_atual' => [
                    'id' => $usuario->id_usuario,
                    'nome' => $usuario->nome
                ]
            ], 403);
        }

        // Verificar se solicitação está pendente
        if ($solicitacao->status !== 'Pendente') {
            return response()->json([
                'success' => false,
                'message' => 'Somente solicitações pendentes podem receber resposta da matriz',
                'status_atual' => $solicitacao->status
            ], 400);
        }

        // Atualizar solicitação
        $aprovacaoAnterior = $solicitacao->matriz_aprovacao;
        $solicitacao->matriz_aprovacao = $validated['matriz_aprovacao'];
        $solicitacao->comentario_matriz = $validated['comentario_matriz'] ?? $solicitacao->comentario_matriz;
        $solicitacao->save();

        // Avançar status se for rejeição da matriz
        if ($validated['matriz_aprovacao'] === false) {
            $solicitacao->status = 'Rejeitado';
            $solicitacao->motivo_rejeicao = 'Reprovado pela matriz';
            $solicitacao->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Resposta da matriz registrada com sucesso',
            'solicitacao' => $solicitacao,
            'resposta_matriz' => [
                'aprovado' => $solicitacao->matriz_aprovacao,
                'comentario' => $solicitacao->comentario_matriz,
                'registrada_em' => now()->toISOString(),
                'alterou_status' => $validated['matriz_aprovacao'] === false
            ]
        ]);
    }
}
