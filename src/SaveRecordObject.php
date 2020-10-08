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
    private $foreignColumnsList = [];

    /**
     * SaveRecordObject constructor.
     *
     * @param string $table
     * @param array $data
     * @param array $primaryColumns
     * @param $foreignColumnsList
     */
    public function __construct($table, array $data, array $primaryColumns, array $foreignColumnsList)
    {

        $this->table = $table;
        $this->data = $data;
        $this->primaryColumns = $primaryColumns;
        $this->foreignColumnsList = $foreignColumnsList;
        /** @var \ParisEngineers\DeepRecordCopy\PrimaryColumn $primaryColumn */
        foreach ($primaryColumns as $primaryColumn) {
            $this->primaryColumnsList[] = $primaryColumn->getField();
        }
    }

    /**
     * Is a accessor for private property with name is primaryColumnsList and returned value which is currently set.
     * Value for this property is set by constructor and type of it must be array type
     * @return array
     * @author Mateusz Bochen
     */
    public function getPrimaryColumnsList()
    {
        return $this->primaryColumnsList;
    }

    /**
     * Is a accessor for private property with name is foreignColumnsList and returned value which is currently set.
     * Value for this property is set by constructor and type of it must be array type
     * @return ForeignKey[]
     * @author Mateusz Bochen
     */
    public function getForeignColumnsList()
    {
        return $this->foreignColumnsList;
    }

    public function setForeignColumnsList($foreignColumnsList)
    {
        return $this->foreignColumnsList = $foreignColumnsList;
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

    public function getInsertColumns() {
        $array = [];
        $keys = array_keys($this->data);
        foreach($keys as $key) {
            $array[] = '`' . $key . '`';
        }

        return implode(', ', $array);
    }

    public function getInsertValues() {
        $array = [];
        $keys = array_keys($this->data);
        foreach($keys as $key) {
            $array[] = ':' . $key . '';
        }

        return implode(', ', $array);
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
