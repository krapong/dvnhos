<?php
function detailedLabQueries(string $where,bool $currentWard): array {
    $scope=$where.($currentWard?'':' AND EXISTS (SELECT 1 FROM ipt_labour ml WHERE ml.an=i.an)');
    return [
        'labs'=><<<'SQL'
SELECT m.an AS `AN มารดา`,m.hn AS `HN มารดา`,x.ipt_labour_lab_id AS `รหัสบันทึก`,
 x.lab_id AS `รหัส Lab`,ll.lab_name AS `ชื่อรายการ`,x.lab_value AS `ผล Lab 1`,x.lab_value2 AS `ผล Lab 2`,
 li.lab_items_unit AS `หน่วย`,ll.normal_value AS `ค่าอ้างอิง`,
 ll.lab_items_code AS `รหัสรายการห้องปฏิบัติการ`,ll.possible_value AS `ค่าที่เลือกได้`
FROM ipt_labour m JOIN ipt_labour_lab x ON x.ipt_labour_id=m.ipt_labour_id
LEFT JOIN labour_lab ll ON ll.lab_id=x.lab_id
LEFT JOIN lab_items li ON li.lab_items_code=ll.lab_items_code
WHERE EXISTS (SELECT 1 FROM ipt i WHERE i.an=m.an AND 
SQL
        .$where.') ORDER BY m.an,x.lab_id,x.ipt_labour_lab_id LIMIT 20001',
        'lab_results'=><<<'SQL'
SELECT i.an AS `AN มารดา`,i.hn AS `HN มารดา`,
 CASE WHEN h.vn=i.an THEN 'AN ผู้ป่วยใน' ELSE 'VN ตอน Admit' END AS `แหล่งใบสั่ง`,
 h.lab_order_number AS `เลขใบสั่ง Lab`,h.form_name AS `แบบฟอร์ม`,
 h.order_date AS `วันที่สั่ง`,h.order_time AS `เวลาสั่ง`,
 h.receive_date AS `วันที่รับสิ่งส่งตรวจ`,h.receive_time AS `เวลารับสิ่งส่งตรวจ`,
 h.report_date AS `วันที่รายงาน`,h.report_time AS `เวลารายงาน`,h.reporter_name AS `ผู้รายงาน`,
 o.lab_items_code AS `รหัส Lab`,COALESCE(NULLIF(o.lab_items_name_ref,''),li.lab_items_name) AS `ชื่อรายการ`,
 o.lab_order_result AS `ผลตรวจ`,li.lab_items_unit AS `หน่วย`,
 COALESCE(NULLIF(o.lab_items_normal_value_ref,''),li.lab_items_normal_value) AS `ค่าอ้างอิง`,
 CASE WHEN NULLIF(o.lab_items_normal_value_ref,'') IS NOT NULL THEN 'จากใบสั่งเดิม' ELSE 'จากรายการปัจจุบัน' END AS `แหล่งค่าอ้างอิง`,
 o.confirm AS `ยืนยันผล (รหัส)`,h.confirm_report AS `ยืนยันรายงาน (รหัส)`,
 o.lab_result_status AS `สถานะผล (รหัส)`,o.abnormal_result AS `ผิดปกติ (รหัส)`,o.critical_result AS `วิกฤต (รหัส)`,
 o.specimen_code AS `สิ่งส่งตรวจ (รหัส)`,o.lab_order_remark AS `หมายเหตุผล`,h.result_note AS `หมายเหตุใบสั่ง`
FROM ipt i
JOIN lab_head h ON h.hn=i.hn AND (h.vn=i.an OR (NULLIF(i.vn,'') IS NOT NULL AND h.vn=i.vn))
JOIN lab_order o ON o.lab_order_number=h.lab_order_number
LEFT JOIN lab_items li ON li.lab_items_code=o.lab_items_code
WHERE 
SQL
        .$scope.' ORDER BY i.an,h.order_date DESC,h.order_time DESC,h.lab_order_number DESC,o.lab_items_code LIMIT 20001'
    ];
}
