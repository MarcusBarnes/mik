<?php

namespace mik\exceptions;

/**
 * Custom MIK Exception class.
 */
class MikException extends \Exception
{


    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        
        $this->message("MikException class constructed");
        parent::__construct($message, $code, $previous);
    }

    public function message($message = "")
    {
        echo $message;
    }
}
