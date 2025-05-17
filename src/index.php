<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Router\Resolver as Resolver;

$resolver = new Resolver(require 'routes.php'); 

$result = $resolver->execute(
    'GET',
    '/artist/0eb1239/getTopTracks',
    [
        'token' => 'asdasdasdasd' 
    ]
);

echo "<pre>";
var_dump($result);
echo "</pre>";