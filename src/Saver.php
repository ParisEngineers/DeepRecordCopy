<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 25.04.18
 * Time: 15:06
 */

namespace ParisEngineers\DeepRecordCopy;


use PDO;
use PDOException;

class Saver
{
    /**
     * @var array SaveRecordObject
     */
    private $collection = [];
    private $pdohTo;
    private $lastCount = 0;
    private $sameLastCountCount = 0;
    /** @var SaveRecordsManger */
    private $collectionManager;
    private $foreignInserted = [];

    public function __construct(DBUser $to)
    {
        $this->pdohTo = new PdoConnector($to);
    }

    /**
     * Is a accessor for private property with name is collectionManager and returned value which is currently set.
     * Value for this property is set by constructor and type of it must be mixed type
     * @return mixed
     * @author Mateusz Bochen
     */
    public function getCollectionManager()
    {
        return $this->collectionManager;
    }

    /**
     * @param mixed $collectionManager
     */
    public function setCollectionManager($collectionManager)
    {
        $this->collectionManager = $collectionManager;
    }



    public function setCollection(array $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return bool
     */
    public function save()
    {
        if (empty($this->collection)) {
            return true;
        }
        $i = 0;

        while (count($this->collection) !== 0) {

            $object = $this->collection[$i];
            $foreigners = $object->getForeignColumnsList();

            if (count($foreigners)) {
                $this->insertForeignValue($object);
            }


            $this->createInsertQuery($object);
            unset($this->collection[$i]);
            $i++;
        }
    }


    private function insertForeignValue(SaveRecordObject $object)
    {
        Logger::log("Adding Foreign Value: {$object->getTable()} {$object->getKey()}\n", 'yellow');

        $foreignersData = $this->collectionManager->getForeignCollection();
        $foreigners = $object->getForeignColumnsList();

        $data = $object->getData();

        foreach ($foreigners as $keyObjectKey => $foreign) {
            $value = $data[$foreign->getColumnName()];

            if ($value === null) {
                continue;
            }

            $key = $this->getSelectKey($foreign->getReferencedTableName(), $foreign->getReferencedColumnName(), $value);

            if (isset($this->foreignInserted[$key])) {
                Logger::log("Key OK {$key}\n", 'blue');
                continue;
            }


            if (isset($foreignersData[$key])) {
                $foreignData = $foreignersData[$key]['data'];

                if ($this->saveForeignData($foreignData, $key)) {
                    unset($foreigners[$keyObjectKey]);
                    $object->setForeignColumnsList($foreigners);
                }

            } else {
                Logger::log("Key not found {$key}\n", 'red');
            }
        }
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $value
     * @return string
     */
    private function getSelectKey($tableName, $columnName, $value)
    {
        return $tableName . '-' . $columnName . '-' . $value;
    }


    private function createInsertQuery(SaveRecordObject $saveRecordObject, $isRetry = false)
    {
        if(!$this->tableExist($saveRecordObject->getTable())) {
            Logger::log("Table {$saveRecordObject->getTable()} not exist \n");
            return true;
        }


        $toReturn = true;
        $recordExist = "SELECT 1 FROM {$saveRecordObject->getTable()} WHERE {$saveRecordObject->getWhere()}";

        Logger::log($recordExist, null, null, 3);

        $data = $saveRecordObject->getData();

        $sth = $this->pdohTo->getPdo()->prepare($recordExist);
        $sth->execute($data);
        $recordExist = $sth->fetchAll();


        if (count($recordExist)) {
            $recordExist = true;
        } else {
            $recordExist = false;
        }



        if ($recordExist) {
            Logger::log("Record exist update\n", 'green');
            $this->updateQuery($saveRecordObject);
        } else {
            Logger::log("Record NOT exist create new\n", 'green');

            $sql = "INSERT INTO {$saveRecordObject->getTable()} ({$saveRecordObject->getInsertColumns()}) VALUES ({$saveRecordObject->getInsertValues()})";

            try {
                $sth = $this->pdohTo->getPdo()
                    ->prepare($sql);
                $sth->execute($data);
                Logger::log($sql."\n", 'green');
            } catch (PDOException $e) {
                Logger::log($sql . "\n", 'red');
                throw $e;
            }
        }

    }



    /**
     * @param $tableName
     *
     * @return bool
     */
    private function tableExist($tableName)
    {
        try {
            $sth = $this->pdohTo->getPdo()->prepare("SELECT 1 FROM `{$tableName}` LIMIT 1");
            $sth->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @param SaveRecordObject $saveRecordObject
     * @param bool $isRetry
     *
     * @return bool
     */
    private function updateQuery(SaveRecordObject $saveRecordObject, $isRetry = false)
    {
        $toReturn = true;
        $updateSth = null;
        $set = $saveRecordObject->getSet(true);
        $data = $saveRecordObject->getData(true);

        $sql = "UPDATE {$saveRecordObject->getTable()} SET {$set} WHERE {$saveRecordObject->getWhere()}";

        try {
            $updateSth = $this->pdohTo->getPdo()->prepare($sql);
            $updateSth->execute($data);

            Logger::log($sql."\n", 'green');

        } catch (PDOException $exception) {
            Logger::log($sql."\n", 'red');
            throw $exception;
            Logger::log("UPDATE Fail try again \n", 'red');
            Logger::log($set, 'red', null, 2);
            Logger::log($data, 'red', null, 2);
            if (!$isRetry) {
                $toReturn = $this->updateFail($saveRecordObject);
            } else {
                throw $exception;
                $toReturn = false;
            }

        }
        return $toReturn;
    }

    private function updateFail(SaveRecordObject $saveRecordObject)
    {
        $sth = $this->pdohTo->getPdo()->prepare("SHOW COLUMNS FROM {$saveRecordObject->getTable()}");
        $sth->execute();
        $newData = [];
        $oldData = $saveRecordObject->getData();
        $primaryColumnsCollection = [];
        $columns = $sth->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, PrimaryColumn::getClassName());

        if (empty($columns)) {
            Logger::log('Nie ma kolumn', 'red');
            return true;
        }
        /** @var PrimaryColumn $column */
        foreach ($columns as $column) {
            $filed = $column->getField();
            if (isset($oldData[$filed])) {
                $newData[$filed] = $oldData[$filed];
            }

            if($column->getKey() === PrimaryColumn::PRIMARY_KEY) {
                $primaryColumnsCollection[] = $column;
            }
        }
    }

    /**
     * @param $foreignData
     * @param $key
     * @return bool
     */
    private function saveForeignData($foreignData, $key)
    {
        $this->insertForeignValue($foreignData);

        $this->createInsertQuery($foreignData);
        $this->foreignInserted[$key] = true;
        return true;
    }
}
