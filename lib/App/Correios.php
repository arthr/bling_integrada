<?php
/*
 * Classe para Consumo do webservice dos Correios
 * https://www.correios.com.br/para-voce/correios-de-a-a-z/pdf/rastreamento-de-objetos/manual_rastreamentoobjetosws.pdf
 */
namespace App;

use App\Api;

class Correios extends Api
{
    private $usuario = 'ECT';
    private $senha = 'SRO';
    private $tipo = 'L';
    private $resultado = 'T';
    private $lingua = '101';

    private $urlRastreio = 'http://webservice.correios.com.br/service/rastro/Rastro.wsdl';

    protected $objeto;

    public function rastrearObjeto($objeto)
    {
        $params = [
            'usuario' => $this->usuario,
            'senha' => $this->senha,
            'tipo' => $this->tipo,
            'resultado' => $this->resultado,
            'lingua' => $this->lingua,
            'objetos' => $objeto,
        ];

        return $this->post($this->urlRastreio, $params);
    }

}
