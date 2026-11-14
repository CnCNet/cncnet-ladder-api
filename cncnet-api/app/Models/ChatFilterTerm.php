<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatFilterTerm extends Model
{
    protected $table = 'chat_filter_terms';

    protected $fillable = [
        'term',
        'whole_word',
    ];

    protected $casts = [
        'whole_word' => 'boolean',
    ];
}
