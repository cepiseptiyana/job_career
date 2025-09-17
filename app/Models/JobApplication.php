<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    // relations
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ambil data user berdasarkan kolom employer_id table jobApplications
    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }
}
