<?php

namespace Krag;

class Config implements \IteratorAggregate
{

    public function __construct(
        public array $settings = [],
    ) {
        if (file_exists('config.php'))
        {
            include('config.php');
            $fileSettings = get_defined_vars();
            unset($fileSettings['settings']);
            $this->settings = array_merge($settings, $fileSettings);
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->settings))
        {
            return $this->settings[$name];
        }
        return null;
    }

    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->settings);
    }

}

?>
