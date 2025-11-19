<?php

namespace Drupal\dmf_core\Log;

use DigitalMarketingFramework\Core\Log\LoggerFactoryInterface;
use DigitalMarketingFramework\Core\Log\LoggerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class LoggerFactory implements LoggerFactoryInterface
{
    public function __construct(
        protected LoggerChannelFactoryInterface $loggerChannelFactory,
    ) {
    }

    public function getLogger(string $forClass): LoggerInterface
    {
        // Create a logger channel for the specific class
        // Drupal's LoggerChannelFactory creates PSR-3 loggers
        $psrLogger = $this->loggerChannelFactory->get($forClass);

        return new Logger($psrLogger);
    }
}