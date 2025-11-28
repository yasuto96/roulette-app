<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberCuisineState extends Model
{
    public $incrementing = false;       // 複合主キー
    protected $primaryKey = null;
    protected $table = 'member_cuisine_states';
    protected $fillable = ['member_id','cuisine_id','is_checked'];
}