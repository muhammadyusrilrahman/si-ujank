<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feedbacks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'message',
        'reply',
        'replied_by',
        'replied_at',
        'is_active',
    ];

    /**
     * The attribute type casts.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isReplied(): bool
    {
        return filled($this->reply);
    }
}
