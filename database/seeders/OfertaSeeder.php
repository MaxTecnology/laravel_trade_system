<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Oferta;
use App\Models\Usuario;
use App\Models\Categoria;
use App\Models\SubCategoria;
use Carbon\Carbon;

class OfertaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar usuários
        $empresarial = Usuario::where('email', 'empresa@exemplo.com')->first();
        $comum = Usuario::where('email', 'cliente@exemplo.com')->first();

        // Buscar categorias e subcategorias
        $categoriaServicos = Categoria::where('nome_categoria', 'Serviços')->first();
        $categoriaVestuario = Categoria::where('nome_categoria', 'Vestuário')->first();
        $categoriaEletronicos = Categoria::where('nome_categoria', 'Eletrônicos')->first();

        $subcategoriaServicos = null;
        $subcategoriaVestuario = null;
        $subcategoriaEletronicos = null;

        if ($categoriaServicos) {
            $subcategoriaServicos = SubCategoria::where('categoria_id', $categoriaServicos->id_categoria)
                ->first();
        }

        if ($categoriaVestuario) {
            $subcategoriaVestuario = SubCategoria::where('categoria_id', $categoriaVestuario->id_categoria)
                ->first();
        }

        if ($categoriaEletronicos) {
            $subcategoriaEletronicos = SubCategoria::where('categoria_id', $categoriaEletronicos->id_categoria)
                ->first();
        }

        // Criar ofertas para usuário empresarial
        if ($empresarial && $categoriaServicos && $subcategoriaServicos) {
            // Oferta 1
            Oferta::create([
                'titulo' => 'Consultoria Empresarial',
                'tipo' => 'Serviço',
                'status' => true,
                'descricao' => 'Consultoria empresarial completa para pequenas e médias empresas.',
                'quantidade' => 10,
                'valor' => 2000.00,
                'limite_compra' => 1,
                'vencimento' => Carbon::now()->addMonths(3),
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'retirada' => 'Local',
                'obs' => 'Agendamento com antecedência mínima de 3 dias.',
                'imagens' => json_encode(['consultoria.jpg']),
                'usuario_id' => $empresarial->id_usuario,
                'nome_usuario' => $empresarial->nome,
                'categoria_id' => $categoriaServicos->id_categoria,
                'sub_categoria_id' => $subcategoriaServicos->id_sub_categoria,
                'created_at' => Carbon::now(),
            ]);

            // Oferta 2
            Oferta::create([
                'titulo' => 'Manutenção de Computadores',
                'tipo' => 'Serviço',
                'status' => true,
                'descricao' => 'Serviço de manutenção e limpeza de computadores e notebooks.',
                'quantidade' => 20,
                'valor' => 150.00,
                'limite_compra' => 3,
                'vencimento' => Carbon::now()->addMonths(2),
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'retirada' => 'Domicílio',
                'obs' => 'Atendimento em até 48 horas.',
                'imagens' => json_encode(['manutencao.jpg']),
                'usuario_id' => $empresarial->id_usuario,
                'nome_usuario' => $empresarial->nome,
                'categoria_id' => $categoriaServicos->id_categoria,
                'sub_categoria_id' => $subcategoriaServicos->id_sub_categoria,
                'created_at' => Carbon::now(),
            ]);
        }

        // Criar ofertas para usuário comum
        if ($comum && $categoriaEletronicos && $subcategoriaEletronicos) {
            // Oferta 3
            Oferta::create([
                'titulo' => 'Smartphone Seminovo',
                'tipo' => 'Produto',
                'status' => true,
                'descricao' => 'Smartphone em excelente estado, com todos os acessórios.',
                'quantidade' => 1,
                'valor' => 800.00,
                'limite_compra' => 1,
                'vencimento' => Carbon::now()->addMonths(1),
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'retirada' => 'Combinado',
                'obs' => 'Produto com garantia de 3 meses.',
                'imagens' => json_encode(['smartphone.jpg']),
                'usuario_id' => $comum->id_usuario,
                'nome_usuario' => $comum->nome,
                'categoria_id' => $categoriaEletronicos->id_categoria,
                'sub_categoria_id' => $subcategoriaEletronicos->id_sub_categoria,
                'created_at' => Carbon::now(),
            ]);
        }

        if ($comum && $categoriaVestuario && $subcategoriaVestuario) {
            // Oferta 4
            Oferta::create([
                'titulo' => 'Jaqueta de Couro',
                'tipo' => 'Produto',
                'status' => true,
                'descricao' => 'Jaqueta de couro legítimo, tamanho M, usada poucas vezes.',
                'quantidade' => 1,
                'valor' => 350.00,
                'limite_compra' => 1,
                'vencimento' => Carbon::now()->addMonths(1),
                'cidade' => 'São Paulo',
                'estado' => 'SP',
                'retirada' => 'Local',
                'obs' => 'Entrega em mãos, preferência por região central.',
                'imagens' => json_encode(['jaqueta.jpg']),
                'usuario_id' => $comum->id_usuario,
                'nome_usuario' => $comum->nome,
                'categoria_id' => $categoriaVestuario->id_categoria,
                'sub_categoria_id' => $subcategoriaVestuario->id_sub_categoria,
                'created_at' => Carbon::now(),
            ]);
        }
    }
}
