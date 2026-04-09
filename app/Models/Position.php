<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model {
    use HasFactory;
    protected $primaryKey = 'position_id';
    protected $guarded = [];
}
