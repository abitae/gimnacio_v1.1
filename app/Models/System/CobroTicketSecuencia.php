<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CobroTicketSecuencia extends Model
{
    use HasFactory;

    protected $table = 'cobro_tickets_secuencias';

    protected $fillable = [
        'ultimo_numero',
    ];
}
