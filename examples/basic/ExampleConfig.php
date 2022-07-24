<?php

class ExampleConfig extends Krag\Config
{

    public function __construct(array $defaultSettings = [], string $configFile = 'config.php')
    {
        parent::__construct($defaultSettings, $configFile);
    }

    public string $dbUsername;
    public string $dbPassword;
    public string $dsn;

}

?>
