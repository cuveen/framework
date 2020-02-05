<?php
namespace Cuveen\Mail;

use Cuveen\View\View;

class Mailable {
    public $body = '';
    public $from = [];
    public $data = [];
    public $attach = [];
    public $view = '';
    public $subject = '';

    public function body($body)
    {
        $this->body = $body;
        return $this;
    }

    public function with($data = [])
    {
        $this->data = $data;
        return $this;
    }

    public function template($file)
    {
        $this->view = $file;
        return $this;
    }

    public function subject($text)
    {
        $this->subject = $text;
        return $this;
    }

    public function attach($file, $name = null)
    {
        $this->attach = [$file, $name];
        return $this;
    }

    public function from($email, $name = null)
    {
        $this->from = [$email, $name];
        return $this;
    }
}