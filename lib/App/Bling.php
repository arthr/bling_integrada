<?php
/*
 * Classe para Consumo da Bling API v2 (https://manuais.bling.com.br/api)
 * Não há necessidade de alterar os métodos, todos retornam os seus respectivos dados no formato json string.
 * Você pode alterar o formato de retorno da api informando o mesmo no construct da classe.
 * Os formatos disponíveis são: JSON, stdClass object
 * Uso:
 *      new Bling('json');  //return json
 *      new Bling();        //return object
 */
namespace App;

use App\Api;

class Bling extends Api
{
    protected $apikey = 'e91ba0dff5a2a00ea2ecf97c6c23fa7ca6ce93c30c8af5387808a9cb93068b1c6b338c2e';
    private $urlApi = 'https://bling.com.br/Api/v2/';

    private $urlPedidos = 'pedidos/';
    private $urlPedido = 'pedido/';

    private $_json = false;

    public function __construct($json = null)
    {
        $this->_json = ($json == 'json');
    }

    public function getPedidos($params = [])
    {
        if (array_key_exists('page', $params)) {
            $url = $this->urlApi . $this->urlPedidos . 'page=' . $params['page'] . '/json';
        } else {
            $url = $this->urlApi . $this->urlPedidos . '/json';
        }

        $params['apikey'] = $this->apikey;
        return self::format($this->get($url, $params));
    }

    public function getPedido($numero)
    {
        $url = $this->urlApi . $this->urlPedido . $numero . '/json';
        $params['apikey'] = $this->apikey;
        return self::format($this->get($url, $params));
    }

    private function format($data)
    {
        return ($this->_json ? $data : json_decode($data));
    }
}
