<?php

use ParisEngineers\DeepRecordCopy\PrimaryColumn;
use ParisEngineers\DeepRecordCopy\SaveRecordObject;

/**
 * Created by PhpStorm.
 * User: backen
 * Date: 27.04.18
 * Time: 08:32
 */

class SaveRecordObjectTest extends PHPUnit_Framework_TestCase {

    public function testIsThereAnySyntaxError(){
        $var = new \ParisEngineers\DeepRecordCopy\SaveRecordObject();
        $this->assertTrue(is_object($var));
        unset($var);
    }

    public function testWhere()
    {
        $collection = [];
        $collection[] = new PrimaryColumn('id', 'int', false, PrimaryColumn::PRIMARY_KEY);
        $collection[] = new PrimaryColumn('id2', 'int', false, PrimaryColumn::PRIMARY_KEY);

        $data = $this->sampleData();

        $saveRecordObject = new SaveRecordObject('table', $data, $collection);

        $this->assertEquals('`id` = \'10\' AND `id2` = \'11\'', $saveRecordObject->getWhere());
        $this->assertEquals('`id` = :id, `id2` = :id2, `text` = :text, `text2` = :text2', $saveRecordObject->getSet());
        $this->assertEquals('`text` = :text, `text2` = :text2', $saveRecordObject->getSet(true));
        $this->assertEquals($data, $saveRecordObject->getData());


        unset($data['id']);
        unset($data['id2']);

        $this->assertEquals($data, $saveRecordObject->getData(true));

    }


    private function sampleData()
    {
        return [
            'id' => 10,
            'id2' => 11,
            'text' => 'lorem',
            'text2' => 'impsum',
        ];

    }

}
