<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parcela extends Model
{
    use HasFactory;

    protected $fillable = [
        'cod_ccir',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 