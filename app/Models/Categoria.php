<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categorias';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_categoria';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome_categoria',
        'tipo_categoria',
    ];

    /**
     * Get the subcategorias for the categoria.
     */
    public function sub_categorias()
    {
        return $this->hasMany(SubCategoria::class, 'categoria_id', 'id_categoria');
    }

    /**
     * Get the ofertas for the categoria.
     */
    public function ofertas()
    {
        return $this->hasMany(Oferta::class, 'categoria_id', 'id_categoria');
    }

    /**
     * Get the usuarios for the categoria.
     */
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'categoria_id', 'id_categoria');
    }
}
