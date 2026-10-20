<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SopDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'file_path',
        'original_filename',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
