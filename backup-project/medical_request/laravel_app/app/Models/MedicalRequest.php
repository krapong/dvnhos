<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id', 'user_id', 'department', 'start_date', 'end_date', 'target_date',
        'type', 'columns', 'status', 'file_url', 'details', 'purpose',
        'requester_name', 'contact_number', 'fiscal_year', 'delivery_channel'
    ];
}
