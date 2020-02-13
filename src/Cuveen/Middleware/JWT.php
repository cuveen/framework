<?php


namespace Cuveen\Middleware;


use Cuveen\Jwt\Encode;
use Cuveen\Jwt\Parse;
use Cuveen\Jwt\Validate;

class JWT
{
    public function handle()
    {
        $token = request()->getAuthorization();
        $secret = !empty(config('jwt.secret'))?config('jwt.secret'):'sec!ReT423*&';
        $jwt = new \Cuveen\Jwt\Jwt($token, $secret);
        $parse = new Parse($jwt, new Validate(), new Encode());
        try{
            $parsed = $parse->validate()
                ->validateExpiration()
                ->parse();
            $payload = $parsed->getPayload();
            session()->put('__CUVEEN_USER_LOGGED_IN', $payload['user_id']);
        }
        catch (\Exception $exception){

        }

        return true;
     }
}