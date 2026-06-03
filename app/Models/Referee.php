<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Fillable([
    'name',
    'email',
    'phone',
    'license_level',
    'availability_status'
])]
class Referee extends Model
{
    // A referee can have many designations
    public function designations()
    {
        return $this->hasMany(Designation::class);
    }
}
