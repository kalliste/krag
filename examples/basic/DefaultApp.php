<?php

use HttpSoft\ServerRequest\ServerRequestCreator;
use Krag\{Injection, Config, App, Log};

require_once(dirname(__FILE__).'/ExampleConfig.php');

$k = new Injection(singletons: [ExampleConfig::class, DB::class], logger: new Log);
$k->setSingleton('Psr\Http\Message\ServerRequestInterface', ServerRequestCreator::create());
$config = $k->get(
    ExampleConfig::class,
    ['configFile' => dirname(__FILE__).'/config.example.php']
);
$k->get(DB::class, $config->databaseConfig());

$k->call($k->get('App')->run(...));

?>
