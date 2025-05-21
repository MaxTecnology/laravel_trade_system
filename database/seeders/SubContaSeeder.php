<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubConta;
use App\Models\Conta;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class SubContaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar conta empresarial
        $empresarial = Usuario::where('email', 'empresa@exemplo.com')->first();

        if ($empresarial) {
            $contaEmpresarial = Conta::where('usuario_id', $empresarial->id_usuario)->first();

            if ($contaEmpresarial) {
                // Criar subconta 1
                SubConta::create([
                    'nome' => 'Subconta Empresa 1',
                    'email' => 'subconta1@exemplo.com',
                    'cpf' => '55555555555',
                    'numero_sub_conta' => 'SUB0001',
                    'senha' => Hash::make('subconta123'),
                    'status_conta' => true,
                    'reputacao' => 4.5,
                    'telefone' => '1122334455',
                    'celular' => '11987654321',
                    'email_contato' => 'contato1@exemplo.com',
                    'logradouro' => 'Rua das Subcontas',
                    'numero' => 100,
                    'cep' => '01234567',
                    'bairro' => 'Centro',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                    'conta_pai_id' => $contaEmpresarial->id_conta,
                    'permissoes' => json_encode(['comprar', 'vender']),
                ]);

                // Criar subconta 2
                SubConta::create([
                    'nome' => 'Subconta Empresa 2',
                    'email' => 'subconta2@exemplo.com',
                    'cpf' => '66666666666',
                    'numero_sub_conta' => 'SUB0002',
                    'senha' => Hash::make('subconta123'),
                    'status_conta' => true,
                    'reputacao' => 4.2,
                    'telefone' => '1133445566',
                    'celular' => '11976543210',
                    'email_contato' => 'contato2@exemplo.com',
                    'logradouro' => 'Avenida das Subcontas',
                    'numero' => 200,
                    'cep' => '01234568',
                    'bairro' => 'Jardins',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                    'conta_pai_id' => $contaEmpresarial->id_conta,
                    'permissoes' => json_encode(['comprar']),
                ]);
            }
        }
    }
}
