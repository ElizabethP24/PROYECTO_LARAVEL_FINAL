<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    protected $primaryKey = 'id_patient';
    protected $fillable = ['name', 'document', 'email', 'eps'];

    // Relationships
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'id_patient');
    }
}
