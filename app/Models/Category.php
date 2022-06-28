<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';
    protected $fillable = ["﻿id", 'category', 'parent_id'];
    public $incrementing = false;
    public $timestamps = false;
}
