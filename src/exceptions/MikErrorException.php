<?php

namespace mik\exceptions;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Custom MIK Exception class.
 */
class MikErrorException extends \ErrorException
{

    public function __construct($settings, $severity, $file, $line, $message = null, $code = 0)
    {
        $pathToLog = $settings['LOGGING']['path_to_log'];
        $log = new Logger('ErrorException');
        $logStreamHandler= new StreamHandler($pathToLog, Logger::ERROR);
        $log->pushHandler($logStreamHandler);
        $log->addError(
            'ErrorException',
            array(
              'message' => $message,
              'code' => $code,
              'severity' => $severity,
              'file' => $file,
              'line' => $line
            )
        );

        //$this->message("MikErrorException class constructed" . PHP_EOL);
        //$previous = Exception::getPrevious;
        parent::__construct($message, $code, $previous = null);
        
    }

    public function message($message = "")
    {
        echo $message;
        return;
    }
}
