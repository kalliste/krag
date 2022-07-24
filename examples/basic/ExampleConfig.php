<?php

class ExampleConfig extends Krag\Config
{

    public string $dbUsername;
    public string $dbPassword;
    public string $dsn;

    public function __construct(array $defaultSettings = [], string $configFile = 'config.php')
    {
        parent::__construct($defaultSettings, $configFile);
    }

}

?>
