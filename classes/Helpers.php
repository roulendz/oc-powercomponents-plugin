<?php namespace Initbiz\PowerComponents\Classes;

use Cms\Classes\Page as CmsPage;

class Helpers
{
    public static function getFileListToDropdown()
    {
        return CmsPage::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * Internal helper for method existence checks.
     * The method originally is in modules/backend/widgets/Form.php, but I consider it useful in helpers
     *
     * @param  object $object
     * @param  string $method
     * @return boolean
     */
    public static function objectMethodExists($object, $method)
    {
        if (method_exists($object, 'methodExists')) {
            return $object->methodExists($method);
        }

        return method_exists($object, $method);
    }

    /**
     * Variant to array_get() but preserves dots in key names.
     * The method originally is in modules/backend/widgets/Form.php, but I consider it useful in helpers
     *
     * @param array $array
     * @param array $parts
     * @param null $default
     * @return array|null
     */
    public static function dataArrayGet(array $array, array $parts, $default = null)
    {
        if ($parts === null) {
            return $array;
        }

        if (count($parts) === 1) {
            $key = array_shift($parts);
            if (isset($array[$key])) {
                return $array[$key];
            } else {
                return $default;
            }
        }

        foreach ($parts as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Variant to array_set() but preserves dots in key names.
     * The method originally is in modules/backend/widgets/Form.php, but I consider it useful in helpers
     *
     * @param array $array
     * @param array $parts
     * @param string $value
     * @return array
     */
    public static function dataArraySet(array &$array, array $parts, $value)
    {
        if ($parts === null) {
            return $value;
        }

        while (count($parts) > 1) {
            $key = array_shift($parts);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array =& $array[$key];
        }

        $array[array_shift($parts)] = $value;

        return $array;
    }
}
