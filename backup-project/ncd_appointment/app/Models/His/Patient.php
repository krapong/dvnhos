<?php

namespace App\Models\His;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $connection = 'hosxp';
    protected $table = 'patient';
    protected $primaryKey = 'hn';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    
    // Accessor for full name
    public function getFullNameAttribute()
    {
        return $this->pname . $this->fname . ' ' . $this->lname;
    }

    // Mask CID for Data Privacy (PDPA)
    public function getMaskedCidAttribute()
    {
        $cid = $this->cid;
        if (empty($cid) || strlen($cid) < 13) return $cid;
        
        // Show only first 4 and last 3 digits: e.g. 1101XXXXXX123
        return substr($cid, 0, 4) . 'XXXXXX' . substr($cid, 10, 3);
    }

    // Mask Phone Number
    public function getMaskedHometelAttribute()
    {
        $tel = $this->hometel;
        if (empty($tel) || strlen($tel) < 9) return $tel;
        
        // e.g. 081XXX5678
        return substr($tel, 0, 3) . 'XXX' . substr($tel, -4);
    }
}
