<?php

chdir(dirname(__DIR__));

require __DIR__ . '/core/wsServerRest.php';

ob_start();
$server = new wsServerRest();
$server->dispatch();
?>