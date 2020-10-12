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
    private $infinityLoopTables = [];

    public function __construct(DBUser $to)
    {
        $this->pdohTo = new PdoConnector($to);
    }

    public function setInfinityLoopTables($infinityLoopTables)
    {
        $this->infinityLoopTables = $infinityLoopTables;
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
        $count = count($this->collection);
        Logger::log("ZAPIS: - Do zapisania jest gotowych {$count} rekordów\n", 'green', 'red');

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
        Logger::log("ZAPIS: Dodawanie Rekordu Typu Foreign: {$object->getTable()} {$object->getKey()}\n", 'yellow');

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
                Logger::log("ZAPIS: Key zaoisany wczesniej {$key}\n", 'green', null, 2);
                continue;
            }


            if (isset($foreignersData[$key])) {
                $foreignData = $foreignersData[$key]['data'];

                if ($this->saveForeignData($foreignData, $key)) {
                    unset($foreigners[$keyObjectKey]);
                    $object->setForeignColumnsList($foreigners);
                }

            } else {
                Logger::log("ZAPIS: Klucz {$key} nie istnieje w kolekcji kluczy obcych\n", 'red');
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
            Logger::log("ZAPIS: Table {$saveRecordObject->getTable()} not exist \n", 'red', null, 2);
            return true;
        }

        $data = $saveRecordObject->getData();
        $recordExist = $this->recordExist($saveRecordObject, $data);

        if ($recordExist) {
            Logger::log("ZAPIS: Record exist try update\n", 'green', null, 2);
            $this->updateQuery($saveRecordObject);
        } else {
            Logger::log("Record NOT exist create new\n", 'green', null , 2);

            $sql = "INSERT INTO `{$saveRecordObject->getTable()}` ({$saveRecordObject->getInsertColumns()}) VALUES ({$saveRecordObject->getInsertValues()})";

            try {
                $sth = $this->pdohTo->getPdo()
                    ->prepare($sql);
                $sth->execute($data);
                Logger::log("ZAPIS: ".$sql."\n", 'green');
            } catch (PDOException $e) {
                Logger::log("ZAPIS: ".$sql . "\n", 'red', null, 2);
                Logger::log($e, 'red', null, 3);
                $this->updateQuery($saveRecordObject);
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
        $set = $saveRecordObject->getSet(true);
        $data = $saveRecordObject->getData(true);

        if (!$set) {
            return true;
        }

        $sql = "UPDATE `{$saveRecordObject->getTable()}` SET {$set} WHERE {$saveRecordObject->getWhere()}";

        try {
            $updateSth = $this->pdohTo->getPdo()->prepare($sql);
            $updateSth->execute($data);
            Logger::log("ZAPIS: ".$sql."\n", 'green');
        } catch (PDOException $exception) {
            Logger::log("ZAPIS: ".$sql."\n", 'red');
            Logger::log($set, 'red', null, 2);
            Logger::log($data, 'red', null, 2);
            Logger::log($exception, 'red', null, 3);
        }
        return true;
    }

    /**
     * @param $foreignData
     * @param $key
     * @return bool
     */
    private function saveForeignData($foreignData, $key)
    {
        if (!in_array($foreignData->getTable(), $this->infinityLoopTables)) {
            $this->insertForeignValue($foreignData);
        }
        $this->createInsertQuery($foreignData);
        $this->foreignInserted[$key] = true;
        return true;
    }

    /**
     * @param SaveRecordObject $saveRecordObject
     * @param array $data
     * @return bool
     */
    private function recordExist(SaveRecordObject $saveRecordObject, $data)
    {
        $where = $saveRecordObject->getWhere();
        $recordExistSql = "SELECT 1 FROM `{$saveRecordObject->getTable()}` WHERE {$where}";
        $recordExist = [];

        try {
            $sth = $this->pdohTo->getPdo()
                ->prepare($recordExistSql);
            $sth->execute($data);
            $recordExist = $sth->fetchAll();
            Logger::log("ZAPIS: ".$recordExistSql."\n", 'blue', null, 2);
        } catch (\Exception $exception) {
            Logger::log("ZAPIS: ".$recordExistSql."\n", 'red', null, 2);
            Logger::log($exception, 'red', null, 3);
        }

        if (count($recordExist)) {
            $recordExist = true;
        } else {
            $recordExist = false;
        }

        return $recordExist;
    }
}
