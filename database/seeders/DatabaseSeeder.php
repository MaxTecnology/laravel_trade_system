<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TipoContaSeeder::class,
            PlanoSeeder::class,
            CategoriaSeeder::class,     // Primeiro categorias
            SubCategoriaSeeder::class,  // Depois subcategorias
            UsuarioSeeder::class,       // Depois usuários (que usam categorias e subcategorias)
            ContaSeeder::class,
            SubContaSeeder::class,
            OfertaSeeder::class,
        ]);
    }
}
