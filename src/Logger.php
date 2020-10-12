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

            if (static::saveLogToFile()) {
                static::addLogToFile($valueString, $textColor, $bgColor);
            } else {
                if ($textColor || $bgColor) {
                    $colors = new Colors();
                    $valueString = $colors->getColoredString($valueString, $textColor, $bgColor);
                }
                fwrite(STDERR, $valueString);
            }
        }
    }

    private static function addLogToFile($stringValue, $textColor, $bgColor)
    {
        if (static::logRecordAsHtml()) {
            $style = '';
            if ($textColor) {
                $style .= 'color: '.$textColor.';';
            }

            if ($bgColor) {
                $style .= 'background: '.$bgColor.';';
            }
            $stringValue = '<div style="'.$style.'">'.$stringValue.'</div>';
        }

        $path = static::logFilePath();
        if ($path) {
            file_put_contents($path, $stringValue, FILE_APPEND);
        }
    }

    private static function saveLogToFile()
    {
        return defined('DEEP_RECORD_COPY_SAVE_LOG_TO_FILE') && DEEP_RECORD_COPY_SAVE_LOG_TO_FILE === true;
    }


    private static function logFilePath()
    {
        if (defined('DEEP_RECORD_COPY_LOG_FILE_PATH')) {
            return DEEP_RECORD_COPY_LOG_FILE_PATH;
        }
    }

    private static function logRecordAsHtml()
    {
        return defined('DEEP_RECORD_COPY_LOG_RECORD_AS_HTML') && DEEP_RECORD_COPY_LOG_RECORD_AS_HTML === true;
    }
}
