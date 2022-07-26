<?php

use Analog\Analog;
use Analog\Handler\LevelBuffer;
use Analog\Handler\Stderr;
use Analog\Logger;
use HttpSoft\Message\{Stream, Response};
use HttpSoft\ServerRequest\ServerRequestCreator;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Message\StreamInterface;
use Krag\{Injection, Config, App, DB};

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

function getInjection(): Injection
{
    $logger = new Logger();
    $logger->handler(
        LevelBuffer::init(
            Stderr::init(),
            Analog::INFO
        )
    );
    $k = new Injection($logger);
    $k->setMapping(ServerRequestInterface::class, ServerRequestCreator::create());
    $k->setMapping(ResponseInterface::class, Response::class);
    $k->setMapping(StreamInterface::class, Stream::class);
    $config = $k->get(
        ExampleConfig::class,
        ['configFile' => dirname(__FILE__).'/config.example.php']
    );
    $k->get(DB::class, $config->databaseConfig());
    return $k;
}
