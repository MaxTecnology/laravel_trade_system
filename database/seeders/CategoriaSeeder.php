<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nome_categoria' => 'Alimentação',
                'tipo_categoria' => 'Produto',
            ],
            [
                'nome_categoria' => 'Vestuário',
                'tipo_categoria' => 'Produto',
            ],
            [
                'nome_categoria' => 'Eletrônicos',
                'tipo_categoria' => 'Produto',
            ],
            [
                'nome_categoria' => 'Serviços',
                'tipo_categoria' => 'Serviço',
            ],
            [
                'nome_categoria' => 'Decoração',
                'tipo_categoria' => 'Produto',
            ],
            [
                'nome_categoria' => 'Saúde e Beleza',
                'tipo_categoria' => 'Serviço',
            ],
            [
                'nome_categoria' => 'Automotivo',
                'tipo_categoria' => 'Serviço',
            ],
            [
                'nome_categoria' => 'Imóveis',
                'tipo_categoria' => 'Produto',
            ],
            [
                'nome_categoria' => 'Entretenimento',
                'tipo_categoria' => 'Serviço',
            ],
            [
                'nome_categoria' => 'Viagens',
                'tipo_categoria' => 'Serviço',
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
