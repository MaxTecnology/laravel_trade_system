<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SubConta extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sub_contas';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_sub_contas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'email',
        'cpf',
        'numero_sub_conta',
        'senha',
        'imagem',
        'status_conta',
        'reputacao',
        'telefone',
        'celular',
        'email_contato',
        'logradouro',
        'numero',
        'cep',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'conta_pai_id',
        'permissoes',
        'token_reset_senha',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'senha',
        'token_reset_senha',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_conta' => 'boolean',
        'reputacao' => 'float',
        'numero' => 'integer',
        'permissoes' => 'json',
    ];

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->senha;
    }

    /**
     * Get the conta pai that owns the subconta.
     */
    public function contaPai()
    {
        return $this->belongsTo(Conta::class, 'conta_pai_id', 'id_conta');
    }

    /**
     * Get the ofertas for the subconta.
     */
    public function ofertas()
    {
        return $this->hasMany(Oferta::class, 'sub_conta_id', 'id_sub_contas');
    }

    /**
     * Get the transacoes as comprador for the subconta.
     */
    public function transacoesComprador()
    {
        return $this->hasMany(Transacao::class, 'sub_conta_comprador_id', 'id_sub_contas');
    }

    /**
     * Get the transacoes as vendedor for the subconta.
     */
    public function transacoesVendedor()
    {
        return $this->hasMany(Transacao::class, 'sub_conta_vendedor_id', 'id_sub_contas');
    }

    /**
     * Get the cobrancas for the subconta.
     */
    public function cobrancas()
    {
        return $this->hasMany(Cobranca::class, 'sub_conta_id', 'id_sub_contas');
    }
}
