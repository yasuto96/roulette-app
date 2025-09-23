<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/RouletteHistory.php
class RouletteHistory extends Model
{
    protected $fillable = ['member_id','name','criteria'];
    protected $casts = ['criteria' => 'array'];

    public function member(){ return $this->belongsTo(Member::class); }
}

