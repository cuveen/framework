<?php


namespace Cuveen\Mail;
use Cuveen\Config\Config;
use Cuveen\Validator\Validator;
use Cuveen\View\View;

class Mail
{
    protected static $to = '';
    protected $name = '';
    protected $body = '';
    protected static $_instance;
    protected $config;
    public $mailer;
    public $default_email;
    public $default_name;
    public $message;
    protected $data;

    public static function getInstance()
    {
        return self::$_instance;
    }

    public function __construct($attr = [])
    {
        $this->config = Config::getInstance();
        $this->getAll($this->config->get('base_path'));
        if(!empty($this->config->get('mail.driver')) && $this->config->get('mail.driver') == 'smtp'){
            $transport = new \Swift_SmtpTransport();
            $transport->setHost($this->config->get('mail.host'))
                ->setPort($this->config->get('mail.port'))
                ->setUsername($this->config->get('mail.username'))
                ->setPassword($this->config->get('mail.password'))
                ->setEncryption($this->config->get('mail.encryption'));
        }
        else{
            $transport = new \Swift_SendmailTransport();
        }
        $this->mailer = new \Swift_Mailer($transport);
        $this->message = new \Swift_Message();
        $this->data = $attr;
        $mail_config = $this->config->get('mail');
        if(is_array($mail_config) && isset($mail_config['from']) && is_array($mail_config['from'])){
            $this->default_email = @$mail_config['from']['address'];
            $this->default_name = @$mail_config['from']['name'];
        }
        $this->from($this->default_email, $this->default_name);
    }

    private function getAll($base_path)
    {
        if ($handle = opendir($base_path . '/mail/')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                    include($base_path.'/mail/'.$entry);
                }
            }
            closedir($handle);
        }
    }

    public static function to($email = '')
    {
        self::$to = $email;
        return new static();
    }


    public function from($email, $name = false)
    {
        if(!$name){
            $name = $this->default_name;
        }
        $this->message->setFrom($email, $name);
        return $this;
    }

    public function subject($subject = ''){
        $this->message->setSubject($subject);
        return $this;
    }

    public function template($file, $data = array())
    {
        $view = View::getInstance();
        $this->body = $view->render($file,$data);
        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function attach($file, $name = false)
    {
        if(!$name){
            $name = basename($file);
        }
        $this->message->attach(\Swift_Attachment::fromPath($file)->setFilename($name));
        return $this;
    }

    public function send($emailClass)
    {
        $mailable = $emailClass->build();
        $this->message->setTo(self::$to);
        if(isset($mailable->data) && !empty($mailable->data)){
            if(is_string($mailable->data)){
                $this->data = ['data' => $mailable->data];
            }
            elseif(is_array($mailable->data)){
                $this->data = $mailable->data;
            }
        }
        if(isset($mailable->body)){
            $this->body = $mailable->body;
        }
        if(isset($mailable->from)){
            if(is_array($mailable->from) && count($mailable->from) == 2){
                $this->from($mailable->from[0], $mailable->from[1]);
            }
            elseif(is_string($mailable->from) && filter_var($mailable->from, FILTER_VALIDATE_EMAIL)){
                $this->from($mailable->from);
            }
        }
        if(isset($mailable->attach) && !empty($mailable->attach)){
            if(is_array($mailable->attach) && count($mailable->attach) == 2){
                $this->attach($mailable->attach[0], @$mailable->attach[1]);
            }
            else{
                $this->attach($mailable->attach);
            }
        }
        if(isset($mailable->view) && $mailable->view != ''){
            $this->template($mailable->view, $this->data);
        }
        if(isset($mailable->subject)){
            $this->subject($mailable->subject);
        }
        return $this->realSend();
    }

    public function realSend()
    {
        $this->message->setContentType('text/html');
        $this->message->setBody($this->body);
        return $this->mailer->send($this->message, $failures);
    }


}