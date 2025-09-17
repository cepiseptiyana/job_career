<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    // ini akan mencari factory di database
    use HasFactory;

    // belongsTo 
    // relations to parent
    // untuk melakukan belongTo Model Job harus miliki foreginKey ke references table tertentu
    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // hasMany 
    // relations to child
    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
