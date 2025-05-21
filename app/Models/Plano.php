<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'planos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_plano';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome_plano',
        'tipo_do_plano',
        'imagem',
        'taxa_inscricao',
        'taxa_comissao',
        'taxa_manutencao_anual',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'taxa_inscricao' => 'float',
        'taxa_comissao' => 'float',
        'taxa_manutencao_anual' => 'float',
    ];

    /**
     * Get the contas for the plano.
     */
    public function contas()
    {
        return $this->hasMany(Conta::class, 'plano_id', 'id_plano');
    }
}
