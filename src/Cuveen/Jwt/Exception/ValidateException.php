<?php

declare(strict_types=1);

namespace Cuveen\Jwt\Exception;

use Exception;
use Throwable;

/**
 * Simple PHP exception class for all validation exceptions so exceptions are
 * more specific and obvious.
 */
class ValidateException extends Exception
{
    /**
     * Constructor for the Validate Exception class
     *
     * @param string $message
     * @param int $code
     * @param Throwable $previous
     */
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        //parent::__construct($message, $code, $previous);
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error', 'msg' => $message]);
        die();
    }
}
