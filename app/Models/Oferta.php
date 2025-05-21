<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oferta extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ofertas';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_oferta';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_franquia',
        'nome_franquia',
        'titulo',
        'tipo',
        'status',
        'descricao',
        'quantidade',
        'valor',
        'limite_compra',
        'vencimento',
        'cidade',
        'estado',
        'retirada',
        'obs',
        'imagens',
        'usuario_id',
        'nome_usuario',
        'categoria_id',
        'sub_categoria_id',
        'sub_conta_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'status' => 'boolean',
        'quantidade' => 'integer',
        'valor' => 'float',
        'limite_compra' => 'float',
        'vencimento' => 'datetime',
        'imagens' => 'array',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the usuario that owns the oferta.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario');
    }

    /**
     * Get the categoria that owns the oferta.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id', 'id_categoria');
    }

    /**
     * Get the sub_categoria that owns the oferta.
     */
    public function sub_categoria()
    {
        return $this->belongsTo(SubCategoria::class, 'sub_categoria_id', 'id_sub_categoria');
    }

    /**
     * Get the subconta that owns the oferta.
     */
    public function subconta()
    {
        return $this->belongsTo(SubConta::class, 'sub_conta_id', 'id_sub_contas');
    }

    /**
     * Get the transacoes for the oferta.
     */
    public function transacoes()
    {
        return $this->hasMany(Transacao::class, 'oferta_id', 'id_oferta');
    }

    /**
     * Get the imagens for the oferta.
     */
    public function imagensUp()
    {
        return $this->hasMany(Imagem::class, 'oferta_id', 'id_oferta');
    }
}
