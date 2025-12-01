<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class Doctor extends Model
{
    /** @use HasFactory<\Database\Factories\DoctorFactory> */
    use HasFactory;

    protected $primaryKey = 'id_doctor';
    protected $fillable = [
        'name',
        'document',
        'email',
        'id_specialty',
        'user_id',
        'slug',
        'status'

    ];

    // Relationships
    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'id_specialty');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'id_doctor');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Generate slug automatically
    protected static function booted()
    {
        static::creating(function ($doctor) {
            if (empty($doctor->slug)) {
                $doctor->slug = Str::slug($doctor->name . '-' . uniqid());
            }
        });
    }

    // Use slug for route-model binding when appropriate
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
