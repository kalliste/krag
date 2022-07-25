<?php

namespace Krag;

class Config extends \stdClass
{
    /**
     * @param array<string, mixed> $defaultSettings
     */
    public function __construct(array $defaultSettings = [], private string $configFile = 'config.php')
    {
        $fileSettings = (file_exists($configFile)) ? $this->settingsFromConfigFile() : [];
        foreach (array_merge($defaultSettings, $fileSettings) as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsFromConfigFile(): array
    {
        require($this->configFile);
        return get_defined_vars();
    }
}
