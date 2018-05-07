<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 25.04.18
 * Time: 09:55
 */

namespace ParisEngineers\DeepRecordCopy;


class ForeignKey extends BaseRecordObject
{
    /**
     * @var string
     */
    private $referencedColumnName;

    /**
     * @var string
     */
    private $referencedTableName;


    /**
     * @var string
     */
    private $columnName;


    /**
     * @var string
     */
    private $tableName;

    /**
     * @return string
     */
    public function getReferencedColumnName()
    {
        return $this->referencedColumnName;
    }

    /**
     * @param string $referencedColumnName
     *
     * @return ForeignKey
     */
    public function setReferencedColumnName($referencedColumnName)
    {
        $this->referencedColumnName = $referencedColumnName;
        return $this;
    }

    /**
     * @return string
     */
    public function getReferencedTableName()
    {
        return $this->referencedTableName;
    }

    /**
     * @param string $referencedTableName
     *
     * @return ForeignKey
     */
    public function setReferencedTableName($referencedTableName)
    {
        $this->referencedTableName = $referencedTableName;
        return $this;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     *
     * @return ForeignKey
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     *
     * @return ForeignKey
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }


}
