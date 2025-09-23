<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // 認証対象にする
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable
{
    use HasFactory, Notifiable;

    // まとめて代入可能にする項目（安全のため許可リスト方式）
    protected $fillable = [
        'name', 'email', 'password',
    ];

    // JSONに出したくない項目
    protected $hidden = [
        'password', 'remember_token',
    ];

    // 型キャスト
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function favorites()
    {
        return $this->belongsToMany(Restaurant::class, 'favorites');
    }

    
    public function rouletteHistories()
    {
        return $this->hasMany(\App\Models\RouletteHistory::class);
    }

    
    public function searchFilters()
    {
        return $this->hasMany(\App\Models\SearchFilter::class);
    }


}
