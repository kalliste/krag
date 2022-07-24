<?php

use HttpSoft\ServerRequest\ServerRequestCreator;
use Krag\{Injection, Config, App};

require_once(dirname(__FILE__).'/ExampleConfig.php');

$k = new Injection(singletons: [ExampleConfig::class, PDO::class]);
$config = $k->get(ExampleConfig::class, ['configFile' => dirname(__FILE__).'/config.example.php']);
$k->get(PDO::class, ['dsn' => $config->dsn, 'username' => $config->dbUsername, 'password' => $config->dbPassword]);

$request = ServerRequestCreator::create();
$k->get(App::class)->run($request);

?>
