<?php
/*
 * Classe para Consumo do webservice dos Correios
 * https://www.correios.com.br/para-voce/correios-de-a-a-z/pdf/rastreamento-de-objetos/manual_rastreamentoobjetosws.pdf
 */
namespace App\Correios;

use App\Correios\Soap;

class Correios extends Soap
{
    private $usuario = 'ECT';
    private $senha = 'SRO';
    private $tipo = 'L';
    private $resultado = 'T';
    private $lingua = '101';
    private $soap;

    protected $objeto;

    public function __construct()
    {
        $params = [
            'user' => $this->usuario,
            'pass' => $this->senha,
            'tipo' => $this->tipo,
            'resultado' => $this->resultado,
            'idioma' => $this->lingua
        ];
        $this->init($params);
    }

    public function rastrearObjeto($objeto)
    {
        return $this->get($objeto);
    }

}
