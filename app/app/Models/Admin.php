<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; // 認証対象に
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name','email','password'];
    protected $hidden   = ['password','remember_token'];
    protected $casts    = ['email_verified_at' => 'datetime']; // 使うなら

    public function exports()
{
    return $this->hasMany(\App\Models\AdminExport::class);
}
}
