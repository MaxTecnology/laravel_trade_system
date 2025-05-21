<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoConta extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tipo_contas';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_tipo_conta';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tipo_da_conta',
        'prefixo_conta',
        'descricao',
        'permissoes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissoes' => 'json',
    ];

    /**
     * Get the contas for the tipo conta.
     */
    public function contasAssociadas()
    {
        return $this->hasMany(Conta::class, 'tipo_conta_id', 'id_tipo_conta');
    }
}
