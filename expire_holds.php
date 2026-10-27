<?php
declare(strict_types=1);
require __DIR__.'/config.local.php';
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$count=$pdo->exec("UPDATE trip_seats SET status='available',hold_token=NULL,hold_until=NULL WHERE status='held' AND hold_until<NOW()");
if(PHP_SAPI==='cli') echo "Reservas liberadas: {$count}\n";
