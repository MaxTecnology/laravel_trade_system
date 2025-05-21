<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitacaoCredito extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'solicitacao_creditos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_solicitacao_credito';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'valor_solicitado',
        'status',
        'motivo_rejeicao',
        'usuario_solicitante_id',
        'descricao_solicitante',
        'comentario_agencia',
        'matriz_aprovacao',
        'comentario_matriz',
        'usuario_criador_id',
        'matriz_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valor_solicitado' => 'float',
        'matriz_aprovacao' => 'boolean',
    ];

    /**
     * Get the usuarioSolicitante that owns the solicitacao.
     */
    public function usuarioSolicitante()
    {
        return $this->belongsTo(Usuario::class, 'usuario_solicitante_id', 'id_usuario');
    }

    /**
     * Get the usuarioCriador that owns the solicitacao.
     */
    public function usuarioCriador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_criador_id', 'id_usuario');
    }

    /**
     * Get the matriz that owns the solicitacao.
     */
    public function matriz()
    {
        return $this->belongsTo(Usuario::class, 'matriz_id', 'id_usuario');
    }
}
