<?php

namespace mik\exceptions;

//use \Monolog\Logger;

/**
 * Custom MIK Exception class.
 */
class MikErrorException extends \ErrorException
{

    public function __construct($message = null, $code = 0, $severity, $file, $line)
    {
        
        //$this->message("MikErrorException class constructed" . PHP_EOL);
        //$previous = Exception::getPrevious;
        parent::__construct($message, $code, $previous = NULL);
        
    }

    public function message($message = "")
    {
        echo $message;
        return;
    }
}
