<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Categoria;
use App\Models\SubCategoria;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar admin
        $admin = Usuario::create([
            'nome' => 'Administrador',
            'cpf' => '00000000000',
            'email' => 'admin@sistema.com',
            'senha' => Hash::make('admin123'),
            'tipo' => 'admin',
            'aceita_orcamento' => true,
            'aceita_voucher' => true,
            'tipo_operacao' => 1,
            'status_conta' => true,
            'status' => true,
            'permissoes_do_usuario' => json_encode(['*']), // Todas as permissões
        ]);

        // Criar matriz
        $matriz = Usuario::create([
            'nome' => 'Matriz Principal',
            'cpf' => '11111111111',
            'email' => 'matriz@sistema.com',
            'senha' => Hash::make('matriz123'),
            'razao_social' => 'Matriz Comercial LTDA',
            'nome_fantasia' => 'Matriz Comercial',
            'cnpj' => '12345678000100',
            'tipo' => 'matriz',
            'aceita_orcamento' => true,
            'aceita_voucher' => true,
            'tipo_operacao' => 2,
            'status_conta' => true,
            'status' => true,
            'permissoes_do_usuario' => json_encode([
                'comprar', 'vender', 'criarOferta', 'solicitarCredito',
                'aprovarCredito', 'gerenciarUsuarios', 'verRelatorios'
            ]),
        ]);

        // Criar usuário empresarial
        $categoriaComercio = Categoria::where('nome_categoria', 'Serviços')->first();
        $subcategoriaComercio = null;

        if ($categoriaComercio) {
            $subcategoriaComercio = SubCategoria::where('categoria_id', $categoriaComercio->id_categoria)
                ->first();
        }

        $empresarial = Usuario::create([
            'nome' => 'Empresa Exemplo',
            'cpf' => '22222222222',
            'email' => 'empresa@exemplo.com',
            'senha' => Hash::make('empresa123'),
            'razao_social' => 'Empresa Exemplo LTDA',
            'nome_fantasia' => 'Empresa Exemplo',
            'cnpj' => '98765432000199',
            'tipo' => 'empresarial',
            'aceita_orcamento' => true,
            'aceita_voucher' => true,
            'tipo_operacao' => 3,
            'status_conta' => true,
            'status' => true,
            'matriz_id' => $matriz->id_usuario,
            'categoria_id' => $categoriaComercio ? $categoriaComercio->id_categoria : null,
            'sub_categoria_id' => $subcategoriaComercio ? $subcategoriaComercio->id_sub_categoria : null,
            'permissoes_do_usuario' => json_encode([
                'comprar', 'vender', 'criarOferta', 'solicitarCredito', 'criarSubconta'
            ]),
        ]);

        // Criar usuário comum
        $categoriaCliente = Categoria::where('nome_categoria', 'Vestuário')->first();
        $subcategoriaCliente = null;

        if ($categoriaCliente) {
            $subcategoriaCliente = SubCategoria::where('categoria_id', $categoriaCliente->id_categoria)
                ->first();
        }

        $comum = Usuario::create([
            'nome' => 'Cliente Comum',
            'cpf' => '33333333333',
            'email' => 'cliente@exemplo.com',
            'senha' => Hash::make('cliente123'),
            'tipo' => 'comum',
            'aceita_orcamento' => true,
            'aceita_voucher' => true,
            'tipo_operacao' => 4,
            'status_conta' => true,
            'status' => true,
            'matriz_id' => $matriz->id_usuario,
            'categoria_id' => $categoriaCliente ? $categoriaCliente->id_categoria : null,
            'sub_categoria_id' => $subcategoriaCliente ? $subcategoriaCliente->id_sub_categoria : null,
            'permissoes_do_usuario' => json_encode([
                'comprar', 'vender', 'criarOferta', 'solicitarCredito'
            ]),
        ]);

        // Criar gerente
        $gerente = Usuario::create([
            'nome' => 'Gerente',
            'cpf' => '44444444444',
            'email' => 'gerente@sistema.com',
            'senha' => Hash::make('gerente123'),
            'tipo' => 'gerente',
            'aceita_orcamento' => true,
            'aceita_voucher' => true,
            'tipo_operacao' => 2,
            'status_conta' => true,
            'status' => true,
            'matriz_id' => $matriz->id_usuario,
            'taxa_comissao_gerente' => 10,
            'permissoes_do_usuario' => json_encode([
                'comprar', 'vender', 'criarOferta', 'gerenciarUsuarios', 'verRelatorios'
            ]),
        ]);
    }
}
