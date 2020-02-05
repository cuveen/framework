<?php


namespace Cuveen\Hash;


class Hash
{
    public function __construct()
    {

    }

    public static function make($password)
    {
        return password_hash($password);
    }

    public static function check($password, $hash)
    {
        return password_verify($password, $hash);
    }
}