<?php
ini_set('display_errors', true);
ini_set('error_reporting', E_ALL & ~E_NOTICE);
set_time_limit(0);
require_once './vendor/autoload.php';

#constante diretório base
const BASE_DIR = __DIR__;

#constante global: Em Separação -> 15
const EM_SEPARACAO = 15;
#constante global: Em Produção -> 17
const EM_PRODUCAO = 17;

#imports
use App\Bling;
use App\LojaIntegrada;

$integrada = new LojaIntegrada();
$bling = new Bling();

$pedidoA = $integrada->getPedido(31678);

$pedidoB = $bling->getPedido(21233);

$separacao = $integrada->getPedidos([
    'situacao_id' => EM_SEPARACAO,
    'limit' => 500,
]);

$producao = $integrada->getPedidos([
    'situacao_id' => EM_PRODUCAO,
    'limit' => 500,
]);

$separacao = isset($separacao->objects) ? $separacao->objects : [];
$producao = isset($producao->objects) ? $producao->objects : [];
$pedidosLojaIntegrada = array_merge($producao, $separacao);
sort($pedidosLojaIntegrada);

if (count($pedidosLojaIntegrada)) {
    echo 'Foram localizados um total de ' . count($pedidosLojaIntegrada) . ' pedidos na Loja Integrada.<br>';
    echo count($separacao) . ' pedidos em separação.<br>';
    echo count($producao) . ' pedidos em produção.<br><br>';
} else {
    echo 'Não forma localizados pedidos pendentes na Loja Integrada. O Script foi interrompido.';
    echo '0 Pedidos Processados', 'Não forma localizados pedidos pendentes na Loja Integrada. O Script foi interrompido.';
    exit;
}

//Etapa Bling
end($pedidosLojaIntegrada);
$key = key($pedidosLojaIntegrada);
$dtIni = new DateTime($pedidosLojaIntegrada[0]->data_criacao);
$dtIni->sub(new DateInterval('P1D'));
$dtEnd = new DateTime($pedidosLojaIntegrada[$key]->data_criacao);
$dtEnd->add(new DateInterval('P10D'));
$filtro = $dtIni->format('d/m/Y') . ' TO ' . $dtEnd->format('d/m/Y');

$filtros = [
    'filters' => "dataEmissao[$filtro]",
    'page' => 0,
];

$pedidosBling = [];
$hasPage = true;

while ($hasPage) {
    $getBling = $bling->getPedidos($filtros);
    if (isset($getBling->retorno->erros)) {
        $hasPage = false;
    } else {
        $filtros['page']++;
        $pedidosBling = array_merge($pedidosBling, $getBling->retorno->pedidos);
    }
}

#pedidos não localizados no bling
$notfound = count($pedidosLojaIntegrada) - count($pedidosBling);
$founded = 0;

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
        $founded++;
        $pedidos = array_merge($pedidos, $p);
    }else{
        $notfound++;
    }
}
##fim cruzamento de dados - array $pedidos populada com os dados atualizados;
echo "Foram localizados $founded pedidos da Loja Integrada no Bling.<br>";
echo "$notfound não foram localizados no Bling.<br><br>";

echo "<h3>Lista de pedidos encontrados:</h3><br>";

foreach($pedidos as $row){
    echo "<p><strong>ID Loja:</strong> $row->numero - <strong>ID Bling:</strong> $row->bling_id - <strong>Rastreio:</strong> $row->codigo_rastreio</p>";
}
