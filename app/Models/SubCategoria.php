<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategoria extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sub_categorias';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_sub_categoria';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome_sub_categoria',
        'categoria_id',
    ];

    /**
     * Get the categoria that owns the sub_categoria.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id', 'id_categoria');
    }

    /**
     * Get the ofertas for the sub_categoria.
     */
    public function ofertas()
    {
        return $this->hasMany(Oferta::class, 'sub_categoria_id', 'id_sub_categoria');
    }

    /**
     * Get the usuarios for the sub_categoria.
     */
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'sub_categoria_id', 'id_sub_categoria');
    }
}
