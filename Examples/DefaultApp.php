<?php

use Krag\Injection;

$k = new Injection(['Krag\Config', 'Krag\DB']);
$k->make('DB', $k->make('Config'))->make('App')->run();

?>
