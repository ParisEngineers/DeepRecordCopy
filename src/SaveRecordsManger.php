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


    /**
     * @param $saveRecord
     * @param bool $isForeign
     *
     * @return bool
     */
    public function add(SaveRecordObject $saveRecord, $isForeign)
    {
        $iNeedToAdd = true;
        $key = $saveRecord->getKey();

        if ($isForeign && isset($this->collectionExist[$key])) {
            if ($this->collection[0]->getKey() !== $key) {
                $this->removeFromCollection($key);
                Logger::log("Klucz istnieje, ale nie jest na poczatku tablicy usuwamy  {$key} \n");
            } else {
               $iNeedToAdd = false;
                Logger::log("Klucz istnieje, ale jest na poczatku tablicy {$key} \n");
            }
        }

        if ($isForeign && $iNeedToAdd) {
            Logger::log("Dodaje do zapisania na poczatek tablicy {$key} \n");
            array_unshift($this->collection, $saveRecord);
        } else {
            if (!isset($this->collectionExist[$key])) {
                Logger::log("Dodaje do zapisania na Koniec tablicy {$key} \n");
                $this->collection[] = $saveRecord;
            }
        }

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


    private function removeFromCollection($key)
    {
        foreach ($this->collection as $storeKey => $storeItem) {
            if ($storeItem->getKey() === $key) {
                unset($this->collection[$storeKey]);
            }
        }
    }
}
