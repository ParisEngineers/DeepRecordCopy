<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 25.04.18
 * Time: 13:37
 */

namespace ParisEngineers\DeepRecordCopy;


class SaveRecordsManger
{
    private $collectionExist = [];
    private $collection = [];
    private $foreignCollection = [];


    /**
     * @param $saveRecord
     * @param bool $isForeign
     *
     * @return bool
     */
    public function add(SaveRecordObject $saveRecord)
    {
        $key = $saveRecord->getKey();

        if (isset($this->collectionExist[$key])) {
            Logger::log("Klucz istnieje {$key} \n");
            return true;
        }


        $this->collection[] = $saveRecord;
        $this->collectionExist[$key] = true;

        return true;
    }

    /**
     * @return array
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Is a accessor for private property with name is collectionExist and returned value which is currently set.
     * Value for this property is set by constructor and type of it must be array type
     * @return array
     * @author Mateusz Bochen
     */
    public function getCollectionExist()
    {
        return $this->collectionExist;
    }

    /**
     * @param array $collectionExist
     */
    public function setCollectionExist($collectionExist)
    {
        $this->collectionExist = $collectionExist;
    }

    /**
     * Is a accessor for private property with name is foreignCollection and returned value which is currently set.
     * Value for this property is set by constructor and type of it must be array type
     * @return array
     * @author Mateusz Bochen
     */
    public function getForeignCollection()
    {
        return $this->foreignCollection;
    }

    /**
     * @param array $foreignCollection
     */
    public function setForeignCollection($foreignCollection)
    {
        $this->foreignCollection = $foreignCollection;
    }
}
