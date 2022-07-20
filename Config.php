<?php

namespace Krag;

class Config implements \IteratorAggregate
{

    private array $settings;

    public function __construct(array $defaultSettings = [], private string $configFile = 'config.php')
    {
        if (file_exists($configFile))
        {
            $fileSettings = $this->settingsFromConfigFile();
            $this->settings = array_merge($defaultSettings, $fileSettings);
        }
    }

    private function settingsFromConfigFile()
    {
        include($this->configFile);
        return get_defined_vars();
    }

    public function __get(string $name) : mixed
    {
        return (array_key_exists($name, $this->settings)) ? $this->settings[$name] : null;
    }

    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->settings);
    }

}

?>
