<?php
/**
 * Invyrr — Backup to Google Drive (SpreadsheetML, no extensions)
 * POST /api/backup_drive.php
 */
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
startSession();
requireRole('admin', 'manager');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$pdo  = getDB();
$user = currentUser();
$date = date('Y-m-d_H-i-s');

$token = getSetting($pdo, 'google_drive_token');
if (!$token) jsonError('Google Drive token not configured. Add it in Settings → Google Drive.');

// ── SpreadsheetML helpers ─────────────────────────────────
function xlCell($val): string {
    if ($val===null||$val==='') return '<Cell><Data ss:Type="String"></Data></Cell>';
    if (is_numeric($val)) return '<Cell ss:StyleID="num"><Data ss:Type="Number">'.htmlspecialchars((string)$val,ENT_XML1).'</Data></Cell>';
    return '<Cell><Data ss:Type="String">'.htmlspecialchars((string)$val,ENT_XML1).'</Data></Cell>';
}
function buildWorksheet(string $name, array $headers, array $rows): string {
    $xml='<Worksheet ss:Name="'.htmlspecialchars($name,ENT_XML1).'"><Table><Row>';
    foreach($headers as $h) $xml.='<Cell ss:StyleID="hdr"><Data ss:Type="String">'.htmlspecialchars((string)$h,ENT_XML1).'</Data></Cell>';
    $xml.='</Row>';
    foreach($rows as $row){$xml.='<Row>';foreach($row as $v) $xml.=xlCell($v);$xml.='</Row>';}
    return $xml.'</Table></Worksheet>';
}
function buildSpreadsheetML(array $sheets): string {
    $xml='<?xml version="1.0" encoding="UTF-8"?>'."\n".'<?mso-application progid="Excel.Sheet"?>'."\n";
    $xml.='<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel"><Styles>';
    $xml.='<Style ss:ID="Default"><Font ss:FontName="Calibri" ss:Size="11"/></Style>';
    $xml.='<Style ss:ID="hdr"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1E3A5F" ss:Pattern="Solid"/></Style>';
    $xml.='<Style ss:ID="num"><NumberFormat ss:Format="General"/></Style>';
    $xml.='</Styles>';
    foreach($sheets as $name=>$data) $xml.=buildWorksheet($name,$data['header'],$data['rows']);
    return $xml.'</Workbook>';
}

// ── Build data ────────────────────────────────────────────
$locs=$pdo->query("SELECT id,name FROM locations ORDER BY is_default DESC,name")->fetchAll();
$locCols='';foreach($locs as $l){$id=(int)$l['id'];$locCols.=", COALESCE((SELECT pl.stock FROM product_locations pl WHERE pl.product_id=p.id AND pl.location_id=$id),0) AS loc_$id";}
$prows=$pdo->query("SELECT p.sku,p.name,p.brand,p.category,v.name AS vendor,p.cost,p.landing_cost,p.sell,ROUND(CASE WHEN p.sell>0 THEN ((p.sell-p.cost)/p.sell)*100 ELSE 0 END,1),p.case_content,IF(p.combo=1,'Yes','No'),p.stock,p.min_stock,p.unit,ROUND(p.stock*p.cost,0)$locCols FROM products p LEFT JOIN vendors v ON v.id=p.vendor_id ORDER BY p.brand,p.name")->fetchAll(PDO::FETCH_NUM);
$phdr=['SKU','Product Name','Brand','Category','Vendor','Cost','Landing Cost','Sell Price','Margin%','Case Content','Combo','Total Stock','Min Stock','Unit','Stock Value'];
foreach($locs as $l) $phdr[]='Stock: '.$l['name'];
$sheets=[
    'Products' =>['header'=>$phdr,'rows'=>$prows],
    'Vendors'  =>['header'=>['Vendor Name','Type','Contact','Phone','Email','City','GST'],'rows'=>$pdo->query("SELECT name,type,contact,phone,email,city,gst FROM vendors ORDER BY name")->fetchAll(PDO::FETCH_NUM)],
    'Stock In' =>['header'=>['Date','Product','Location','Vendor','Qty','Cost','Total','Note'],'rows'=>$pdo->query("SELECT si.date,p.name,l.name,v.name,si.qty,si.cost,ROUND(si.qty*si.cost,0),si.note FROM stock_in si JOIN products p ON p.id=si.product_id LEFT JOIN locations l ON l.id=si.location_id LEFT JOIN vendors v ON v.id=si.vendor_id ORDER BY si.date DESC")->fetchAll(PDO::FETCH_NUM)],
    'Stock Out'=>['header'=>['Date','Product','Location','Customer','Qty','Sell Price','Cost','Profit','Note'],'rows'=>$pdo->query("SELECT so.date,p.name,l.name,so.customer,so.qty,so.sell_price,so.cost,ROUND((so.sell_price-so.cost)*so.qty,0),so.note FROM stock_out so JOIN products p ON p.id=so.product_id LEFT JOIN locations l ON l.id=so.location_id ORDER BY so.date DESC")->fetchAll(PDO::FETCH_NUM)],
    'P&L'      =>['header'=>['Product','Sold Qty','Revenue','COGS','Profit','Margin%'],'rows'=>$pdo->query("SELECT p.name,SUM(so.qty),ROUND(SUM(so.sell_price*so.qty),0),ROUND(SUM(so.cost*so.qty),0),ROUND(SUM((so.sell_price-so.cost)*so.qty),0),ROUND(CASE WHEN SUM(so.sell_price*so.qty)>0 THEN SUM((so.sell_price-so.cost)*so.qty)/SUM(so.sell_price*so.qty)*100 ELSE 0 END,1) FROM stock_out so JOIN products p ON p.id=so.product_id GROUP BY so.product_id,p.name ORDER BY 5 DESC")->fetchAll(PDO::FETCH_NUM)],
];

