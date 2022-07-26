<?php

require_once(dirname(__FILE__).'/../Environment.php');

$k = getInjection();
$k->call($k->get('Krag\App')->run(...));
