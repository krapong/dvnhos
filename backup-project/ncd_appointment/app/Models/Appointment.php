<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Appointment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'oapp_id', 
        'hn', 
        'vn', 
        'clinic_code', 
        'vstdate',
        'patient_id', 
        'clinic_id', 
        'appointment_date', 
        'appointment_time', 
        'doctor_id', 
        'status', 
        'lab_required', 
        'reason', 
        'note', 
        'created_by'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // Relationship to HIS Patient for synced names
    public function hisPatient()
    {
        return $this->belongsTo(\App\Models\His\Patient::class, 'hn', 'hn');
    }

    // Relationship to HIS Ovst
    public function ovst()
    {
        return $this->belongsTo(\App\Models\His\Ovst::class, 'vn', 'vn');
    }

    // Relationship to Lab Orders in HOSxP
    public function labHeads()
    {
        return $this->hasMany(\App\Models\His\LabHead::class, 'hn', 'hn');
    }

    // Relationship to Xray Orders in HOSxP
    public function xrayHeads()
    {
        return $this->hasMany(\App\Models\His\XrayHead::class, 'hn', 'hn');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getVisitStatusAttribute()
    {
        $today = date('Y-m-d');
        
        if (empty($this->vn)) {
            return $this->appointment_date < $today ? 'ผิดนัด' : 'ยังไม่มา';
        }
        
        // HOSxP ovstost=99 is the completed status used by this dashboard.
        if ($this->ovst && $this->ovst->ovstost === '99') { 
            return 'ตรวจเสร็จแล้ว';
        }

        // Check if sent to this clinic today but arrived without an appointment or wrong date
        if ($this->vstdate && $this->appointment_date && $this->vstdate !== $this->appointment_date) {
            return 'มาไม่ตรงนัด';
        }

        // Check if sent to this clinic and visit type is "นัดมา" (ovstist = 02)
        // HOSxP main_dep uses the same code as our local clinic code.
        if ($this->ovst && $this->clinic && $this->ovst->main_dep === Clinic::hosxpMainDep($this->clinic->code) && $this->ovst->ovstist === '02') {
            return 'ส่งตรวจในวันนี้';
        }

        // If they have VN but don't match the clinic department
        if ($this->ovst && $this->clinic && $this->ovst->main_dep !== Clinic::hosxpMainDep($this->clinic->code)) {
            return 'ไม่ได้เข้าคลินิก';
        }
        
        // Fallback
        return 'ส่งตรวจในวันนี้'; 
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['appointment_date', 'status', 'clinic_code'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Computed attribute for visit status color
    public function getVisitStatusColorAttribute()
    {
        $status = $this->visit_status;
        
        return match($status) {
            'ส่งตรวจในวันนี้' => 'green',
            'ตรวจเสร็จแล้ว' => 'emerald',
            'ยังไม่มา' => 'orange',
            'มาไม่ตรงนัด' => 'purple',
            'ไม่ได้เข้าคลินิก' => 'rose',
            'ผู้รับบริการรายใหม่' => 'teal',
            default => 'blue',
        };
    }
    
    protected $appends = ['visit_status', 'visit_status_color'];
}
