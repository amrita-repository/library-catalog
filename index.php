<?php
require 'API.php';

$api = new API();
$res = $api->fireUp("kurose");
echo $api->getBookDetails($res['action'], $res['user_name'], 1);
