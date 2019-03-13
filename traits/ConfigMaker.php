<?php namespace Initbiz\PowerComponents\Traits;

use Yaml;
use File;
use October\Rain\Html\Helper as HtmlHelper;

/**
 * Config Maker Trait
 * Adds configuration based methods to a component class
 */
trait ConfigMaker
{
    use \System\Traits\ConfigMaker;

    protected $configPath;
    protected $configFileName;


    /**
     * Transfers config values stored inside the $config property directly
     * on to the root object properties. If no properties are defined
     * all config will be transferred if it finds a matching property.
     * Copied from WidgetBase
     * @param array $properties
     * @return void
     */
    protected function fillFromConfig($properties = null)
    {
        if ($properties === null) {
            $properties = array_keys((array) $this->config);
        }

        foreach ($properties as $property) {
            if (property_exists($this, $property)) {
                $this->{$property} = $this->getConfig($property, $this->{$property});
            }
        }
        // if ($properties === null) {
        //     $properties = array_keys((array)$this->config);
        // }
        //
        // foreach ($properties as $property) {
        //     if (property_exists($this, $property) && isset($this->config->$property)) {
        //         $this->{$property} = $this->config->$property;
        //         if ($property === 'model') {
        //             $this->model = new $this->config->model;
        //         }
        //     }
        // }
    }

    /**
     * Safe accessor for configuration values. Copied from WidgetBase
     * @param string $name Config name, supports array names like "field[key]"
     * @param mixed $default Default value if nothing is found
     * @return string
     */
    public function getConfig($name = null, $default = null)
    {
        /*
         * Return all config
         */
        if (is_null($name)) {
            return $this->config;
        }

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        $keyParts = HtmlHelper::nameToArray($name);

        /*
         * First part will be the field name, pop it off
         */
        $fieldName = array_shift($keyParts);
        if (!isset($this->config->{$fieldName})) {
            return $default;
        }

        $result = $this->config->{$fieldName};

        /*
         * Loop the remaining key parts and build a result
         */
        foreach ($keyParts as $key) {
            if (!is_array($result) || !array_key_exists($key, $result)) {
                return $default;
            }

            $result = $result[$key];
        }

        return $result;
    }
}
