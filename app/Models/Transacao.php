<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transacao extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transacoes';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_transacao';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'created_at',
        'data_do_estorno',
        'nome_comprador',
        'nome_vendedor',
        'comprador_id',
        'vendedor_id',
        'saldo_utilizado',
        'valor_rt',
        'valor_adicional',
        'saldo_anterior_comprador',
        'saldo_apos_comprador',
        'saldo_anterior_vendedor',
        'saldoAposVendedor',
        'limiteCreditoAnteriorComprador',
        'limiteCreditoAposComprador',
        'numeroParcelas',
        'descricao',
        'notaAtendimento',
        'observacaoNota',
        'status',
        'emiteVoucher',
        'oferta_id',
        'sub_conta_comprador_id',
        'sub_conta_vendedor_id',
        'comissao',
        'comissaoParcelada',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'notaAtendimento' => 0,
        'valor_adicional' => 0,
        'emiteVoucher' => false
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'data_do_estorno' => 'datetime',
        'valor_rt' => 'float',
        'valor_adicional' => 'float',
        'saldo_anterior_comprador' => 'float',
        'saldo_apos_comprador' => 'float',
        'saldo_anterior_vendedor' => 'float',
        'saldoAposVendedor' => 'float',
        'limiteCreditoAnteriorComprador' => 'float',
        'limiteCreditoAposComprador' => 'float',
        'comissao' => 'float',
        'comissaoParcelada' => 'float',
        'emiteVoucher' => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the oferta that belongs to the transacao.
     */
    public function oferta()
    {
        return $this->belongsTo(Oferta::class, 'oferta_id', 'id_oferta');
    }

    /**
     * Get the comprador that belongs to the transacao.
     */
    public function comprador()
    {
        return $this->belongsTo(Usuario::class, 'comprador_id', 'id_usuario');
    }

    /**
     * Get the vendedor that belongs to the transacao.
     */
    public function vendedor()
    {
        return $this->belongsTo(Usuario::class, 'vendedor_id', 'id_usuario');
    }

    /**
     * Get the subcontaComprador that belongs to the transacao.
     */
    public function subContaComprador()
    {
        return $this->belongsTo(SubConta::class, 'sub_conta_comprador_id', 'id_sub_contas');
    }

    /**
     * Get the subcontaVendedor that belongs to the transacao.
     */
    public function subContaVendedor()
    {
        return $this->belongsTo(SubConta::class, 'sub_conta_vendedor_id', 'id_sub_contas');
    }

    /**
     * Get the parcelamentos for the transacao.
     */
    public function parcelamento()
    {
        return $this->hasMany(Parcelamento::class, 'transacao_id', 'id_transacao');
    }

    /**
     * Get the cobrancas for the transacao.
     */
    public function cobrancas()
    {
        return $this->hasMany(Cobranca::class, 'transacao_id', 'id_transacao');
    }

    /**
     * Get the vouchers for the transacao.
     */
    public function voucher()
    {
        return $this->hasMany(Voucher::class, 'transacao_id', 'id_transacao');
    }
}
