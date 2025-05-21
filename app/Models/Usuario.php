<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_usuario';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'usuario_criador_id',
        'matriz_id',
        'nome',
        'cpf',
        'email',
        'senha',
        'imagem',
        'status_conta',
        'reputacao',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'insc_estadual',
        'insc_municipal',
        'mostrar_no_site',
        'descricao',
        'tipo',
        'tipo_de_moeda',
        'status',
        'restricao',
        'nome_contato',
        'telefone',
        'celular',
        'email_contato',
        'email_secundario',
        'site',
        'logradouro',
        'numero',
        'cep',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'regiao',
        'aceita_orcamento',
        'aceita_voucher',
        'tipo_operacao',
        'categoria_id',
        'sub_categoria_id',
        'taxa_comissao_gerente',
        'permissoes_do_usuario',
        'bloqueado',
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
        'mostrar_no_site' => 'boolean',
        'status' => 'boolean',
        'numero' => 'integer',
        'aceita_orcamento' => 'boolean',
        'aceita_voucher' => 'boolean',
        'tipo_operacao' => 'integer',
        'taxa_comissao_gerente' => 'integer',
        'permissoes_do_usuario' => 'json',
        'bloqueado' => 'boolean',
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
     * Relacionamentos
     */

    // Usuário Criador
    public function usuarioCriador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_criador_id', 'id_usuario');
    }

    // Usuários criados por este usuário
    public function usuariosCriados()
    {
        return $this->hasMany(Usuario::class, 'usuario_criador_id', 'id_usuario');
    }

    // Matriz
    public function matriz()
    {
        return $this->belongsTo(Usuario::class, 'matriz_id', 'id_usuario');
    }

    // Usuários filhos da matriz
    public function usuariosFilhosDaMatriz()
    {
        return $this->hasMany(Usuario::class, 'matriz_id', 'id_usuario');
    }

    // Conta do usuário
    public function conta()
    {
        return $this->hasOne(Conta::class, 'usuario_id', 'id_usuario');
    }

    // Contas gerenciadas pelo usuário (como gerente)
    public function contasGerenciadas()
    {
        return $this->hasMany(Conta::class, 'gerente_conta_id', 'id_usuario');
    }

    // Ofertas do usuário
    public function ofertas()
    {
        return $this->hasMany(Oferta::class, 'usuario_id', 'id_usuario');
    }

    // Transações onde o usuário é comprador
    public function transacoesComprador()
    {
        return $this->hasMany(Transacao::class, 'comprador_id', 'id_usuario');
    }

    // Transações onde o usuário é vendedor
    public function transacoesVendedor()
    {
        return $this->hasMany(Transacao::class, 'vendedor_id', 'id_usuario');
    }

    // Cobrancas
    public function cobrancas()
    {
        return $this->hasMany(Cobranca::class, 'usuario_id', 'id_usuario');
    }

    // Cobrancas gerenciadas
    public function cobrancasGerenciadas()
    {
        return $this->hasMany(Cobranca::class, 'gerente_conta_id', 'id_usuario');
    }

    // Categoria
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id', 'id_categoria');
    }

    // SubCategoria
    public function sub_categoria()
    {
        return $this->belongsTo(SubCategoria::class, 'sub_categoria_id', 'id_sub_categoria');
    }

    // Solicitações de crédito criadas
    public function solicitacoesDeCreditoCriadas()
    {
        return $this->hasMany(SolicitacaoCredito::class, 'usuario_criador_id', 'id_usuario');
    }

    // Solicitações de crédito solicitadas
    public function solicitacoesDeCreditoSolicitadas()
    {
        return $this->hasMany(SolicitacaoCredito::class, 'usuario_solicitante_id', 'id_usuario');
    }

    // Solicitações de crédito para a matriz
    public function solicitacoesDeCreditoMatriz()
    {
        return $this->hasMany(SolicitacaoCredito::class, 'matriz_id', 'id_usuario');
    }

    // Fundos de permuta
    public function fundoPermuta()
    {
        return $this->hasMany(FundoPermuta::class, 'usuario_id', 'id_usuario');
    }
}
