<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'published_at' => $this->published_at,
            'source' => [
                'id' => $this->source->id,
                'name' => $this->source->name,
                'slug' => $this->source->slug,
            ],
            'category' => $this->when($this->category, [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'authors' => $this->when($this->authors, function () {
                return $this->authors->map(function ($author) {
                    return [
                        'id' => $author->id,
                        'name' => $author->name,
                    ];
                });
            }),
        ];
    }
}