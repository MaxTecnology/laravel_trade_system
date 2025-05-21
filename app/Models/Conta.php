<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conta extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contas';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_conta';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'taxa_repasse_matriz',
        'limite_credito',
        'limite_utilizado',
        'limite_disponivel',
        'saldo_permuta',
        'saldo_dinheiro',
        'limite_venda_mensal',
        'limite_venda_total',
        'limite_venda_empresa',
        'valor_venda_mensal_atual',
        'valor_venda_total_atual',
        'dia_fechamento_fatura',
        'data_vencimento_fatura',
        'numero_conta',
        'data_de_afiliacao',
        'nome_franquia',
        'tipo_conta_id',
        'usuario_id',
        'plano_id',
        'gerente_conta_id',
        'permissoes_especificas',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'taxa_repasse_matriz' => 'integer',
        'limite_credito' => 'float',
        'limite_utilizado' => 'float',
        'limite_disponivel' => 'float',
        'saldo_permuta' => 'float',
        'saldo_dinheiro' => 'float',
        'limite_venda_mensal' => 'float',
        'limite_venda_total' => 'float',
        'limite_venda_empresa' => 'float',
        'valor_venda_mensal_atual' => 'float',
        'valor_venda_total_atual' => 'float',
        'dia_fechamento_fatura' => 'integer',
        'data_vencimento_fatura' => 'integer',
        'data_de_afiliacao' => 'datetime',
        'permissoes_especificas' => 'json',
    ];

    // Relacionamentos

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario');
    }

    public function tipo_da_conta()
    {
        return $this->belongsTo(TipoConta::class, 'tipo_conta_id', 'id_tipo_conta');
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id', 'id_plano');
    }

    public function gerente_conta()
    {
        return $this->belongsTo(Usuario::class, 'gerente_conta_id', 'id_usuario');
    }

    public function sub_contas()
    {
        return $this->hasMany(SubConta::class, 'conta_pai_id', 'id_conta');
    }

    public function cobrancas()
    {
        return $this->hasMany(Cobranca::class, 'conta_id', 'id_conta');
    }
}
