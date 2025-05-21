<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cobranca extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cobrancas';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_cobranca';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'valor_fatura',
        'referencia',
        'created_at',
        'status',
        'transacao_id',
        'usuario_id',
        'conta_id',
        'vencimento_fatura',
        'sub_conta_id',
        'gerente_conta_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valor_fatura' => 'float',
        'created_at' => 'datetime',
        'vencimento_fatura' => 'datetime',
    ];

    /**
     * Get the transacao that owns the cobranca.
     */
    public function transacao()
    {
        return $this->belongsTo(Transacao::class, 'transacao_id', 'id_transacao');
    }

    /**
     * Get the usuario that owns the cobranca.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario');
    }

    /**
     * Get the conta that owns the cobranca.
     */
    public function conta()
    {
        return $this->belongsTo(Conta::class, 'conta_id', 'id_conta');
    }

    /**
     * Get the subconta that owns the cobranca.
     */
    public function subConta()
    {
        return $this->belongsTo(SubConta::class, 'sub_conta_id', 'id_sub_contas');
    }

    /**
     * Get the gerente that owns the cobranca.
     */
    public function gerente()
    {
        return $this->belongsTo(Usuario::class, 'gerente_conta_id', 'id_usuario');
    }
}
