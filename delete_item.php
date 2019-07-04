<?php
require '../Meli/meli.php';
require '../configApp.php';

$meli = new Meli($appId, $secretKey);

$params = array('access_token' => $access_token);

$url = '/sites/' . $siteId;

$result = $meli->delete('/items/MLB1266168930', $params);

echo '<pre>';
print_r($result);
echo '</pre>';