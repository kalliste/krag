<?php

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

?>
