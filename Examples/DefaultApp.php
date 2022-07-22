<?php

use Krag\Injection;

$k = new Injection(['Krag\Config', 'Krag\DB']);
$k->setSingleton($k)->make('DB', $k->make('Config'));
$k->make('App')->run();

?>
