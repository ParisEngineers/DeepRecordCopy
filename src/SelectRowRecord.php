<?php

namespace ParisEngineers\DeepRecordCopy;

class SelectRowRecord
{
    /** @var string */
    private $tableFrom;

    /** @var string */
    private $tableColumn;

    /** @var mixed */
    private $whereValue;

    /**
     * SelectRowRecord constructor.
     * @param string $tableFrom
     * @param string $tableColumn
     * @param mixed $whereValue
     */
    public function __construct($tableFrom, $tableColumn, $whereValue)
    {
        $this->tableFrom = $tableFrom;
        $this->tableColumn = $tableColumn;
        $this->whereValue = $whereValue;
    }

    /**
     * Is a accessor for private property with name is tableFrom and returned value which is currently set.
     * Value for this property is set by constructor and type of it must be string type
     * @return string
     * @author Mateusz Bochen
     */
    public function getTableFrom()
    {
        return $this->tableFrom;
    }

    /**
     * Is a accessor for private property with name is tableColumn and returned value which is currently set.
     * Value for this property is set by constructor and type of it must be string type
     * @return string
     * @author Mateusz Bochen
     */
    public function getTableColumn()
    {
        return $this->tableColumn;
    }

    /**
     * Is a accessor for private property with name is whereValue and returned value which is currently set.
     * Value for this property is set by constructor and type of it must be mixed type
     * @return mixed
     * @author Mateusz Bochen
     */
    public function getWhereValue()
    {
        return $this->whereValue;
    }
}
