<?php

header('HTTP/1.1 200 OK');
header('Content-Type: application/json');

exit(json_encode(array('prePayed' => htmlspecialchars($_GET['prePayed']), 'orderID' => (int)$_GET['orderID'])));