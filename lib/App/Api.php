<?php
/*
 * Classe para envio de requisições GET, POST para API's via curl
 * Caso haja a necessidade de fazer autenticação na API via HEADER da requisição
 * ou adicionar alguma informação extra no HEADER da requisição,
 * armazene os mesmos em um array e envie através do construct da classe.
 * Ex.:
 *      $header = [
 *                   'Content-Type: application/json',
 *                   'Authorization: token ' . $token,
 *                ];
 *      $api = new Api($header);
 */
namespace App;

class Api
{
    protected $url;
    private $_params;
    private $_method;
    private $_headers;
    private $_ch;

    public function __construct($headers = null)
    {
        $this->_headers = $headers;
        self::init();
    }

    private function headerAppend()
    {
        is_null($this->_headers) ?: curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $this->_headers);
    }

    private function init()
    {
        $this->_ch = curl_init();
        self::headerAppend();
    }

    protected function get($url, $params = null)
    {
        self::init();
        $this->url = $url;

        curl_setopt($this->_ch, CURLOPT_HTTPGET, true);

        if (is_array($params)) {
            $this->url = sprintf("%s?%s", $this->url, http_build_query($params));
        }

        return self::exec();
    }

    protected function post($url, $params = null)
    {
        self::init();
        $this->url = $url;

        curl_setopt($this->_ch, CURLOPT_POST, true);

        if (is_array($params)) {
            curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $params);
        }

        return self::exec();
    }

    protected function put($url, array $params)
    {
        self::init();
        $this->url = $url;

        $data = json_encode($params);
        if (is_null($this->_headers)) {
            $this->_headers = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data),
            ];
        } else {
            $this->_headers[] = 'Content-Length: ' . strlen($data);
        }

        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $this->_headers);
        curl_setopt($this->_ch, CURLOPT_PUT, true);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $data);

        return self::exec();
    }

    private function exec()
    {
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_URL, $this->url);

        $r = curl_exec($this->_ch);

        if (false === $r) {
            // Caso ocorra algum erro de consulta CURL, invoca um Exception.
            throw new \Exception(curl_error($this->_ch), curl_errno($this->_ch));
            //TODO: Enviar notificação de erro no script
        }

        curl_close($this->_ch);

        return $r;
    }
}
