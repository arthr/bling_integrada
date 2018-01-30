<?php
/*
 * Classe para Consumo da Loja Integrada API (https://lojaintegrada.docs.apiary.io/)
 * Não há necessidade de alterar os métodos, todos retornam os seus respectivos dados no formato json string.
 * Você pode alterar o formato de retorno da api informando o mesmo no construct da classe.
 * Os formatos disponíveis são: JSON, stdClass object
 * Uso:
 *      new Bling('json');  //return json
 *      new Bling();        //return object
 */
namespace App;

use App\Api;

class LojaIntegrada extends Api
{
    protected $token = '63b853890bfc0a9926f6'; //chave_api
    protected $key = '7a7134e1-dfc3-4922-b145-eb8b605171aa'; //aplicacao
    protected $headers; //header definindo formato e dados de autenticação
    private $urlApi = 'https://api.awsli.com.br/v1/'; //api url

    private $urlPedidos = 'pedido/search/'; //https://lojaintegrada.docs.apiary.io/#reference/pedido/listar-pedidos
    private $urlPedido = 'pedido/'; //https://lojaintegrada.docs.apiary.io/#reference/pedido/pedido-especifico/detalhes-do-pedido
    private $urlAttRastreio = 'pedido_envio/'; //https://lojaintegrada.docs.apiary.io/#reference/pedido/codigo-de-rastreamento/atualizar-codigo-de-rastreamento
    private $urlAttSituacao = 'situacao/pedido/'; //https://lojaintegrada.docs.apiary.io/#reference/situacoes-do-pedido/situacao-especifica/alterar-situacao-do-pedido

    private $_json = false;

    public function __construct($json = null)
    {
        $this->_json = ($json == 'json') ? true : false;
        $this->mountHeader();
        parent::__construct($this->headers);
    }

    protected function mountHeader()
    {
        $this->headers = [
            'Content-Type: application/json',
            'Authorization: chave_api ' . $this->token . ' aplicacao ' . $this->key,
        ];
    }

    public function getPedidos($params = null)
    {
        $url = $this->urlApi . $this->urlPedidos;
        return self::format($this->get($url, $params));
    }

    public function getPedido($numero)
    {
        $url = $this->urlApi . $this->urlPedido . $numero;
        return self::format($this->get($url));
    }

    public function atualizaRastreio($envioId, $rastreio)
    {
        $url = $this->urlApi . $this->urlAttRastreio . $envioId;
        $data = ['objeto' => $rastreio];
        return $this->put($url, $data);
    }

    private function format($data)
    {
        return ($this->_json) ? $data : json_decode($data);
    }

}
