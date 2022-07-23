<?php

use Krag\Injection;

$k = new Injection(singletons: ['Krag\Config', 'PDO']);
$config = $k->make('Config', ['configFile' => 'config.example.php'])
$k->make('PDO', ['dsn' => $config->dsn, 'username' => $config->dbUsername, 'password' => $config->dbPassword]);
Model::setInjection($k);

$k->make('App')->run();

?>
