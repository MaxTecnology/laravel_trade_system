<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parcelamento extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'parcelamentos';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_parcelamento';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'numero_parcela',
        'valor_parcela',
        'comissao_parcela',
        'transacao_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'numero_parcela' => 'integer',
        'valor_parcela' => 'float',
        'comissao_parcela' => 'float',
    ];

    /**
     * Get the transacao that owns the parcelamento.
     */
    public function transacao()
    {
        return $this->belongsTo(Transacao::class, 'transacao_id', 'id_transacao');
    }
}
