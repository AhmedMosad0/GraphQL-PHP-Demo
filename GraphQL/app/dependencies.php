<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        \Doctrine\DBAL\Connection::class => function (ContainerInterface $c) {
            $settings = $c->get('settings');
        
            $connectionParams = [
                'dbname'   => $settings['db']['name'],
                'user'     => $settings['db']['username'],
                'password' => $settings['db']['password'],
                'host'     => $settings['db']['host'],
                'driver'   => $settings['db']['driver'],
            ];
        
            $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        
            # Set up SQL logging
            $connection->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\DebugStack());
        
            return $connection;
        }
    ]);
};
