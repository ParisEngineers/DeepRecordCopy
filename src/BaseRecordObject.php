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
