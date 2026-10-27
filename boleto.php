<?php
declare(strict_types=1);
require __DIR__.'/config.local.php';
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$q=$pdo->prepare('SELECT tk.id,tk.qr_code,tk.price,p.full_name,p.dni,ts.label,ts.deck_level,t.departure_at,o.city origin,d.city destination,b.plate FROM tickets tk JOIN passengers p ON p.id=tk.passenger_id JOIN trip_seats ts ON ts.id=tk.trip_seat_id JOIN trips t ON t.id=tk.trip_id JOIN routes r ON r.id=t.route_id JOIN terminals o ON o.id=r.origin_id JOIN terminals d ON d.id=r.destination_id JOIN buses b ON b.id=t.bus_id WHERE tk.id=?');
$q->execute([(int)($_GET['id']??0)]);$ticket=$q->fetch();
if(!$ticket){http_response_code(404);exit('Boleto no encontrado');}
$printerConfigured=defined('CENTRAL_PRINT_API_URL')&&defined('CENTRAL_PRINT_SITE_TOKEN')&&defined('CENTRAL_PRINT_SUBMIT_TOKEN')&&CENTRAL_PRINT_API_URL!==''&&CENTRAL_PRINT_SITE_TOKEN!==''&&CENTRAL_PRINT_SUBMIT_TOKEN!=='';
$printResult=null;
if(isset($_GET['central'])&&$printerConfigured){
    $clean=static fn($v):string=>trim(preg_replace('/[\x00-\x1F\x7F]+/u',' ',(string)$v)??'');
    $content=implode("\n",[
        'RUTA NORTE','BOLETO DE VIAJE','BOLETO RN-'.$ticket['id'],'',
        'RUTA: '.$clean($ticket['origin']).' -> '.$clean($ticket['destination']),
        'SALIDA: '.date('d/m/Y H:i',strtotime($ticket['departure_at'])),
        'BUS: '.$clean($ticket['plate']),
        'PISO: '.$clean($ticket['deck_level']).'  ASIENTO: '.$clean($ticket['label']),
        'PASAJERO: '.$clean($ticket['full_name']),
        'DNI: '.$clean($ticket['dni']),
        'PRECIO: S/ '.number_format((float)$ticket['price'],2,'.',''),
        'CODIGO QR (ingreso manual): '.$clean($ticket['qr_code']),
        '', 'Presentar al momento del embarque.'
    ]);
    $payload=json_encode(['source'=>'Ruta Norte','external_id'=>'RUTA-NORTE-BOLETO-'.$ticket['id'],'title'=>'Boleto RN-'.$ticket['id'],'content'=>$content,'copies'=>1],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $context=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nOAI-Sites-Authorization: Bearer ".CENTRAL_PRINT_SITE_TOKEN."\r\nAuthorization: Bearer ".CENTRAL_PRINT_SUBMIT_TOKEN."\r\n",'content'=>$payload,'timeout'=>8,'ignore_errors'=>true],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
    $response=@file_get_contents(CENTRAL_PRINT_API_URL,false,$context);$status=0;
    foreach(($http_response_header??[]) as $header){if(preg_match('/^HTTP\/\S+\s+(\d+)/',$header,$match)){$status=(int)$match[1];}}
    $decoded=is_string($response)?json_decode($response,true):null;
    if(($status===200||$status===201)&&is_array($decoded)&&!empty($decoded['job']['id'])){$printResult=['ok'=>true,'status'=>(string)($decoded['job']['status']??'En cola'),'duplicate'=>(bool)($decoded['duplicate']??false)];}
    else{$printResult=['ok'=>false];}
}
$esc=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Boleto Ruta Norte</title><link rel="stylesheet" href="assets/app.css?v=20261028"></head><body class="ticket-page"><article class="ticket"><header><b>RN</b><strong>RUTA NORTE<small>BOLETO DE VIAJE</small></strong></header><div class="ticket-route"><small>RUTA</small><h1><?=$esc($ticket['origin'])?> <i>→</i> <?=$esc($ticket['destination'])?></h1></div><div class="ticket-grid"><div><small>FECHA Y HORA</small><b><?=date('d/m/Y H:i',strtotime($ticket['departure_at']))?></b></div><div><small>BUS</small><b><?=$esc($ticket['plate'])?></b></div><div><small>PISO · ASIENTO</small><b><?=$esc($ticket['deck_level'])?> · <?=$esc($ticket['label'])?></b></div><div><small>PASAJERO</small><b><?=$esc($ticket['full_name'])?></b></div><div><small>DNI</small><b><?=$esc($ticket['dni'])?></b></div><div><small>PRECIO</small><b>S/ <?=number_format((float)$ticket['price'],2)?></b></div></div><div class="qr" id="qr" data-code="<?=$esc($ticket['qr_code'])?>"></div><small class="qr-caption">Presenta este código al embarcar · <?=$esc($ticket['qr_code'])?></small><?php if($printResult):?><div class="central-print-result <?=$printResult['ok']?'success':'failure'?>"><?php if($printResult['ok']):?>Enviado a impresión central · <?= $esc($printResult['status']) ?><?= $printResult['duplicate']?' · ya estaba en cola':'' ?><?php else:?>No se pudo enviar a la impresora central. El boleto quedó guardado.<?php endif;?></div><?php endif;?><?php if($printerConfigured):?><a class="search-btn print central-print-link" href="boleto.php?id=<?=(int)$ticket['id']?>&amp;central=1"><?=$printResult&&!$printResult['ok']?'Reintentar impresión central':'Enviar a impresora central'?></a><?php endif;?><button type="button" class="outline print" onclick="window.print()">Imprimir desde este dispositivo</button></article><script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>if(window.QRCode)new QRCode(document.querySelector('#qr'),{text:document.querySelector('#qr').dataset.code,width:144,height:144});</script></body></html>