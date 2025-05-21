<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plano;

class PlanoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planos = [
            [
                'nome_plano' => 'Básico',
                'tipo_do_plano' => 'Individual',
                'taxa_inscricao' => 100.00,
                'taxa_comissao' => 5.0,
                'taxa_manutencao_anual' => 50.00,
            ],
            [
                'nome_plano' => 'Premium',
                'tipo_do_plano' => 'Individual',
                'taxa_inscricao' => 200.00,
                'taxa_comissao' => 3.5,
                'taxa_manutencao_anual' => 80.00,
            ],
            [
                'nome_plano' => 'Empresarial Básico',
                'tipo_do_plano' => 'Empresarial',
                'taxa_inscricao' => 350.00,
                'taxa_comissao' => 4.0,
                'taxa_manutencao_anual' => 120.00,
            ],
            [
                'nome_plano' => 'Empresarial Premium',
                'tipo_do_plano' => 'Empresarial',
                'taxa_inscricao' => 500.00,
                'taxa_comissao' => 2.5,
                'taxa_manutencao_anual' => 180.00,
            ],
            [
                'nome_plano' => 'Matriz',
                'tipo_do_plano' => 'Matriz',
                'taxa_inscricao' => 1000.00,
                'taxa_comissao' => 1.5,
                'taxa_manutencao_anual' => 250.00,
            ],
        ];

        foreach ($planos as $plano) {
            Plano::create($plano);
        }
    }
}
