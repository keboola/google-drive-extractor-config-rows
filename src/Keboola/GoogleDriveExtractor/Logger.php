<?php

/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 10/12/15
 * Time: 12:45
 */

namespace Keboola\GoogleDriveExtractor;

use Keboola\GoogleDriveExtractor\Logger\LineFormatter;
use Monolog\Handler\StreamHandler;

class Logger extends \Monolog\Logger
{
    public function __construct($name = '', $debug = false)
    {
        $options = getopt("", ['debug']);
        if (isset($options['debug'])) {
            // Default format with all the info for dev debug
            $formatter = new LineFormatter();
            $debug = true;
        } elseif (!empty($debug)) {
            // Set user debug mode
            $formatter = new LineFormatter("%level_name%: %message% %context% %extra%\n");
        } else {
            // Simple message
            $formatter = new LineFormatter("%message%\n");
        }

        $errHandler = new StreamHandler('php://stderr', \Monolog\Logger::NOTICE, false);
        $level = $debug ? \Monolog\Logger::DEBUG : \Monolog\Logger::INFO;
        $handler = new StreamHandler('php://stdout', $level);
        $handler->setFormatter($formatter);

        parent::__construct($name, [$errHandler, $handler]);
    }
}
