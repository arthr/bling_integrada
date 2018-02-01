<?php
ini_set('display_errors', true);
set_time_limit(0);
require_once './vendor/autoload.php';

#constante diretório base
const BASE_DIR = __DIR__;

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
use App\Notifications\Logs;
use App\Notifications\Mailer;
use phpFastCache\CacheManager; // gestor de cache

#configuração do gestor de cache
CacheManager::setDefaultConfig([
    'path' => BASE_DIR . '/cache',
]);
$ic = CacheManager::getInstance('files');

#limpar cache manualmente
if (isset($_GET['clearcache']) && $_GET['clearcache'] == 'true') {
    $ic->clear();
}

#inicialização gestor de logs
$log = new Logs();

#inicialização email service
$configs = [
    'host' => 'smtp.gmail.com',
    'username' => 'debuggrobot@gmail.com',
    'password' => '55465213',
    'remetente' => 'Cronjob Loja Integrada - Bling',
];

$mail = new Mailer($configs);

#incialização das Api's Loja Integrada e Bling
$integrada = new LojaIntegrada();
$bling = new Bling();

#inicialização WebService Correios
$correios = new Correios();

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

    $separacao = isset($separacao->objects) ? $separacao->objects : [];
    $producao = isset($producao->objects) ? $producao->objects : [];

    $pedidosLojaIntegrada = array_merge($producao, $separacao);
    #fim da consulta [pedidos armazenados em: $separacao, $producao]

    #se forem encontrados pedidos na loja integrada, a contagem é registrada.
    #caso contrário, o script é interrompido e um registro informativo é criado.
    if (count($pedidosLojaIntegrada)) {
        $log->info('Foram localizados um total de ' . count($pedidosLojaIntegrada) . ' pedidos na Loja Integrada.');
        $log->info(count($separacao) . ' pedidos em separação.');
        $log->info(count($producao) . ' pedidos em produção.');
    } else {
        $log->info('Não forma localizados pedidos pendentes na Loja Integrada. O Script foi interrompido.');
        $mail->notificar('0 Pedidos Processados', 'Não forma localizados pedidos pendentes na Loja Integrada. O Script foi interrompido.');
        exit;
    }

    #inicio da consulta de pedidos Bling
    $pedidosBlingC = $ic->getItem('bling');
    if (is_null($pedidosBlingC->get())) {
        $pedidosBlingA = [];

        //definição do filtro dinâmico
        end($pedidosLojaIntegrada);
        $key = key($pedidosLojaIntegrada);
        $dtIni = new DateTime($pedidosLojaIntegrada[0]->data_criacao);
        $dtIni->add(new DateInterval('P1D'));
        $dtEnd = new DateTime($pedidosLojaIntegrada[$key]->data_criacao);
        $dtEnd->add(new DateInterval('P10D'));
        $filtro = $dtIni->format('d/m/Y') . ' TO ' . $dtEnd->format('d/m/Y');

        $filtros = [
            'filters' => "dataEmissao[$filter]",
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

$log->info('Foram localizados um total de ' . count($semRastreio) . ' pedidos sem código de rastreio.');
if (count($semRastreio)) {
    $mail->notificar(count($semRastreio) . 'Pedidos sem Rastreio', 'Foram localizados um total de ' . count($semRastreio) . ' pedidos sem código de rastreio.');
}

#separa pedidos com código de rastreio disponível
$comRastreio = array_filter($pedidos, function ($p) {
    return !empty($p->codigo_rastreio);
});

#percorre os pedidos com código de rastreio e atualiza seus status
foreach ($comRastreio as $objeto) {
    $envioId = $objeto->envio_id;
    $codigoRastreio = $objeto->codigo_rastreio;
    $pedidoId = $objeto->numero;

    //Busca objeto na base dos correios
    $rastreio = $correios->rastrearObjeto($codigoRastreio);

    //Atualiza código de rastreio do pedido
    $ras = $integrada->atualizaRastreio($envioId, $codigoRastreio);

    //Se o objeto for localizado na base dos correios ele atualiza a situação do pedido
    if (!isset($rastreio->erro)) {
        $sit = $integrada->atualizaSituacao($pedidoId);
    } else {
        $log->notice("Objeto $codigoRastreio do pedido $pedidoId não localizado.");
    }
}
