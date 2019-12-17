<?php
/**
 * Copyright (c) 2019 Rajkumar S
 */

require 'API.php';
require 'vendor/autoload.php';

$api = new API();
header('Content-Type: application/json');

Flight::route('/', function () use ($api) {
    echo "Hello there! Use the designated routes. Nothing is served from root.";
});

Flight::route('GET /mappings', function () use ($api) {
    echo $api->init('mappings');
});

Flight::route('GET /search', function () use ($api) {
    echo $api->init('search', $_GET['q'], $_GET['docType'], $_GET['field']);
});

Flight::route('POST /get-book-details', function () use ($api) {
    echo $api->getBookDetails($_POST['action'], $_POST['user_name'], $_POST['id']);
});

Flight::route('POST /checkouts',function () use ($api){
    echo $api->init('checkouts', $_POST['memberid'],$_POST['password']);
});

Flight::start();
