<?php
ini_set('display_errors', true);
set_time_limit(0);
require_once './vendor/autoload.php';

#constante global: Em Separação -> 15
const EM_SEPARACAO = 15;
#constante global: Em Produção -> 17
const EM_PRODUCAO = 17;

#constante global: Duração do cache -> 7200ms = 2h
const DURACAO_CACHE = 7200;

#imports
use App\Bling;
use App\Correios\Correios;
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
$pedidosC = $ic->getItem('pedidos');
$separacaoC = $ic->getItem('separacao');
$producaoC = $ic->getItem('producao');

if (is_null($pedidosC->get())) {
    if (is_null($separacaoC->get())) {
        $pedidos = $integrada->getPedidos([
            'situacao_id' => EM_SEPARACAO,
            'limit' => 500,
        ]);
        $separacaoC->set($pedidos)->expiresAfter(DURACAO_CACHE);
        $ic->save($separacaoC);
    }

    if (is_null($producaoC->get())) {
        $pedidos = $integrada->getPedidos([
            'situacao_id' => EM_PRODUCAO,
            'limit' => 500,
        ]);
        $producaoC->set($pedidos)->expiresAfter(DURACAO_CACHE);
        $ic->save($producaoC);
    }

    $separacao = $separacaoC->get();
    $producao = $producaoC->get();
    $pedidosLojaIntegrada = array_merge($separacao->objects, $producao->objects);
    #fim da consulta [pedidos armazenados em: $separacao, $producao]

    #inicio da consulta de pedidos Bling
    $pedidosBlingC = $ic->getItem('bling');
    if (is_null($pedidosBlingC->get())) {
        $pedidosBlingA = [];
        $filtros = [
            'filters' => 'dataEmissao[28/12/2017 TO 28/01/2018]', //TODO: Definir filtros dinâmicos
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

        $pedidosBlingC->set($pedidosBlingA)->expiresAfter(DURACAO_CACHE);
        $ic->save($pedidosBlingC);
    }

    $pedidosBling = $pedidosBlingC->get();
    ##fim consulta de pedidos Bling;

    $pedidos = [];
    ##cruzando pedidos bling <-> loja integrada e populando array $pedidos;
    foreach ($pedidosBling as $pbling) {
        $p = array_filter($pedidosLojaIntegrada, function ($obj) use ($pbling) {
            if (isset($pbling->pedido->numeroPedidoLoja)) {
                return ((int) $obj->numero == (int) $pbling->pedido->numeroPedidoLoja);
            }
            return false;
        });

        $p = array_values($p);
        @$p[0]->bling_id = $pbling->pedido->numero;
        @$p[0]->codigo_rastreio = $pbling->pedido->codigosRastreamento->codigoRastreamento;

        if (count($p) && isset($p[0]->numero)) {
            $pedidos = array_merge($pedidos, $p);
        }
    }
    ##fim cruzamento de dados - array $pedidos populada com os dados atualizados;

    ##consultando envio_id de cada pedido
    foreach ($pedidos as $k => $pedido) {
        $pLoja = $integrada->getPedido($pedido->numero);
        $envioId = $pLoja->envios[0]->id;
        $pedido->envio_id = $envioId;

        //TODO: Gerar LOG? Remover ob_flush
        echo ($k + 1) . ' - ';
        flush();
        ob_flush();

        usleep(500000); // sleep time 0.5s !! NÃO ALTERAR !!
    }
    #fim da consulta de envio_id - array $pedidos atualizada com os id's de envio;

    #armazenando os dados em cache;
    $pedidosC->set($pedidos)->expiresAfter(DURACAO_CACHE);
    $ic->save($pedidosC);
}

$pedidos = $pedidosC->get();

#separa pedidos sem código de rastreio disponível
$semRastreio = array_filter($pedidos, function ($p) {
    return empty($p->codigo_rastreio);
});

#separa pedidos com código de rastreio disponível
$comRastreio = array_filter($pedidos, function ($p) {
    return !empty($p->codigo_rastreio);
});

echo '<pre>';
// var_dump($comRastreio);
// $idPedido = 30584;
// $filtr = array_filter($comRastreio, function ($p) use ($idPedido) {
//     return ($p->numero == $idPedido);
// });
// sort($filtr);
// $iPedido = $filtr[0];

// $idEnvio = $iPedido->envio_id;
// $rastreio = $iPedido->codigo_rastreio;
// var_dump($idEnvio);
// $r = $integrada->atualizaRastreio($idEnvio, '');

// $pedido = $integrada->getPedido($idPedido);
// var_dump($r);
// var_dump($iPedido);
$correios = new Correios();
$rastreio = $correios->rastrearObjeto('OA709275324BR');
var_dump($rastreio);
// $log->info('Pedido Bling ID: ' . $pbling->pedido->numero . ' - Loja ID:' . $p[0]->numero . ' :: ATUALIZADO DE ' . $p[0]->situacao->codigo . ' PARA enviado');
