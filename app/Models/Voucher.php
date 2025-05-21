<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vouchers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id_voucher';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // ADICIONE ESTA LINHA

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'transacao_id',
        'status',
        'data_cancelamento',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data_cancelamento' => 'datetime',
    ];

    /**
     * Get the transacao that owns the voucher.
     */
    public function transacao()
    {
        return $this->belongsTo(Transacao::class, 'transacao_id', 'id_transacao');
    }
}
