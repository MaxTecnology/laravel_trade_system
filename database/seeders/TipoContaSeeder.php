<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoConta;

class TipoContaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposContas = [
            [
                'tipo_da_conta' => 'Comum',
                'prefixo_conta' => 'COM',
                'descricao' => 'Conta comum para usuários finais',
                'permissoes' => json_encode([
                    'comprar',
                    'vender',
                    'criarOferta',
                    'solicitarCredito'
                ]),
            ],
            [
                'tipo_da_conta' => 'Empresarial',
                'prefixo_conta' => 'EMP',
                'descricao' => 'Conta para empresas e comerciantes',
                'permissoes' => json_encode([
                    'comprar',
                    'vender',
                    'criarOferta',
                    'solicitarCredito',
                    'criarSubconta',
                    'venderEmpresa'
                ]),
            ],
            [
                'tipo_da_conta' => 'Matriz',
                'prefixo_conta' => 'MTZ',
                'descricao' => 'Conta para matriz com permissões administrativas',
                'permissoes' => json_encode([
                    'comprar',
                    'vender',
                    'criarOferta',
                    'solicitarCredito',
                    'criarSubconta',
                    'venderEmpresa',
                    'aprovarCredito',
                    'gerenciarUsuarios',
                    'verRelatorios'
                ]),
            ],
            [
                'tipo_da_conta' => 'Administrador',
                'prefixo_conta' => 'ADM',
                'descricao' => 'Conta de administrador do sistema',
                'permissoes' => json_encode([
                    'comprar',
                    'vender',
                    'criarOferta',
                    'solicitarCredito',
                    'criarSubconta',
                    'venderEmpresa',
                    'aprovarCredito',
                    'gerenciarUsuarios',
                    'verRelatorios',
                    'gerenciarSistema',
                    'verLogs'
                ]),
            ],
        ];

        foreach ($tiposContas as $tipoConta) {
            TipoConta::create($tipoConta);
        }
    }
}
