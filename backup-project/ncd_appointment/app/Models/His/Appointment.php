<?php

namespace App\Models\His;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $connection = 'hosxp';
    protected $table = 'oapp';
    protected $primaryKey = 'oapp_id';
    public $timestamps = false;
    
    // Relationship to HIS Patient
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'hn', 'hn');
    }
    
    // Relationship to HIS Clinic (Renamed to avoid conflict with 'clinic' column)
    public function hisClinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic', 'clinic');
    }

    // Relationship to HIS Visit
    public function visit()
    {
        return $this->belongsTo(Ovst::class, 'visit_vn', 'vn');
    }

    // Relationship to Lab Orders
    public function labHeads()
    {
        return $this->hasMany(LabHead::class, 'hn', 'hn');
    }

    // Relationship to Xray Orders
    public function xrayHeads()
    {
        return $this->hasMany(XrayHead::class, 'hn', 'hn');
    }

    // Computed attribute for visit status
    public function getVisitStatusAttribute()
    {
        $today = date('Y-m-d');
        
        if (empty($this->visit_vn)) {
            return $this->nextdate < $today ? 'ผิดนัด' : 'รอรับบริการ';
        }
        
        if ($this->relationLoaded('visit') && $this->visit) {
            return $this->visit->vstdate === $this->nextdate ? 'มาตามนัด' : 'มาไม่ตรงนัด';
        }
        
        return 'มาแล้ว'; // Fallback if relation not loaded but visit_vn exists
    }

    // Computed attribute for visit status color
    public function getVisitStatusColorAttribute()
    {
        $status = $this->visit_status;
        
        return match($status) {
            'มาตามนัด' => 'green',
            'ผิดนัด' => 'red',
            'มาไม่ตรงนัด' => 'orange',
            'รอรับบริการ' => 'gray',
            default => 'blue',
        };
    }
    
    protected $appends = ['visit_status', 'visit_status_color'];
}
