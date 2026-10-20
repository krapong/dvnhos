<?php

return [
    'name' => env('HOSPITAL_NAME', 'โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา'),
    'name_en' => env('HOSPITAL_NAME_EN', 'Thepratanawetchanukun Hospital'),
    'phone' => env('HOSPITAL_PHONE', '053-XXX-XXX'),
    'emergency' => env('HOSPITAL_EMERGENCY', '1669'),
    'address' => env('HOSPITAL_ADDRESS', ''),
    'email' => env('HOSPITAL_EMAIL', 'info@dvhospital.go.th'),
    'facebook' => env('HOSPITAL_FACEBOOK', ''),
    'line' => env('HOSPITAL_LINE', ''),
    'lat' => env('HOSPITAL_LAT', '18.7883'),
    'lng' => env('HOSPITAL_LNG', '98.9853'),
    'beds' => 60,
    'level' => 'F2',
    'type' => 'โรงพยาบาลชุมชน',

    'roles' => [
        'super_admin' => 'Super Admin',
        'staff_pr' => 'Staff PR (ประชาสัมพันธ์)',
        'staff_procurement' => 'Staff Procurement (จัดซื้อจัดจ้าง)',
        'staff_ita' => 'Staff ITA & Ethics (คุณธรรมความโปร่งใส)',
        'staff_events' => 'Staff Events (กิจกรรม)',
    ],

    'services' => [
        'opd' => 'แผนกผู้ป่วยนอก (OPD)',
        'er' => 'แผนกอุบัติเหตุและฉุกเฉิน (ER) 24 ชม.',
        'ipd' => 'แผนกผู้ป่วยใน (IPD)',
        'dental' => 'กลุ่มงานทันตกรรม',
        'rehab' => 'กลุ่มงานเวชกรรมฟื้นฟู',
        'dialysis' => 'หน่วยบริการไตเทียม',
        'primary_care' => 'กลุ่มงานบริการด้านปฐมภูมิและองค์รวม',
        'ncd_clinic' => 'คลินิกโรคไม่ติดต่อเรื้อรัง (NCD)',
        'pharmacy' => 'กลุ่มงานเภสัชกรรม',
        'radiology' => 'กลุ่มงานรังสีวิทยา',
        'lab' => 'กลุ่มงานเทคนิคการแพทย์',
        'anc' => 'คลินิกฝากครรภ์ (ANC)',
        'vaccine' => 'คลินิกวัคซีน',
        'home_care' => 'งานเยี่ยมบ้าน (Home Care)',
    ],
];