$content  = buildSpreadsheetML($sheets);
$filename = 'Invyrr_Backup_'.$date.'.xls';
$mimeType = 'application/vnd.ms-excel';

// ── Save locally ──────────────────────────────────────────
$backupDir=__DIR__.'/../backups/';
if(!is_dir($backupDir)) mkdir($backupDir,0755,true);
$localPath=$backupDir.$filename;
file_put_contents($localPath,$content);

// ── Upload to Drive ───────────────────────────────────────
function driveReq(string $method,string $url,array $hdrs,$body=null):array{
    $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$hdrs,CURLOPT_SSL_VERIFYPEER=>true]);
    if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
    $resp=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    return['status'=>$status,'data'=>json_decode($resp,true)??[],'raw'=>$resp];
}
$auth="Authorization: Bearer $token";
$search=driveReq('GET','https://www.googleapis.com/drive/v3/files?q='.urlencode("name='Invyrr Backups' and mimeType='application/vnd.google-apps.folder' and trashed=false").'&fields=files(id)',[$auth,'Accept: application/json']);
if($search['status']===401){@unlink($localPath);jsonError('Google Drive token expired. Please update in Settings.');}
$folderId=$search['data']['files'][0]['id']??null;
if(!$folderId){$c=driveReq('POST','https://www.googleapis.com/drive/v3/files',[$auth,'Content-Type: application/json'],json_encode(['name'=>'Invyrr Backups','mimeType'=>'application/vnd.google-apps.folder']));$folderId=$c['data']['id']??null;}
if(!$folderId){@unlink($localPath);jsonError('Could not create Google Drive folder.');}
$boundary='smb_'.bin2hex(random_bytes(8));
$meta=json_encode(['name'=>$filename,'parents'=>[$folderId]]);
$body="--$boundary\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n$meta\r\n--$boundary\r\nContent-Type: $mimeType\r\n\r\n$content\r\n--$boundary--";
$upload=driveReq('POST','https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink',[$auth,"Content-Type: multipart/related; boundary=$boundary",'Content-Length: '.strlen($body)],$body);
if(empty($upload['data']['id'])){@unlink($localPath);jsonError('Drive upload failed: '.($upload['data']['error']['message']??$upload['raw']));}
$size=strlen($content);
file_put_contents($localPath.'.meta',json_encode(['type'=>'drive','filename'=>$filename,'drive_id'=>$upload['data']['id'],'link'=>$upload['data']['webViewLink']??'','created_at'=>date('Y-m-d H:i:s'),'created_by'=>$user['name']??'unknown','size'=>$size]));
auditLog($pdo,'backup_drive','database',0,$filename);
jsonOk(['filename'=>$filename,'drive_id'=>$upload['data']['id'],'link'=>$upload['data']['webViewLink']??''],'Backed up to Google Drive: '.$filename);
