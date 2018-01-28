<?php
ini_set('display_errors', true);
require_once './vendor/autoload.php';

#imports
use App\Bling;
use App\LojaIntegrada;
use phpFastCache\CacheManager; // gestor de cache

#configuração do gestor de cache
CacheManager::setDefaultConfig([
    'path' => './cache',
]);
$ic = CacheManager::getInstance('files');

#incialização das Api's Loja Integrada e Bling
$integrada = new LojaIntegrada();
$bling = new Bling();

// $pedidos = $bling->getPedidos(['page' => 0]);
$pedidos = $integrada->getPedido(1000);

echo '<pre>';
var_dump($pedidos);
die;
