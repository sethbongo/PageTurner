<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'title' => $this->title,
            'author' => $this->author,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'published_at' => $this->published_at,
            
            // Only load description on the detail route, not in list views
            'description' => $this->when(
                $request->routeIs('books.show'),
                $this->description
            ),
            
            // Category loaded safely — no query if not eager-loaded
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
