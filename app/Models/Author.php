<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
    ];

    public function articles(): BelongsToMany
    {
        // Authors can write multiple articles, articles can have multiple authors
        return $this->belongsToMany(Article::class);
    }
}