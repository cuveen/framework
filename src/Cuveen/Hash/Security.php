<?php


namespace Cuveen\Hash;


class Security
{
    //default time
    private $time;
    //System time
    private $sysTime;
    protected static $_instance;
    /**
     * __construct.
     *
     *
     * @return Void;
     */
    public function __construct()
    {
        //delete expires tokens
        self::DeleteExpires();
        //update system time
        self::UpdateSysTime();
        //session handler
        self::GenerateSession();
        self::$_instance = $this;
    }


    public static function getInstance()
    {
        return self::$_instance;
    }
    /**
     * Delete token with $keye.
     *
     * @key = $key token tobe deleted
     *
     * @return void;
     */
    public function Delete($token)
    {
        if (isset($_SESSION['cuveen_framework_security']['csrf'][$token])) {
            unset($_SESSION['cuveen_framework_security']['csrf'][$token]);
        }
    }
    /**
     * Delete expire tokens.
     *
     *
     * @return void;
     */
    public function DeleteExpires()
    {
        if (isset($_SESSION['cuveen_framework_security']['csrf'])) {
            foreach ($_SESSION['cuveen_framework_security']['csrf'] as $token => $value) {
                if (time() >= $value) {
                    unset($_SESSION['cuveen_framework_security']['csrf'][$token]);
                }
            }
        }
    }
    /**
     * Delete unnecessary tokens.
     *
     *
     * @return void;
     */
    public function DeleteUnnecessaryTokens()
    {
        $total = self::CountsTokens();
        $delete = $total - 1;
        $tokens_deleted = $_SESSION['cuveen_framework_security']['csrf'];
        $tokens = array_slice($tokens_deleted, 0, $delete);
        foreach ($tokens as $token => $time) {
            self::Delete($token);
        }
    }
    /**
     * Debug
     *	return all tokens.
     *
     * @return json object;
     */
    public function Debug()
    {
        echo json_encode($_SESSION['cuveen_framework_security']['csrf'], JSON_PRETTY_PRINT);
    }
    /**
     * Update time.
     *
     * @time = $time tobe updated
     *
     * @return bolean;
     */
    public function UpdateTime($time)
    {
        if (is_int($time) && is_numeric($time)) {
            $this->time = $time;
            return $this->time;
        } else {
            return false;
        }
    }
    /**
     * Update system time.
     *
     * @return void;
     */
    final private function UpdateSysTime()
    {
        $this->sysTime = time();
    }
    /**
     * generate salts for files.
     *
     * @param string $length length of salts
     *
     * @return string;
     */
    public function GenerateSalts($length)
    {
        $chars = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        $stringlength = count($chars);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $chars[rand(0, $stringlength - 1)];
        }
        return $randomString;
    }
    /**
     * Generate tokens.
     *
     *@param
     *$time => $time
     *$multiplier => 3*3600
     *
     * @return mix-data;
     */
    public function GenerateTokens($time, $multiplier)
    {
        $key = self::GenerateSalts(100);
        $utime = self::UpdateTime($time);
        $value = $this->sysTime + ($utime * $multiplier);
        $_SESSION['cuveen_framework_security']['csrf'][$key] = $value;
        return $key;
    }
    /**
     * Generate empty session.
     *
     * @return void;
     */
    public function GenerateSession()
    {
        if (!isset($_SESSION['cuveen_framework_security']['csrf'])) {
            $_SESSION['cuveen_framework_security']['csrf'] = [];
        }
    }
    /**
     * View token.
     *
     * @token = $key
     *
     * @return mix-data;
     */
    public function View($token)
    {
        if (isset($_SESSION['cuveen_framework_security']['csrf'][$token])) {
            return $_SESSION['cuveen_framework_security']['csrf'][$token];
        } else {
            return false;
        }
    }
    /**
     * Verify token exists or not.
     *
     * @token = $key
     *
     * @return boolean;
     */
    public function Verify($token)
    {
        if (isset($_SESSION['cuveen_framework_security']['csrf'][$token])) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Last token.
     *
     * @return mix-data;
     */
    public function LastToken()
    {
        if (isset($_SESSION['cuveen_framework_security']['csrf'])) {
            return end($_SESSION['cuveen_framework_security']['csrf']);
        } else {
            return false;
        }
    }
    /**
     * Count tokens.
     *
     * @return int;
     */
    public function CountsTokens()
    {
        if (isset($_SESSION['cuveen_framework_security']['csrf'])) {
            return count($_SESSION['cuveen_framework_security']['csrf']);
        } else {
            return 0;
        }
    }
}