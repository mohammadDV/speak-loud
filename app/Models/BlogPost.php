<?php

namespace App\Models;

use App\Support\LogsModelChanges;
use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use LogsModelChanges, SoftDeletes;

    protected $fillable = [
        'author_id', 'category_id', 'title', 'slug', 'excerpt',
        'body', 'cover_image_path', 'status', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tags', 'post_id', 'tag_id');
    }

    public function coverUrl(): string
    {
        return StorageUrl::resolve($this->cover_image_path)
            ?? asset('images/blog/default-cover.svg');
    }

    public function coverAlt(): string
    {
        return 'Cover image for '.$this->title;
    }
}
