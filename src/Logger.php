<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 26.04.18
 * Time: 11:24
 */

namespace ParisEngineers\DeepRecordCopy;

/**
 * set global const to display debug notifications
 * const name is DEEP_RECORD_COPY_DEBUG_LVL
 * 0 - no notifications
 * 1 - simple notifications
 * 2 and more most advanced info
 */
class Logger
{
    /**
     * @param      $value
     * @param null $textColor - text color in console
     * @param null $bgColor - text background color in console
     * @param int  $lvl - the bigger the number, the more accurate the debug
     */
    public static function log($value, $textColor = null, $bgColor = null, $lvl = 1)
    {
        if (defined('DEEP_RECORD_COPY_DEBUG_LVL') && DEEP_RECORD_COPY_DEBUG_LVL >= $lvl) {
            $valueString = print_r($value, true);
            if ($textColor || $bgColor) {
                $colors = new Colors();
                $valueString = $colors->getColoredString($valueString, $textColor, $bgColor);
            }
            fwrite(STDERR, $valueString);
        }
    }
}
