<?php
include "../IopSdk.php";

echo $_SERVER['REMOTE_ADDR'];

$access_token = '50000701327tdjqgueJPCwRG8kCt5ovWDHGtnvi1fbf1ccfqSHHbHrPcRxTVJXu';

$c = new IopClient('api.miravia.es/rest', '506856', 'FWxhGHFdIvs2lVdTeq48se3SSNj0dfEs');
// $c->logLevel = Constants::$log_level_debug;
$request = new IopRequest('/products/get','GET');
$request->addApiParam('itemId', '1358319568197033');
$request->addApiParam('authDO', '{"sellerId":ES1S5Z78J5M}');

var_dump($c->execute($request, $access_token));
// echo PHP_INT_MAX;
// echo PHP_INI_SYSTEM;
// var_dump($c->msectime());
// echo IOP_AUTOLOADER_PATH;
?>