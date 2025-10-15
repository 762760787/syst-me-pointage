<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prenom',
        'nom',
        'poste',
        'qrcode_id',
    ];

    /**
     * Get the attendances for the employee.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
