<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

    protected $primaryKey = 'id_appointment';
    protected $fillable = [
        'id_patient',
        'id_doctor',
        'id_specialty',
        'date',
        'time',
        'status',
        'slug'
    ];

    // Relationships
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'id_patient');
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'id_specialty');
    }

    // Slug autogenerado
    protected static function booted()
    {
        static::creating(function ($appointment) {
            $appointment->slug = Str::slug(
                $appointment->date . '-' . $appointment->time . '-' . uniqid()
            );
        });
    }
}
