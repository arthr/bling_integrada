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
use Monolog\Handler\StreamHandler;
use Monolog\Logger; // gestor de logs
use phpFastCache\CacheManager; // gestor de cache

#configuração do gestor de cache
CacheManager::setDefaultConfig([
    'path' => './cache',
]);
$ic = CacheManager::getInstance('files');

#configuração do gestor de logs
$log = new Logger('pedidos');
$log->pushHandler(new StreamHandler('./logs/pedidos.log', Logger::INFO));

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
        'limit' => 500,
    ]);
    $producaoC->set($pedidos)->expiresAfter(3600);
    $ic->save($producaoC);
}

$separacao = $separacaoC->get();
$producao = $producaoC->get();
$pedidosLojaIntegrada = array_merge($separacao->objects, $producao->objects);
#fim da consulta [pedidos armazenados em: $separacao, $producao]

#inicio da consulta e atualização de pedidos na Bling
$pedidosBlingC = $ic->getItem('bling');
if (is_null($pedidosBlingC->get())) {
    $pedidosBlingA = [];
    $filtros = [
        'filters' => 'dataEmissao[28/12/2017 TO 28/01/2018]',
        'page' => 0,
    ];

    $hasPage = true;

    while ($hasPage) {
        $getBling = $bling->getPedidos($filtros);
        if (isset($getBling->retorno->erros)) {
            $hasPage = false;
        } else {
            $filtros['page']++;
            $pedidosBlingA = array_merge($pedidosBlingA, $getBling->retorno->pedidos);
        }
    }

    $pedidosBlingC->set($pedidosBlingA)->expiresAfter(3600);
    $ic->save($pedidosBlingC);
}

$pedidosBling = $pedidosBlingC->get();

$pedidos = [];

foreach ($pedidosBling as $pbling) {
    $p = array_filter($pedidosLojaIntegrada, function ($obj) use ($pbling) {
        if (isset($pbling->pedido->numeroPedidoLoja)) {
            return ((int) $obj->numero == (int) $pbling->pedido->numeroPedidoLoja);
        }
        return false;
    });

    $p = array_values($p);
    @$p[0]->bling_id = ($pbling instanceof stdClass) ? $pbling->pedido->numero : false;

    if (count($p) && isset($p[0]->numero)) {
        $pedidos = array_merge($pedidos, $p);
    }
}

echo '<pre>';
foreach($pedidos as $pedido) {
    var_dump($pedido);
    // $log->info('Pedido Bling ID: ' . $pbling->pedido->numero . ' - Loja ID:' . $p[0]->numero . ' :: ATUALIZADO DE ' . $p[0]->situacao->codigo . ' PARA enviado');
}
