<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $table = 'personal_access_tokens';
    protected $primaryKey = 'id';

    // Defina o tipo de incremento conforme o banco de dados
    public $incrementing = true;
    protected $keyType = 'integer'; // Use 'integer' para bigint no PostgreSQL
}
