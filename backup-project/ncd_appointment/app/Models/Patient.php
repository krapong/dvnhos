<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['hn', 'cid', 'first_name', 'last_name', 'date_of_birth', 'gender', 'phone', 'address', 'blood_group'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
