<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 25.04.18
 * Time: 15:06
 */

namespace ParisEngineers\DeepRecordCopy;


class Saver
{
    /**
     * @var array SaveRecordObject
     */
    private $collection = [];
    private $pdohTo;

    public function __construct(DBUser $to)
    {
        $this->pdohTo = new PdoConnector($to);
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
        $lastResults = true;
        /** @var \ParisEngineers\DeepRecordCopy\SaveRecordObject $saveRecordObject */
        while (count($this->collection) !== 0) {
            Logger::log("Index {$i} \n");
            $saveRecordObject = $this->collection[$i];
            if ($this->createInsertQuery($saveRecordObject)) {
                Logger::log("Zapisano {$saveRecordObject->getKey()} \n", 'green');
                unset($this->collection[$i]);
                $this->collection = array_values($this->collection);
                if ($lastResults === false) {
                    Logger::log("Wraca bo ostani byl nie zapisany  \n", 'blue', null, 2);
                    $i = -1;
                }

                $lastResults = true;
            } else {

                if ($lastResults === true) {
                    Logger::log("Wraca bo nie udalo sie zapisac ale ostatni byl zapisany  \n", 'blue', null, 2);
                    $i = -1;
                }

                $lastResults = false;
            }

            $count = count($this->collection);

            Logger::log("Liczba w kolejce: $count  \n", 'cyan', null, 2);
            $i++;

            if($i >= $count) {
                $i = 0;
            }
        }
    }

    private function createInsertQuery(SaveRecordObject $saveRecordObject, $isRetry = false)
    {
        $toReturn = true;
        Logger::log("SELECT * FROM {$saveRecordObject->getTable()} WHERE {$saveRecordObject->getWhere()}; \n", null, null, 3);

        $data = $saveRecordObject->getData();

        if(!$this->tableExist($saveRecordObject->getTable())) {
            Logger::log("Table {$saveRecordObject->getTable()} not exist \n");
            return true;
        }

        try {
            $sth = $this->pdohTo->getPdo()->prepare("REPLACE INTO {$saveRecordObject->getTable()} SET {$saveRecordObject->getSet()} ");
            $sth->execute($data);
        }catch (\PDOException $e) {
            $toReturn = $this->insertFail($saveRecordObject, $isRetry);
        }
        return $toReturn;
    }

    /**
     * @param $tableName
     *
     * @return bool
     */
    private function tableExist($tableName)
    {
        try {
            $sth = $this->pdohTo->getPdo()->prepare("SELECT 1 FROM {$tableName}");
            $sth->execute();
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * @param \ParisEngineers\DeepRecordCopy\SaveRecordObject $saveRecordObject
     * @param bool $isRetry
     *
     * @return bool
     */
    private function insertFail(SaveRecordObject $saveRecordObject, $isRetry = false)
    {
        $toReturn = true;
        Logger::log("INSERT Fail Try update \n", 'red');
        $updateSth = null;
        $set = $saveRecordObject->getSet(true);
        $data = $saveRecordObject->getData(true);
        try {
            $updateSth = $this->pdohTo->getPdo()->prepare("UPDATE {$saveRecordObject->getTable()} SET {$set} WHERE {$saveRecordObject->getWhere()}");

            $updateSth->execute($data);
        } catch (\PDOException $exception) {
            Logger::log("UPDATE Fail try again \n", 'red');
            Logger::log($set, 'red', null, 2);
            Logger::log($data, 'red', null, 2);
            if (!$isRetry) {
                $toReturn = $this->updateFail($saveRecordObject);
            } else {
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
        $columns = $sth->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, PrimaryColumn::class);

        if (empty($columns)) {
            Logger::log('Nie ma kolumn', 'red');
            return true;
        }
        /** @var PrimaryColumn $column */
        foreach ($columns as $column) {
            $filed = $column->getField();
            $newData[$filed] = $oldData[$filed];

            if($column->getKey() === PrimaryColumn::PRIMARY_KEY) {
                $primaryColumnsCollection[] = $column;
            }
        }

        $newSaveRecordObject = new SaveRecordObject($saveRecordObject->getTable(), $newData, $primaryColumnsCollection);
        return $this->createInsertQuery($newSaveRecordObject, true);
    }
}
