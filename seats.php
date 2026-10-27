<?php
declare(strict_types=1);
require __DIR__.'/config.local.php';
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$trip=(int)($_GET['trip']??0);
$pdo->prepare("UPDATE trip_seats SET status='available',hold_token=NULL,hold_until=NULL WHERE trip_id=? AND status='held' AND hold_until < NOW()")->execute([$trip]);
$q=$pdo->prepare('SELECT ts.id,ts.label,ts.deck_level,ts.status,ts.seat_type FROM trip_seats ts WHERE ts.trip_id=? ORDER BY ts.deck_level,ts.position_row,ts.position_col');$q->execute([$trip]);$decks=[];foreach($q as $s){$decks[$s['deck_level']][]=$s;}
header('Content-Type: application/json; charset=utf-8');echo json_encode(['decks'=>array_map(fn($level,$seats)=>['level'=>$level,'seats'=>$seats],array_keys($decks),array_values($decks))],JSON_UNESCAPED_UNICODE);
