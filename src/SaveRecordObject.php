<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 25.04.18
 * Time: 14:50
 */

namespace ParisEngineers\DeepRecordCopy;


class SaveRecordObject
{
    /**
     * @var string
     */
    private $table;

    /**
     * table columns as key=>value
     * @var array
    */
    private $data = [];

    private $primaryColumns = [];
    private $primaryColumnsList = [];


    /**
     * SaveRecordObject constructor.
     *
     * @param string $table
     * @param array  $data
     * @param array  $primaryColumns
     */
    public function __construct($table = null, array $data = null, $primaryColumns = array())
    {

        $this->table = $table;
        $this->data = $data;
        $this->primaryColumns = $primaryColumns;
        /** @var \ParisEngineers\DeepRecordCopy\PrimaryColumn $primaryColumn */
        foreach ($primaryColumns as $primaryColumn) {
            $this->primaryColumnsList[] = $primaryColumn->getField();
        }
    }


    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     *
     * @return SaveRecordObject
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param bool $ignorePrimeKeys
     *
     * @return array
     */
    public function getData($ignorePrimeKeys = false)
    {
        if ($ignorePrimeKeys) {
            $array = [];
            foreach($this->data as $key => $value) {
                if (in_array($key, $this->primaryColumnsList)) {
                    continue;
                } else {
                    $array[$key] = $value;
                }
            }
            return $array;
        }

        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return SaveRecordObject
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        $key = [];
        /** @var \ParisEngineers\DeepRecordCopy\PrimaryColumn $primaryColumn */
        foreach ($this->primaryColumns as $primaryColumn) {
            $field = $primaryColumn->getField();
            $key[] = $field.'-'.$this->data[$field];
        }

        return $this->table.'-'.implode('-', $key);
    }

    /**
     * @return string
     */
    public function getWhere()
    {
        $whereArray = [];
        /** @var \ParisEngineers\DeepRecordCopy\PrimaryColumn $primaryColumn */
        foreach ($this->primaryColumns as $primaryColumn) {
            $field = $primaryColumn->getField();
            $whereArray[] = '`'.$field.'` = \''.$this->data[$field].'\'';
        }

        return implode(' AND ', $whereArray);
    }


    public function  getSet($ignorePrimeKeys = false) {
        $array = [];
        $keys = array_keys($this->data);
        foreach($keys as $key) {
            if ($ignorePrimeKeys) {
                if (in_array($key, $this->primaryColumnsList)) {
                    continue;
                } else {
                    $array[] = '`' . $key . '` = :' . $key;
                }
            } else {
                $array[] = '`' . $key . '` = :' . $key;
            }
        }

        return implode(', ', $array);
    }

    /**
     * @return array
     */
    public function getPrimaryColumns()
    {
        return $this->primaryColumns;
    }
}
