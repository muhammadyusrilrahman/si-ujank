<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Skpd extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'alias',
        'npwp',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public static function cachedOptions(): Collection
    {
        return Cache::remember('skpd:options', 3600, function () {
            return self::query()
                ->orderBy('name')
                ->get(['id', 'name', 'alias', 'npwp']);
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('skpd:options');
    }
}
