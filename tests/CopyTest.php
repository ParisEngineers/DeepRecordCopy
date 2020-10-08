<?php 

/**
*  Corresponding Class to test YourClass class
*
*  For each class in your library, there should be a corresponding Unit-Test for it
*  Unit-Tests should be as much as possible independent from other test going on.
*
*  @author Mateusz Bochen
*/
class CopyTest extends PHPUnit_Framework_TestCase {

    public function testIsThereAnySyntaxError(){
        $var = new \ParisEngineers\DeepRecordCopy\Copy();
	    $this->assertTrue(is_object($var));
	    unset($var);
    }

    public function testCopy()
    {

        define('DEEP_RECORD_COPY_DEBUG_LVL', 1);

        //ini_set('xdebug.max_nesting_level', 10000);

        $var = new \ParisEngineers\DeepRecordCopy\Copy();

        $var->setFrom(' ', ' ', ' ', ' ');
        $var->setTo('localhost', 'sss', ' ', ' ');
        $var->copy('dfsdfs', ' sdfsdfs', 233535);
    }
}
