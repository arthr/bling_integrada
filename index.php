<?php
ini_set('display_errors', true);
require_once './vendor/autoload.php';

#constante global: Em Separação -> 15
const EM_SEPARACAO = 15;
#constante global: Em Produção -> 17
const EM_PRODUCAO = 17;

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

#inicio da consulta de pedidos na Loja Integrada
$separacaoC = $ic->getItem('separacao');
$producaoC = $ic->getItem('producao');

if (is_null($separacaoC->get())) {
    $pedidos = $integrada->getPedidos([
        'situacao_id' => EM_SEPARACAO,
        'limit' => 500,
    ]);
    $separacaoC->set($pedidos)->expiresAfter(3600);
    $ic->save($separacaoC);
}

if (is_null($producaoC->get())) {
    $pedidos = $integrada->getPedidos([
        'situacao_id' => EM_PRODUCAO,
    ]);
    $producaoC->set($pedidos)->expiresAfter(3600);
    $ic->save($producaoC);
}

$separacao = $separacaoC->get();
$producao = $producaoC->get();
#fim da consulta [pedidos armazenados em: $separacao, $producao]

$pedidosBling = $bling->getPedidos();

echo '<pre>';
var_dump($pedidosBling);
