<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{

    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
