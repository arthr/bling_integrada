<?php

namespace App\Notifications;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Logs extends Logger
{
    protected $log;
    protected $config;

    public function __construct($instance = 'logs')
    {
        parent::__construct($instance);
        $this->pushHandler(new StreamHandler(BASE_DIR . '/logs/info.log', Logger::INFO));
        $this->pushHandler(new StreamHandler(BASE_DIR . '/logs/error.log', Logger::ERROR));
        $this->pushHandler(new StreamHandler(BASE_DIR . '/logs/alert.log', Logger::ALERT));
        $this->pushHandler(new StreamHandler(BASE_DIR . '/logs/objetos.log', Logger::NOTICE));
    }
}
