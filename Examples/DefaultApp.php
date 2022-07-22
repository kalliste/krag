<?php

use Krag\Injection;

$k = new Injection(singletons: ['Krag\Config', 'PDO']);
$k->make('PDO', $k->make('Config'));
$k->make('App')->run();

?>
