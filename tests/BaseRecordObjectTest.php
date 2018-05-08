<?php

use ParisEngineers\DeepRecordCopy\ForeignKey;

/**
 * Created by PhpStorm.
 * User: backen
 * Date: 27.04.18
 * Time: 08:49
 */

class BaseRecordObjectTest extends PHPUnit_Framework_TestCase
{
    public function testGetterAndSetter()
    {
        $foreignKey = new ForeignKey();

        $foreignKey->REFERENCED_COLUMN_NAME = 10;

        $this->assertEquals(10, $foreignKey->getReferencedColumnName());

    }

    public function testGetNmae()
    {
        $this->assertEquals('ParisEngineers\DeepRecordCopy\ForeignKey', ForeignKey::getClassName());
    }
}
