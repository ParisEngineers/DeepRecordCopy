<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 25.04.18
 * Time: 10:10
 */

namespace ParisEngineers\DeepRecordCopy;


abstract class BaseRecordObject
{
    /**
     * For Keep Compatibility With PHP 5.4
     * in php5.4 cant use ClasName::class
    */
    public static function getClassName()
    {
        return get_called_class();
    }


    public function __set($propName, $propValue)
    {
        $prop = $this->toCamelCase($propName);

        call_user_func([$this, 'set'.$prop], $propValue);
    }

    protected function toSnakeCase($string) {
        $str = strtolower($string);
        $str = preg_replace('/\s+/', '_', $str);
        return $str;
    }

    protected function toCamelCase($string)
    {
        $string = strtolower($string);
        $str = str_replace('_', '', ucwords($string, '_'));
        return $str;
    }
}
