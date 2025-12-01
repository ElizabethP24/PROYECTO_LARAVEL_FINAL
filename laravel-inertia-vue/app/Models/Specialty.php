<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    /** @use HasFactory<\Database\Factories\SpecialtyFactory> */
    use HasFactory;

    protected $primaryKey = 'id_specialty';
    protected $fillable = ['name'];

    // Relationships
    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'id_specialty');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'id_specialty');
    }
}
