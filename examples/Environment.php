<?php

use Analog\{Analog, Logger};
use Analog\Handler\{LevelBuffer, Stderr};
use HttpSoft\Message\{StreamFactory, ResponseFactory};
use HttpSoft\ServerRequest\ServerRequestCreator;
use Krag\{Injection, Config, App, DB};
use Psr\Http\Message\{ServerRequestInterface, ResponseFactoryInterface, StreamFactoryInterface};
use Psr\Log\LoggerInterface;

require_once(dirname(__FILE__).'/../src/Interfaces.php');
require_once(dirname(__FILE__).'/../src/Config.php');
require_once(dirname(__FILE__).'/../src/Injection.php');
require_once(dirname(__FILE__).'/../src/Routing.php');
require_once(dirname(__FILE__).'/../src/Result.php');
require_once(dirname(__FILE__).'/../src/Views.php');
require_once(dirname(__FILE__).'/../src/App.php');
require_once(dirname(__FILE__).'/../src/DB.php');
require_once(dirname(__FILE__).'/../src/HTTP.php');
require_once(dirname(__FILE__).'/../src/SQL.php');
require_once(dirname(__FILE__).'/../src/Model.php');

class ExampleConfig extends Krag\Config
{
    public string $dbType;
    public string $dbHost;
    public string $dbUsername;
    public string $dbPassword;

    /**
     * @param array<int|string, mixed> $defaultSettings
     */
    public function __construct(array $defaultSettings = [], string $configFile = 'config.php')
    {
        parent::__construct($defaultSettings, $configFile);
    }

    /**
     * @return array<string, string>
     */
    public function databaseConfig(): array
    {
        return [
            'type' => $this->dbType,
            'hostname' => $this->dbHost,
            'database' => $this->dbUsername,
            'password' => $this->dbPassword,
        ];
    }
}

function getInjection(?LoggerInterface $logger = null): Injection
{
    if (is_null($logger)) {
        $logger = new Logger();
        $logger->handler(
            LevelBuffer::init(
                Stderr::init(),
                Analog::DEBUG
            )
        );
    }

    $k = new Injection($logger);

    $config = $k->get(
        ExampleConfig::class,
        ['configFile' => dirname(__FILE__).'/config.example.php']
    );

    $k->setMapping(Krag\Config::class, $config);
    $k->setMapping(Krag\DB::class, $k->get(DB::class, $config->databaseConfig()));

    $k->setMapping(ServerRequestInterface::class, ServerRequestCreator::create());
    $k->setMapping(ResponseFactoryInterface::class, new ResponseFactory());
    $k->setMapping(StreamFactoryInterface::class, new StreamFactory());

    return $k;
}
