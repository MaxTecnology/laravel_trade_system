<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conta;
use App\Models\Oferta;
use App\Models\Transacao;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Obter dados do dashboard
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $data = [];

        // Tipo de usuário para filtrar as informações corretas
        $tipoUsuario = $usuario->tipo;

        // Informações da conta
        $conta = Conta::where('usuario_id', $usuario->id_usuario)->first();

        if ($conta) {
            $data['conta'] = [
                'limite_credito' => $conta->limite_credito,
                'limite_disponivel' => $conta->limite_disponivel,
                'limite_utilizado' => $conta->limite_utilizado,
                'saldo_permuta' => $conta->saldo_permuta,
                'saldo_dinheiro' => $conta->saldo_dinheiro,
            ];
        }

        // Estatísticas de transações
        $transacoes = $this->obterEstatisticasTransacoes($usuario);
        $data['transacoes'] = $transacoes;

        // Estatísticas de ofertas
        $ofertas = $this->obterEstatisticasOfertas($usuario);
        $data['ofertas'] = $ofertas;

        // Estatísticas de usuários (para administradores ou gerentes)
        if ($tipoUsuario === 'admin' || $tipoUsuario === 'gerente') {
            $usuarios = $this->obterEstatisticasUsuarios($usuario);
            $data['usuarios'] = $usuarios;
        }

        // Gráfico de transações por período
        $periodoInicio = $request->input('periodo_inicio', Carbon::now()->subDays(30)->format('Y-m-d'));
        $periodoFim = $request->input('periodo_fim', Carbon::now()->format('Y-m-d'));

        $graficoTransacoes = $this->obterGraficoTransacoes($usuario, $periodoInicio, $periodoFim);
        $data['grafico_transacoes'] = $graficoTransacoes;

        return response()->json($data);
    }

    /**
     * Obter estatísticas de transações
     *
     * @param Usuario $usuario
     * @return array
     */
    private function obterEstatisticasTransacoes(Usuario $usuario)
    {
        // Total de transações como comprador
        $totalCompras = Transacao::where('comprador_id', $usuario->id_usuario)->count();

        // Total de transações como vendedor
        $totalVendas = Transacao::where('vendedor_id', $usuario->id_usuario)->count();

        // Valor total de compras
        $valorTotalCompras = Transacao::where('comprador_id', $usuario->id_usuario)
            ->sum('valor_rt');

        // Valor total de vendas
        $valorTotalVendas = Transacao::where('vendedor_id', $usuario->id_usuario)
            ->sum('valor_rt');

        // Compras recentes
        $comprasRecentes = Transacao::where('comprador_id', $usuario->id_usuario)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Vendas recentes
        $vendasRecentes = Transacao::where('vendedor_id', $usuario->id_usuario)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_compras' => $totalCompras,
            'total_vendas' => $totalVendas,
            'valor_total_compras' => $valorTotalCompras,
            'valor_total_vendas' => $valorTotalVendas,
            'compras_recentes' => $comprasRecentes,
            'vendas_recentes' => $vendasRecentes,
        ];
    }

    /**
     * Obter estatísticas de ofertas
     *
     * @param Usuario $usuario
     * @return array
     */
    private function obterEstatisticasOfertas(Usuario $usuario)
    {
        // Total de ofertas do usuário
        $totalOfertas = Oferta::where('usuario_id', $usuario->id_usuario)->count();

        // Ofertas ativas
        $ofertasAtivas = Oferta::where('usuario_id', $usuario->id_usuario)
            ->where('status', true)
            ->count();

        // Ofertas recentes
        $ofertasRecentes = Oferta::where('usuario_id', $usuario->id_usuario)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_ofertas' => $totalOfertas,
            'ofertas_ativas' => $ofertasAtivas,
            'ofertas_recentes' => $ofertasRecentes,
        ];
    }

    /**
     * Obter estatísticas de usuários (para admins e gerentes)
     *
     * @param Usuario $usuario
     * @return array
     */
    private function obterEstatisticasUsuarios(Usuario $usuario)
    {
        // Se for matriz, obter estatísticas de usuários filhos
        if ($usuario->tipo === 'matriz') {
            $totalUsuariosFilhos = Usuario::where('matriz_id', $usuario->id_usuario)->count();

            $usuariosRecentesFilhos = Usuario::where('matriz_id', $usuario->id_usuario)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return [
                'total_usuarios_filhos' => $totalUsuariosFilhos,
                'usuarios_recentes_filhos' => $usuariosRecentesFilhos,
            ];
        }

        // Se for gerente, obter estatísticas de contas gerenciadas
        if ($usuario->tipo === 'gerente') {
            $totalContasGerenciadas = Conta::where('gerente_conta_id', $usuario->id_usuario)->count();

            $contasGerenciadasRecentes = Conta::with('usuario')
                ->where('gerente_conta_id', $usuario->id_usuario)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return [
                'total_contas_gerenciadas' => $totalContasGerenciadas,
                'contas_gerenciadas_recentes' => $contasGerenciadasRecentes,
            ];
        }

        // Se for admin, obter estatísticas gerais
        if ($usuario->tipo === 'admin') {
            $totalUsuarios = Usuario::count();
            $totalUsuariosAtivos = Usuario::where('status', true)->count();
            $totalContasAtivas = Conta::count();

            $usuariosRecentes = Usuario::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return [
                'total_usuarios' => $totalUsuarios,
                'total_usuarios_ativos' => $totalUsuariosAtivos,
                'total_contas_ativas' => $totalContasAtivas,
                'usuarios_recentes' => $usuariosRecentes,
            ];
        }

        return []; // Retorna array vazio para outros tipos de usuário
    }

    /**
     * Obter dados para gráfico de transações
     *
     * @param Usuario $usuario
     * @param string $periodoInicio
     * @param string $periodoFim
     * @return array
     */
    private function obterGraficoTransacoes(Usuario $usuario, $periodoInicio, $periodoFim)
    {
        $dataInicio = Carbon::parse($periodoInicio);
        $dataFim = Carbon::parse($periodoFim);
        $diasDiferenca = $dataInicio->diffInDays($dataFim);

        // Determinar o agrupamento com base no período
        $agrupamento = 'day'; // padrão: dia a dia
        $formato = 'Y-m-d';

        if ($diasDiferenca > 60) {
            $agrupamento = 'month';
            $formato = 'Y-m';
        } elseif ($diasDiferenca > 14) {
            $agrupamento = 'week';
            $formato = 'Y-\WW'; // Formato de semana (ex: 2023-W35)
        }

        // Transações como comprador
        $compras = $this->obterTransacoesPorPeriodo(
            $usuario->id_usuario,
            'comprador_id',
            $periodoInicio,
            $periodoFim,
            $agrupamento,
            $formato
        );

        // Transações como vendedor
        $vendas = $this->obterTransacoesPorPeriodo(
            $usuario->id_usuario,
            'vendedor_id',
            $periodoInicio,
            $periodoFim,
            $agrupamento,
            $formato
        );

        return [
            'agrupamento' => $agrupamento,
            'compras' => $compras,
            'vendas' => $vendas,
        ];
    }

    /**
     * Obter transações agrupadas por período
     *
     * @param int $usuarioId
     * @param string $campoUsuario
     * @param string $periodoInicio
     * @param string $periodoFim
     * @param string $agrupamento
     * @param string $formato
     * @return array
     */
    private function obterTransacoesPorPeriodo($usuarioId, $campoUsuario, $periodoInicio, $periodoFim, $agrupamento, $formato)
    {
        // PostgreSQL usa TO_CHAR para formatação de data em vez de DATE_FORMAT
        $formatoSQL = '';

        switch ($formato) {
            case 'Y-m-d':
                $formatoSQL = 'YYYY-MM-DD';
                break;
            case 'Y-m':
                $formatoSQL = 'YYYY-MM';
                break;
            case 'Y-\WW':
                $formatoSQL = 'YYYY-"W"WW'; // Formato de semana para PostgreSQL
                break;
        }

        return Transacao::where($campoUsuario, $usuarioId)
            ->whereBetween('created_at', [$periodoInicio, $periodoFim])
            ->select(
                DB::raw("TO_CHAR(created_at, '{$formatoSQL}') as periodo"),
                DB::raw('count(*) as total_transacoes'),
                DB::raw('sum("valor_rt") as valor_total')
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->toArray();
    }
}
