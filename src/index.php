<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Router\Resolver as Resolver;

$resolver = new Resolver(require 'routes.php'); 

$result = $resolver->execute(
    'POST',
    '/artist/0e1203991/getTopTracks?limit=100&offset=0',
    []
);

echo "<pre>";
var_dump($result);
echo "</pre>";