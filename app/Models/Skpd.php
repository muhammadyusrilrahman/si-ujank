<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skpd extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'alias',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
