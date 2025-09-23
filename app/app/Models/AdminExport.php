<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminExport extends Model
{
    protected $fillable = ['admin_id','type','criteria','file_path','status'];
    protected $casts = ['criteria' => 'array'];

    public function admin(){ return $this->belongsTo(Admin::class); }
}