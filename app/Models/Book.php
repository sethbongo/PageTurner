<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Laravel\Scout\Searchable;
use App\Traits\Shardable;

class Book extends Model
{
    use HasFactory, Auditable, Searchable, Shardable;

    protected $fillable = [
        'category_id',
        'title',
        'author',
        'isbn',
        'price',
        'stock_quantity',
        'description',
        'cover_image',
        'published_at',
        'publisher',
        'format',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'date',
    ];

    protected $appends = ['reviewCount', 'averageRating'];

    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function userReview()
    {
        return $this->hasOne(Review::class)->where('user_id', auth()->id());
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'description' => $this->description,
            // 'category' => $this->category?->name, // Commented out: The Scout 'database' driver searches columns directly.
            'format' => $this->format,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }
}
