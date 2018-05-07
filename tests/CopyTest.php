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
        $var = new \ParisEngineers\DeepRecordCopy\Copy();

        $var->setFrom('localhost', 'db1', 'root', '');
        $var->setTo('localhost', 'db2', 'root', '');
        $var->copy('table', 'id', 1);
    }
}
