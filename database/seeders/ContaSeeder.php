<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conta;
use App\Models\Usuario;
use App\Models\TipoConta;
use App\Models\Plano;
use Carbon\Carbon;

class ContaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar usuários criados pelo seeder
        $admin = Usuario::where('email', 'admin@sistema.com')->first();
        $matriz = Usuario::where('email', 'matriz@sistema.com')->first();
        $empresarial = Usuario::where('email', 'empresa@exemplo.com')->first();
        $comum = Usuario::where('email', 'cliente@exemplo.com')->first();
        $gerente = Usuario::where('email', 'gerente@sistema.com')->first();

        // Buscar tipos de conta
        $tipoAdmin = TipoConta::where('tipo_da_conta', 'Administrador')->first();
        $tipoMatriz = TipoConta::where('tipo_da_conta', 'Matriz')->first();
        $tipoEmpresarial = TipoConta::where('tipo_da_conta', 'Empresarial')->first();
        $tipoComum = TipoConta::where('tipo_da_conta', 'Comum')->first();

        // Buscar planos
        $planoMatriz = Plano::where('tipo_do_plano', 'Matriz')->first();
        $planoEmpresarial = Plano::where('tipo_do_plano', 'Empresarial')->orderBy('taxa_inscricao', 'desc')->first(); // Premium
        $planoIndividual = Plano::where('tipo_do_plano', 'Individual')->orderBy('taxa_inscricao', 'asc')->first(); // Básico

        // Criar conta para admin
        if ($admin && $tipoAdmin) {
            Conta::create([
                'taxa_repasse_matriz' => 0,
                'limite_credito' => 1000000,
                'limite_utilizado' => 0,
                'limite_disponivel' => 1000000,
                'saldo_permuta' => 500000,
                'saldo_dinheiro' => 500000,
                'limite_venda_mensal' => 1000000,
                'limite_venda_total' => 10000000,
                'limite_venda_empresa' => 5000000,
                'valor_venda_mensal_atual' => 0,
                'valor_venda_total_atual' => 0,
                'dia_fechamento_fatura' => 1,
                'data_vencimento_fatura' => 10,
                'numero_conta' => 'ADM0001',
                'data_de_afiliacao' => Carbon::now(),
                'tipo_conta_id' => $tipoAdmin->id_tipo_conta,
                'usuario_id' => $admin->id_usuario,
                'plano_id' => $planoMatriz ? $planoMatriz->id_plano : null,
            ]);
        }

        // Criar conta para matriz
        if ($matriz && $tipoMatriz && $gerente) {
            Conta::create([
                'taxa_repasse_matriz' => 0,
                'limite_credito' => 500000,
                'limite_utilizado' => 0,
                'limite_disponivel' => 500000,
                'saldo_permuta' => 250000,
                'saldo_dinheiro' => 250000,
                'limite_venda_mensal' => 500000,
                'limite_venda_total' => 5000000,
                'limite_venda_empresa' => 2500000,
                'valor_venda_mensal_atual' => 0,
                'valor_venda_total_atual' => 0,
                'dia_fechamento_fatura' => 5,
                'data_vencimento_fatura' => 15,
                'numero_conta' => 'MTZ0001',
                'data_de_afiliacao' => Carbon::now(),
                'tipo_conta_id' => $tipoMatriz->id_tipo_conta,
                'usuario_id' => $matriz->id_usuario,
                'plano_id' => $planoMatriz ? $planoMatriz->id_plano : null,
                'gerente_conta_id' => $gerente->id_usuario,
            ]);
        }

        // Criar conta para empresarial
        if ($empresarial && $tipoEmpresarial && $gerente) {
            Conta::create([
                'taxa_repasse_matriz' => 5,
                'limite_credito' => 100000,
                'limite_utilizado' => 0,
                'limite_disponivel' => 100000,
                'saldo_permuta' => 50000,
                'saldo_dinheiro' => 50000,
                'limite_venda_mensal' => 100000,
                'limite_venda_total' => 1000000,
                'limite_venda_empresa' => 500000,
                'valor_venda_mensal_atual' => 0,
                'valor_venda_total_atual' => 0,
                'dia_fechamento_fatura' => 10,
                'data_vencimento_fatura' => 20,
                'numero_conta' => 'EMP0001',
                'data_de_afiliacao' => Carbon::now(),
                'nome_franquia' => 'Franquia Exemplo',
                'tipo_conta_id' => $tipoEmpresarial->id_tipo_conta,
                'usuario_id' => $empresarial->id_usuario,
                'plano_id' => $planoEmpresarial ? $planoEmpresarial->id_plano : null,
                'gerente_conta_id' => $gerente->id_usuario,
            ]);
        }

        // Criar conta para usuário comum
        if ($comum && $tipoComum && $gerente) {
            Conta::create([
                'taxa_repasse_matriz' => 10,
                'limite_credito' => 20000,
                'limite_utilizado' => 0,
                'limite_disponivel' => 20000,
                'saldo_permuta' => 10000,
                'saldo_dinheiro' => 5000,
                'limite_venda_mensal' => 5000,
                'limite_venda_total' => 50000,
                'limite_venda_empresa' => 0,
                'valor_venda_mensal_atual' => 0,
                'valor_venda_total_atual' => 0,
                'dia_fechamento_fatura' => 15,
                'data_vencimento_fatura' => 25,
                'numero_conta' => 'COM0001',
                'data_de_afiliacao' => Carbon::now(),
                'tipo_conta_id' => $tipoComum->id_tipo_conta,
                'usuario_id' => $comum->id_usuario,
                'plano_id' => $planoIndividual ? $planoIndividual->id_plano : null,
                'gerente_conta_id' => $gerente->id_usuario,
            ]);
        }

        // Criar conta para gerente
        if ($gerente && $tipoComum) {
            Conta::create([
                'taxa_repasse_matriz' => 5,
                'limite_credito' => 30000,
                'limite_utilizado' => 0,
                'limite_disponivel' => 30000,
                'saldo_permuta' => 15000,
                'saldo_dinheiro' => 15000,
                'limite_venda_mensal' => 10000,
                'limite_venda_total' => 100000,
                'limite_venda_empresa' => 0,
                'valor_venda_mensal_atual' => 0,
                'valor_venda_total_atual' => 0,
                'dia_fechamento_fatura' => 20,
                'data_vencimento_fatura' => 30,
                'numero_conta' => 'COM0002',
                'data_de_afiliacao' => Carbon::now(),
                'tipo_conta_id' => $tipoComum->id_tipo_conta,
                'usuario_id' => $gerente->id_usuario,
                'plano_id' => $planoIndividual ? $planoIndividual->id_plano : null,
            ]);
        }
    }
}
