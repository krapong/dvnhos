<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'type', 'category_id', 'user_id',
        'excerpt', 'content', 'featured_image', 'attachments',
        'status', 'published_at', 'expired_at', 'is_pinned',
        'view_count', 'meta', 'sort_order', 'gallery',
    ];

    protected $casts = [
        'attachments' => 'array',
        'meta' => 'array',
        'published_at' => 'datetime',
        'expired_at' => 'datetime',
        'is_pinned' => 'boolean',
        'gallery' => 'array',
    ];

    // ===== Relations =====

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // ===== Scopes =====

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expired_at')
              ->orWhere('expired_at', '>', now());
        });
    }

    public function scopeNews(Builder $query): Builder
    {
        return $query->ofType('news');
    }

    public function scopeJobs(Builder $query): Builder
    {
        return $query->ofType('job');
    }

    public function scopeProcurement(Builder $query): Builder
    {
        return $query->ofType('procurement');
    }

    public function scopeActivities(Builder $query): Builder
    {
        return $query->ofType('activity');
    }

    // ===== Helpers =====

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'news' => 'ข่าวประชาสัมพันธ์',
            'job' => 'รับสมัครงาน',
            'procurement' => 'จัดซื้อจัดจ้าง',
            'activity' => 'กิจกรรม',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'แบบร่าง',
            'published' => 'เผยแพร่แล้ว',
            'archived' => 'เก็บถาวร',
            default => $this->status,
        };
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
