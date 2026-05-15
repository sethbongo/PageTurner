<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, Auditable;
    protected $fillable = [
        'name',
        'description'
    ];


    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
