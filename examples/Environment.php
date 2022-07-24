<?php

use HttpSoft\ServerRequest\ServerRequestCreator;
use Krag\{Injection, Config, App, DB, Log};

require_once(dirname(__FILE__).'/../src/Structures.php');
require_once(dirname(__FILE__).'/../src/Interfaces.php');
require_once(dirname(__FILE__).'/../src/Config.php');
require_once(dirname(__FILE__).'/../src/Log.php');
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

    public function __construct(array $defaultSettings = [], string $configFile = 'config.php')
    {
        parent::__construct($defaultSettings, $configFile);
    }

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
    $k = new Injection(singletons: [ExampleConfig::class, DB::class], logger: new Log());
    $k->setSingleton('Psr\Http\Message\ServerRequestInterface', ServerRequestCreator::create());
    $config = $k->get(
        ExampleConfig::class,
        ['configFile' => dirname(__FILE__).'/config.example.php']
    );
    $k->get(DB::class, $config->databaseConfig());
    return $k;
}
