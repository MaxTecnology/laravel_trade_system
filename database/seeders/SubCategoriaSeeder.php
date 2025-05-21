<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubCategoria;
use App\Models\Categoria;

class SubCategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapeamento de sub_categorias por categoria
        $subcategoriasPorCategoria = [
            'Alimentação' => [
                'Restaurantes',
                'Padarias',
                'Mercados',
                'Confeitarias',
                'Fast Food',
            ],
            'Vestuário' => [
                'Masculino',
                'Feminino',
                'Infantil',
                'Calçados',
                'Acessórios',
            ],
            'Eletrônicos' => [
                'Celulares',
                'Computadores',
                'Áudio e Vídeo',
                'Acessórios',
                'Eletrodomésticos',
            ],
            'Serviços' => [
                'Consultorias',
                'Manutenção',
                'Reformas',
                'Educação',
                'Suporte Técnico',
            ],
            'Decoração' => [
                'Móveis',
                'Objetos Decorativos',
                'Iluminação',
                'Utensílios Domésticos',
                'Cama, Mesa e Banho',
            ],
            'Saúde e Beleza' => [
                'Salões',
                'Spas',
                'Clínicas',
                'Academias',
                'Produtos de Beleza',
            ],
            'Automotivo' => [
                'Oficinas',
                'Lava Rápido',
                'Peças e Acessórios',
                'Seguros',
                'Locação de Veículos',
            ],
            'Imóveis' => [
                'Apartamentos',
                'Casas',
                'Terrenos',
                'Comerciais',
                'Rurais',
            ],
            'Entretenimento' => [
                'Cinemas',
                'Teatros',
                'Shows',
                'Parques',
                'Jogos',
            ],
            'Viagens' => [
                'Hotéis',
                'Passagens',
                'Pacotes',
                'Passeios',
                'Aluguel de Veículos',
            ],
        ];

        // Buscar todas as categorias existentes
        $categorias = Categoria::all();

        // Criar sub_categorias para cada categoria
        foreach ($categorias as $categoria) {
            $nomeCategoria = $categoria->nome_categoria;

            if (isset($subcategoriasPorCategoria[$nomeCategoria])) {
                foreach ($subcategoriasPorCategoria[$nomeCategoria] as $nomeSubcategoria) {
                    SubCategoria::create([
                        'nome_sub_categoria' => $nomeSubcategoria,
                        'categoria_id' => $categoria->id_categoria,
                    ]);
                }
            }
        }
    }
}
