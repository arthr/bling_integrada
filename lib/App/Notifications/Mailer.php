<?php

namespace App\Notifications;

use PHPMailer\PHPMailer\PHPMailer;

class Mailer extends PHPMailer
{
    public function __construct(array $configs, $enableExceptions = null)
    {
        parent::__construct($enableExceptions);

        $this->isSMTP(); // Defini tipo de Autenticação
        $this->SMTPDebug = 0; // Habilita verbose debug output | 0 = off (for production use) | 1 = client messages | 2 = client and server messages
        $this->SMTPAuth = true; // Habilita SMTP authentication
        $this->SMTPSecure = 'tls'; // Habilita criptografia TLS, `ssl` também aceito
        $this->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ),
        );

        $this->Host = $configs['host']; // URL SMTP Server
        $this->Username = $configs['username']; // SMTP username
        $this->Password = $configs['password']; // SMTP password

        $this->Port = 587; // TCP port

        //Recipients
        $this->setFrom($configs['username'], $configs['remetente']);
        $this->addAddress('folhaverdeatacado@gmail.com', 'Lionardo');
        $this->addAddress('lfprioverde@hotmail.com', 'Lionardo');

    }

    public function notificar($titulo, $mensagem)
    {
        try {
            $this->isHTML(true);
            $this->CharSet = "UTF-8";
            $this->Subject = $titulo;
            $this->Body = $mensagem;
            $this->AltBody = $mensagem;

            $this->send();
        } catch (Exception $e) {
            $this->_log->error('Ocorreu um erro na classe Mailer.', [
                'exception' => $e->getMessage(),
            ]);
        }

    }
}
