<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 25.04.18
 * Time: 11:15
 */

namespace ParisEngineers\DeepRecordCopy;


class PrimaryColumn extends BaseRecordObject
{

    const PRIMARY_KEY = 'PRI';

    /**
     * @var string
    */
    private $field;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $null;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $default;

    /**
     * @var string
     */
    private $extra;

    /**
     * PrimaryColumn constructor.
     *
     * @param string $field
     * @param string $type
     * @param string $null
     * @param string $key
     * @param string $default
     * @param string $extra
     */
    public function __construct($field = null, $type = null, $null = null, $key = null, $default = null, $extra = null)
    {
        $this->field = $field;
        $this->type = $type;
        $this->null = $null;
        $this->key = $key;
        $this->default = $default;
        $this->extra = $extra;
    }


    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     *
     * @return PrimaryColumn
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return PrimaryColumn
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getNull()
    {
        return $this->null;
    }

    /**
     * @param string $key
     *
     * @return PrimaryColumn
     */
    public function setNull($null)
    {
        $this->null = $null;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return PrimaryColumn
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param string $default
     *
     * @return PrimaryColumn
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param string $extra
     *
     * @return PrimaryColumn
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
        return $this;
    }

}