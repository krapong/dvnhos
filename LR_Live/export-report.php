<?php
function exportXml(mixed $value): string {
    $text=is_array($value)?json_encode($value,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE):(string)($value??'');
    $text=preg_replace_callback('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',static fn($match)=>'\\x'.strtoupper(bin2hex($match[0])),$text);
    return htmlspecialchars($text??'',ENT_XML1|ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
function exportColumns(array $rows,array $defaults=[]): array {
    $keys=array_fill_keys($defaults,true);foreach($rows as $row)foreach($row as $key=>$value)if(!str_starts_with((string)$key,'_'))$keys[$key]=true;return array_keys($keys);
}
function exportSheets(array $data,array $stored,array $fields,array $period): array {
    $sheets=[['name'=>'ทะเบียนมารดาและทารก','headers'=>exportColumns($data['main'],array_column($fields,'label')),'rows'=>$data['main']]];
    foreach(['labs'=>'Lab ทะเบียนคลอด','lab_results'=>'ผลห้องปฏิบัติการ','vaccines'=>'วัคซีน','complications'=>'ภาวะแทรกซ้อน'] as $key=>$name){
        $rows=$data[$key]??[];$sheets[]=['name'=>$name,'headers'=>exportColumns($rows,['AN มารดา']),'rows'=>$rows];
    }
    $manual=[];$audit=[];$source=[];$links=[];
    foreach($data['main'] as $row){
        $id=$row['_record_id'];$identity=['รหัสระเบียน'=>$id,'AN มารดา'=>$row['AN มารดา'],'HN มารดา'=>$row['HN มารดา'],'ลำดับทารก'=>$row['ลำดับทารก']??''];
        foreach($stored[$id]['fields']??[] as $field=>$value)$manual[]=$identity+['ชื่อช่อง'=>$field,'ค่า'=>$value['value'],'ผู้บันทึก'=>$value['by'],'เวลาบันทึก'=>$value['at'],'ใช้ในรายงาน'=>isset($row['_manual'][$field])?'Y':'N'];
        foreach($stored[$id]['audit']??[] as $value)$audit[]=$identity+['ชื่อช่อง'=>$value['field'],'ก่อนแก้ไข'=>$value['before'],'หลังแก้ไข'=>$value['after'],'ผู้แก้ไข'=>$value['by'],'เวลาแก้ไข'=>$value['at']];
        foreach($row['_source']??[] as $field=>$value)$source[]=$identity+['ชื่อช่อง'=>$field,'ค่า HOSxP เดิม'=>$value,'ค่าใช้งาน'=>$row[$field]??null];
        foreach($row as $key=>$value)if(str_starts_with((string)$key,'_')&&!in_array($key,['_manual','_source'],true))$links[]=$identity+['ชื่อข้อมูล'=>$key,'ค่า'=>$value];
    }
    foreach([['ข้อมูลเสริม',$manual,['รหัสระเบียน','AN มารดา','HN มารดา','ลำดับทารก','ชื่อช่อง','ค่า','ผู้บันทึก','เวลาบันทึก','ใช้ในรายงาน']],['ประวัติข้อมูลเสริม',$audit,['รหัสระเบียน','AN มารดา','ชื่อช่อง','ก่อนแก้ไข','หลังแก้ไข','ผู้แก้ไข','เวลาแก้ไข']],['ค่า HOSxP ก่อนแก้ไข',$source,['รหัสระเบียน','AN มารดา','ชื่อช่อง','ค่า HOSxP เดิม','ค่าใช้งาน']],['ข้อมูลเชื่อมโยง',$links,['รหัสระเบียน','AN มารดา','ชื่อข้อมูล','ค่า']]] as [$name,$rows,$headers])$sheets[]=['name'=>$name,'headers'=>exportColumns($rows,$headers),'rows'=>$rows];
    $meta=[['รายการ'=>'ขอบเขต','ค่า'=>$period['all']?'ผู้รับบริการประเภทคลอดบุตรทั้งหมดทุกช่วงเวลา':'ผู้รับบริการประเภทคลอดบุตรตามวันที่ Admit'],['รายการ'=>'วันที่เริ่มต้น','ค่า'=>$period['all']?'ทุกช่วงเวลา':$period['from']],['รายการ'=>'วันที่สิ้นสุด','ค่า'=>$period['all']?'ทุกช่วงเวลา':$period['to']],['รายการ'=>'เวลาส่งออก (ไทย)','ค่า'=>(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s')],['รายการ'=>'จำนวนแถวทะเบียน','ค่า'=>count($data['main'])],['รายการ'=>'รูปแบบแถว','ค่า'=>'หนึ่งแถวต่อระเบียนมารดา/ทารก ลูกหลายคนแยกแถว'],['รายการ'=>'ช่องข้อมูล','ค่า'=>'ทุกช่องในโครงสร้างรายงาน LR EMR รวมช่องที่ซ่อนในหน้าจอ ข้อมูลเพิ่มเติม และรายการประกอบ ไม่ใช่ทุกตารางในฐาน HOSxP'],['รายการ'=>'รูปแบบค่า','ค่า'=>'เก็บเป็นข้อความเพื่อคงเลขศูนย์นำหน้าและป้องกันสูตร Excel ค่าว่างคือไม่มีข้อมูล ไม่สร้างค่าทดแทน']];
    $sheets[]=['name'=>'รายละเอียดไฟล์','headers'=>['รายการ','ค่า'],'rows'=>$meta];return $sheets;
}
function exportColumnName(int $number): string {$name='';do{$name=chr(65+$number%26).$name;$number=intdiv($number,26)-1;}while($number>=0);return $name;}
function exportWrite($handle,string $text): void {if(fwrite($handle,$text)!==strlen($text))throw new RuntimeException('Export write failed');}
function exportCsvCell(mixed $value): string {
    $text=is_array($value)?json_encode($value,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE):(string)($value??'');
    if(preg_match('/^[\s\x{FEFF}]*[=+@-]|^[\t\r\n]/u',$text))$text="'".$text;
    return $text;
}
function exportCsvWrite($out,array $sheet): void {
    exportWrite($out,"\xEF\xBB\xBF");
    $write=static function(array $values)use($out){
        if(fputcsv($out,array_map('exportCsvCell',$values),',','"','',"\r\n")===false)throw new RuntimeException('CSV write failed');
    };
    $write($sheet['headers']);
    foreach($sheet['rows'] as $row)$write(array_map(static fn($key)=>$row[$key]??null,$sheet['headers']));
}
// CSV has no worksheets: the complete export packages one CSV per workbook sheet.
function exportCsvFile(array $sheets,bool $bundle=false): string {
    $dir=__DIR__.'/.private/exports';
    if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Export directory unavailable');
    $archive=tempnam($dir,'csv-');$part=false;$out=null;$success=false;
    if($archive===false)throw new RuntimeException('Export storage unavailable');
    register_shutdown_function(static function()use($archive){if(is_file($archive))@unlink($archive);});
    try{
        chmod($archive,0600);$out=fopen($archive,'w+b');if(!$out)throw new RuntimeException('Export storage unavailable');
        if(!$bundle)exportCsvWrite($out,$sheets[0]);
        else{
            $part=tempnam($dir,'csv-part-');if($part===false)throw new RuntimeException('Export storage unavailable');
            register_shutdown_function(static function()use($part){if(is_file($part))@unlink($part);});
            chmod($part,0600);$central=[];
            foreach($sheets as $index=>$sheet){
                $stream=fopen($part,'wb');if(!$stream)throw new RuntimeException('Export storage unavailable');
                try{exportCsvWrite($stream,$sheet);}finally{fclose($stream);}
                clearstatcache(true,$part);
                exportZip($out,sprintf('%02d.csv',$index+1),$part,$central);
            }
            $offset=ftell($out);foreach($central as $entry)exportWrite($out,$entry);$size=ftell($out)-$offset;
            exportWrite($out,pack('VvvvvVVv',0x06054b50,0,0,count($central),count($central),$size,$offset,0));
        }
        $success=true;return $archive;
    }finally{
        if(is_resource($out))fclose($out);
        if($part!==false&&is_file($part))unlink($part);
        if(!$success&&is_file($archive))unlink($archive);
    }
}
// Store-only ZIP writer: produces a native XLSX without requiring ext-zip.
function exportZip($out,string $name,string $path,array &$central): void {
    $size=filesize($path);$offset=ftell($out);$crc=hexdec(hash_file('crc32b',$path));
    if($size+strlen($name)+30+$offset>0xffffffff)throw new LengthException('ไฟล์มีขนาดเกินขีดจำกัด Excel ที่ระบบรองรับ กรุณาเลือกช่วงวันที่');
    $date=(46<<9)|(1<<5)|1;
    exportWrite($out,pack('VvvvvvVVVvv',0x04034b50,20,0,0,0,$date,$crc,$size,$size,strlen($name),0).$name);
    $in=fopen($path,'rb');if(!$in)throw new RuntimeException('Export read failed');
    try{if(stream_copy_to_stream($in,$out)!==$size)throw new RuntimeException('Export copy failed');}finally{fclose($in);}
    $central[]=pack('VvvvvvvVVVvvvvvVV',0x02014b50,20,20,0,0,0,$date,$crc,$size,$size,strlen($name),0,0,0,0,0,$offset).$name;
}
function exportWorkbook(array $sheets): string {
    $dir=__DIR__.'/.private/exports';if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Export directory unavailable');
    $archive=tempnam($dir,'xlsx-');$out=null;
    if($archive===false)throw new RuntimeException('Export storage unavailable');
    $part=tempnam($dir,'xml-');
    if($part===false){unlink($archive);throw new RuntimeException('Export storage unavailable');}
    register_shutdown_function(static function()use($archive,$part){foreach([$archive,$part] as $file)if(is_file($file))@unlink($file);});
    chmod($archive,0600);chmod($part,0600);$success=false;
    try{
        $out=fopen($archive,'w+b');if(!$out)throw new RuntimeException('Export storage unavailable');$central=[];
        $parts=[
            '_rels/.rels'=>'<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/styles.xml'=>'<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF9D3376"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="49" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
        ];
        $types='<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $workbook='<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $types.='<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $rels='<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        foreach($sheets as $index=>$sheet){
            $id=$index+1;$name='xl/worksheets/sheet'.$id.'.xml';
            if(count($sheet['rows'])>1048575||count($sheet['headers'])>16384)throw new LengthException('ข้อมูลเกินจำนวนแถวหรือคอลัมน์ที่ Excel รองรับ กรุณาเลือกช่วงวันที่');
            $types.='<Override PartName="/'.$name.'" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbook.='<sheet name="'.exportXml($sheet['name']).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
            $rels.='<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$id.'.xml"/>';
            $xml=fopen($part,'wb');if(!$xml)throw new RuntimeException('Export storage unavailable');
            try{
                exportWrite($xml,'<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="'.max(1,count($sheet['headers'])).'" width="24" customWidth="1"/></cols><sheetData>');
                $writeRow=static function(array $values,int $rowNumber)use($xml){
                    exportWrite($xml,'<row r="'.$rowNumber.'"'.($rowNumber===1?' ht="42" customHeight="1"':'').'>');
                    foreach($values as $column=>$value){
                        $text=is_array($value)?json_encode($value,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE):(string)($value??'');
                        // Excel limits each cell to 32,767 UTF-16 code units. Fail visibly rather than truncate.
                        if(strlen(iconv('UTF-8','UTF-16LE',$text))>65534)throw new LengthException('ข้อมูลในช่องยาวเกินขีดจำกัด Excel กรุณาติดต่อผู้ดูแล');
                        exportWrite($xml,'<c r="'.exportColumnName($column).$rowNumber.'" s="'.($rowNumber===1?'1':'0').'" t="inlineStr"><is><t xml:space="preserve">'.exportXml($text).'</t></is></c>');
                    }exportWrite($xml,'</row>');
                };
                $writeRow($sheet['headers'],1);$rowNumber=2;
                foreach($sheet['rows'] as $row)$writeRow(array_map(static fn($key)=>$row[$key]??null,$sheet['headers']),$rowNumber++);
                exportWrite($xml,'</sheetData><autoFilter ref="A1:'.exportColumnName(max(0,count($sheet['headers'])-1)).max(1,$rowNumber-1).'"/></worksheet>');
            }finally{fclose($xml);}
            clearstatcache(true,$part);exportZip($out,$name,$part,$central);
        }
        $parts['[Content_Types].xml']=$types.'</Types>';$parts['xl/workbook.xml']=$workbook.'</sheets></workbook>';$parts['xl/_rels/workbook.xml.rels']=$rels.'</Relationships>';
        foreach($parts as $name=>$xml){if(file_put_contents($part,$xml)!==strlen($xml))throw new RuntimeException('Export write failed');clearstatcache(true,$part);exportZip($out,$name,$part,$central);}
        $offset=ftell($out);foreach($central as $entry)exportWrite($out,$entry);$size=ftell($out)-$offset;
        exportWrite($out,pack('VvvvvVVv',0x06054b50,0,0,count($central),count($central),$size,$offset,0));
        $success=true;return $archive;
    }finally{if(is_resource($out))fclose($out);if(is_file($part))unlink($part);if(!$success&&is_file($archive))unlink($archive);}
}
function exportPrint(array $data,array $fields,array $stored): never {
    $escape=static fn($v)=>htmlspecialchars(is_array($v)?json_encode($v,JSON_UNESCAPED_UNICODE):(string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    require __DIR__.'/branding.php';
    header('Content-Type: text/html; charset=UTF-8');
    $titles=['ข้อมูลมารดาและการรับไว้รักษา','การตั้งครรภ์และ ANC','การคลอดและทารก','APGAR และการช่วยเหลือ','การดูแลและจำหน่าย','ผลตรวจที่บันทึก','ภาวะแทรกซ้อน'];
    ?><!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>แบบสรุปเวชระเบียนรายบุคคล | LR EMR</title>
    <style>@page{size:A4;margin:14mm}body{font:12px Tahoma,sans-serif;color:#172c3b;margin:24px;background:#edf1f5}main{max-width:1000px;margin:auto;background:white;padding:28px}.toolbar{display:flex;gap:14px;align-items:center;margin-bottom:20px}button{padding:12px 20px;background:#9d3376;color:white;border:0;border-radius:7px;cursor:pointer}.identity{border:1px solid #718391;padding:14px;line-height:1.8}h1{font-size:22px}h2{font-size:16px;background:#edf3f7;padding:10px;margin-top:24px}h3{font-size:13px}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));border-left:1px solid #ccd5dc;border-top:1px solid #ccd5dc}.field{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.1fr);border:1px solid #ccd5dc;border-left:0;border-top:0;padding:8px;gap:8px;break-inside:avoid;overflow-wrap:anywhere}.label{color:#526274}.value{white-space:pre-wrap}.muted{color:#667788}.case{break-before:page}.case:first-of-type{break-before:auto}table{border-collapse:collapse;width:100%;font-size:10px;table-layout:fixed}th,td{border:1px solid #ccd5dc;padding:6px;text-align:left;overflow-wrap:anywhere;vertical-align:top}thead{display:table-header-group}tr{break-inside:avoid}footer{margin-top:24px;border-top:1px solid #aaa;padding-top:12px}.sign{display:flex;justify-content:space-between;margin-top:35px}@media print{body{margin:0;background:white}main{padding:0;max-width:none}.toolbar{display:none}h2,h3{break-after:avoid}.identity{break-inside:avoid}a{color:inherit}}</style>
    <main><div class="toolbar"><button onclick="window.print()">พิมพ์ / บันทึก PDF</button><span>ตรวจสอบข้อมูลก่อนพิมพ์ · แสดงเฉพาะข้อมูลที่บันทึกแล้ว</span></div><h1><?=$escape($hospitalName?:'LR EMR')?> · แบบสรุปเวชระเบียนรายบุคคล</h1>
    <?php foreach($data['main'] as $caseIndex=>$row): ?><article class="case"><div class="identity"><strong><?=$escape($row['ชื่อ-สกุลมารดา']??'')?></strong><br>HN <?=$escape($row['HN มารดา'])?> · AN <?=$escape($row['AN มารดา'])?> · ทารกลำดับ <?=$escape($row['ลำดับทารก']??'—')?><br>Admit <?=$escape($row['วันที่ Admit']??'')?> · จำหน่าย <?=$escape($row['วันที่จำหน่ายมารดา']??'')?></div>
    <?php foreach($titles as $tab=>$title): if(!array_filter($fields,static fn($field)=>$field['tab']===$tab))continue; ?><h2><?=$escape($title)?></h2><div class="fields"><?php foreach($fields as $field): if($field['tab']!==$tab)continue;$label=$field['label'];$value=$row[$label]??null; ?><div class="field"><span class="label"><?=$escape($label)?></span><span class="value"><?=$value===null||$value===''?'—':$escape($value)?><?php if(isset($row['_manual'][$label])): ?><small class="muted"> [ข้อมูลเสริม]</small><?php endif; ?></span></div><?php endforeach; ?></div><?php endforeach; ?>
    <h2>ข้อมูลเชื่อมโยงแม่–ลูก</h2><p>AN ทารกที่เชื่อมโยง: <?=$escape(implode(', ',$row['_child_ans']??[])?:'—')?> · สถานะ: <?=$escape($row['_family_status']??'—')?></p>
    <?php if(!empty($row['_encoding_issues'])): ?><p>พบไบต์ข้อมูลเดิมที่แปลงอักขระไม่ได้ แสดงเป็น \xNN ในช่องที่เกี่ยวข้อง และเก็บข้อมูลดิบ hex ไว้ในชีตข้อมูลเชื่อมโยง: <?=$escape(implode(', ',array_keys($row['_encoding_issues'])))?></p><?php endif; ?>
    </article><?php endforeach; ?>
    <?php foreach(['labs'=>'Lab ทะเบียนคลอด','lab_results'=>'ผลห้องปฏิบัติการ','complications'=>'ภาวะแทรกซ้อน','vaccines'=>'วัคซีน'] as $key=>$title): ?><h2><?=$escape($title)?></h2><?php $rows=$data[$key]??[];if(!$rows): ?><p class="muted">ไม่พบรายการ</p><?php else: foreach($rows as $number=>$row): ?><h3>รายการ <?=$number+1?></h3><div class="fields"><?php foreach($row as $label=>$value): ?><div class="field"><span class="label"><?=$escape($label)?></span><span class="value"><?=$value===null||$value===''?'—':$escape($value)?></span></div><?php endforeach; ?></div><?php endforeach;endif;endforeach; ?>
    <footer>ข้อมูลจาก HOSxP และฐานข้อมูลเสริม · ค่าว่างแสดง — · พิมพ์เมื่อ <?=(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('d/m/Y H:i:s')?> (เวลาไทย)<div class="sign"><span>ผู้ตรวจสอบ ____________________</span><span>ลงชื่อ ____________________ วันที่ ____________</span></div></footer></main></html><?php exit;
}
