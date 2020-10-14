<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 24.04.18
 * Time: 14:34
 */

namespace ParisEngineers\DeepRecordCopy;

use PDO;
use PDOException;

class CopyMachine
{
    /**
     * @var DBUser
     */
    private $from;

    /**
     * @var PdoConnector
     */
    private $pdohFrom;

    /**
     * @var SaveRecordsManger
     */
    private $saveRecordsManger;
    private $getReferencesByColumnNameCollection = [];
    private $showColumnsFromCollection = [];
    private $foreignRecordsCollection = [];
    private $foreignRecordsCollectionKeys = [];
    private $primaryColumns = [];
    private $foreignColumns = [];
    private $sha1Sqls = [];
    private $sha1SqlsResults = [];

    public function __construct(DBUser $from)
    {
        $this->from = $from;
        $this->pdohFrom = new PdoConnector($from);
        $this->saveRecordsManger = new SaveRecordsManger();
    }

    /**
     * @param        $tableName
     * @param string $columnName
     * @param int $recordId
     *
     * @return bool
     */
    public function selectRecordFrom($tableName, $columnName, $recordId)
    {
        $primaryColumnsOfTable = $this->getPrimaryColumnsOfTable($tableName);
        $foreignKeyCollection = $this->findForeignKeyOfTable($tableName);
        $referencesOfTable = $this->findReferencesOfTable($tableName, $primaryColumnsOfTable);

        $records = $this->getRecordToCopy($tableName, $columnName, $recordId, 'a');

        if ($records === null) {
            return true;
        }

        $this->fetchData($tableName, $records, $foreignKeyCollection, $referencesOfTable, $primaryColumnsOfTable);
        $this->saveRecordsManger->setForeignCollection($this->foreignRecordsCollection);
        return true;
    }

    /**
     * @param $tableName
     *
     * @return array ForeignKey
     */
    private function findForeignKeyOfTable($tableName)
    {
        if (isset($this->foreignColumns[$tableName])) {
            return $this->foreignColumns[$tableName];
        }

        $sth = $this->pdohFrom->getPdo()->prepare("SELECT distinct k.REFERENCED_COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.TABLE_NAME, k.COLUMN_NAME 
        FROM information_schema.TABLE_CONSTRAINTS i 
        LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
        WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY'
        AND i.TABLE_SCHEMA = :dbName
        AND i.TABLE_NAME = :tableName");

        $sth->execute([
            ':dbName' => $this->from->getName(),
            ':tableName' => $tableName,
        ]);
        $collection = $sth->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, ForeignKey::getClassName());
        $this->foreignColumns[$tableName] = $collection;
        return $collection;
    }

    private function findReferencesOfTable($tableName, $primaryColumnsCollection)
    {
        if (empty($primaryColumnsCollection)) {
            return [];
        }

        $referencesCollection = [];

        /** @var PrimaryColumn $primaryColumn */
        foreach ($primaryColumnsCollection as $primaryColumn) {
            $referencesCollection = array_merge($referencesCollection, $this->getReferencesByColumnName($tableName, $primaryColumn->getField()));
        }

        return $referencesCollection;
    }

    public function getSaveRecordsManager()
    {
        return $this->saveRecordsManger;
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $recordId
     * @param string $type
     * @return array
     */
    private function getRecordToCopy($tableName, $columnName, $recordId, $type = 'r')
    {
        if (!$tableName) {
            Logger::log("Pobieranie: PUSTA NAZWA TABELI! \n", 'red', null, 4);
            return null;
        }

        $inArray = [];
        $explode = explode(',', $recordId);
        foreach ($explode as $item) {
            $inArray[] = '\''.trim($item).'\'';
        }

        $in = implode(',', $inArray);
        $sql = "SELECT * FROM `{$tableName}` WHERE `{$columnName}` IN($in)";
        $sh1 = sha1($sql);

        if (isset($this->sha1Sqls[$type][$sh1])) {
            Logger::log(
                "Pobieranie:  $type - $sql - return cached array this record is already done \n",
                'yellow',
                'green',
                3
            );
            return null; // anti infinity loop protection
        }

        if (isset($this->sha1SqlsResults[$sh1])) {
            $records = $this->sha1SqlsResults[$sh1];
            Logger::log("Pobieranie:  $type - $sql - records form cache \n", 'yellow', 'green', 3);
        } else {
            $sth = $this->pdohFrom->getPdo()
                ->prepare($sql);
            $sth->execute();
            $records = $sth->fetchAll(PDO::FETCH_ASSOC);
            $this->sha1SqlsResults[$sh1] = $records;
            Logger::log("Pobieranie:  $type - $sql \n", 'blue', null, 2);
        }

        $this->sha1Sqls[$type][$sh1] = $records;
        return $this->sha1Sqls[$type][$sh1];
    }

    private function getPrimaryColumnsOfTable($tableName)
    {
        if (!$tableName) {
            return [];
        }

        if (isset($this->primaryColumns[$tableName])) {
            return $this->primaryColumns[$tableName];
        }

        if (isset($this->showColumnsFromCollection[$tableName])) {
            Logger::log("Pobieranie: SHOW COLUMNS FROM {$tableName} Cache \n", 'green', null, 3);
            $columns = $this->showColumnsFromCollection[$tableName];
        } else {
            try {
                $sth = $this->pdohFrom->getPdo()->prepare("SHOW COLUMNS FROM `{$tableName}`");
                $sth->execute();
            } catch (PDOException $exception) {
                Logger::log("Pobieranie: : ERROR SHOW COLUMNS FROM {$tableName} use log lvl5 to see more details\n", 'red', null, 4);
                Logger::log($exception, 'red', null, 5);
                return [];
            }

            $columns = $sth->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, PrimaryColumn::getClassName());
            $this->showColumnsFromCollection[$tableName] = $columns;
        }

        if(empty($columns)) {
            return [];
        }

        $collection = [];
        /** @var PrimaryColumn $column */
        foreach ($columns as $column) {
            if ($column->getKey() === PrimaryColumn::PRIMARY_KEY) {
                $collection[] = $column;
            }
        }

        $this->primaryColumns[$tableName] = $collection;

        return $collection;
    }

    /**
     * @param string $tableName
     * @param string $columnName
     *
     * @return array
     */
    private function getReferencesByColumnName($tableName, $columnName)
    {
        $key = $tableName.'-'.$columnName.'-'.$this->from->getName();

        if  (isset($this->getReferencesByColumnNameCollection[$key])) {
            Logger::log("Pobieranie: information_schema.KEY_COLUMN_USAGE From cache \n", 'green', null, 4);
            return $this->getReferencesByColumnNameCollection[$key];
        }

        $sth = $this->pdohFrom->getPdo()->prepare("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME = :tableName AND REFERENCED_COLUMN_NAME = :columnName AND TABLE_SCHEMA = :schema ");

        $sth->execute([
            ':tableName' => $tableName,
            ':columnName' => $columnName,
            ':schema' => $this->from->getName(),
        ]);

        $data = $sth->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Reference::getClassName());
        $this->getReferencesByColumnNameCollection[$key] = $data;
        return $this->getReferencesByColumnNameCollection[$key];
    }

    /**
     * @var Reference[] $tablesCollection
     * @var array $data
     */
    public function getReferencesData(array $tablesCollection, array $data)
    {
        foreach ($tablesCollection as $reference) {
            $primaryColumnsOfTable = $this->getPrimaryColumnsOfTable($reference->getTableName());
            $referencesOfTable = $this->findReferencesOfTable($reference->getTableName(), $primaryColumnsOfTable);

            $records = $this->getRecordToCopy(
                $reference->getTableName(),
                $reference->getColumnName(),
                $data[$reference->getReferencedColumnName()],
                'b'
            );

            if ($records === null) {
                continue;
            }

            $foreignKeyCollection = $this->findForeignKeyOfTable($reference->getTableName());

            if (count($records)) {
                $this->fetchData(
                    $reference->getTableName(),
                    $records,
                    $foreignKeyCollection,
                    $referencesOfTable,
                    $primaryColumnsOfTable
                );
            }
        }
    }

    /**
     * @var Reference[] $tablesCollection
     * @var array $record
     */
    private function getForeignRecordsForReferencesTables(array $tablesCollection, array $record)
    {
        foreach ($tablesCollection as $reference) {
            $selectRowRecord = new SelectRowRecord(
                $reference->getTableName(),
                $reference->getColumnName(),
                $record[$reference->getReferencedColumnName()]
            );
            $this->checkForeigners($selectRowRecord);
        }
    }

    /**
     * @param ForeignKey[] $foreignKeys
     * @param array $record
     * @return array
     */
    private function collectForeignRecords(array $foreignKeys, array $record)
    {
        if (!count($foreignKeys)) {
            return [];
        }

        $localForeignCollection = [];

        foreach ($foreignKeys as $foreignKey) {
            if (isset($record[$foreignKey->getColumnName()]) && $record[$foreignKey->getColumnName()] !== null) {
                $selectRow = new SelectRowRecord(
                    $foreignKey->getReferencedTableName(),
                    $foreignKey->getReferencedColumnName(),
                    $record[$foreignKey->getColumnName()]
                );

                if ($this->checkForeignSelect($selectRow)) {
                    $localForeignCollection[] = $selectRow;
                    $this->addToForeignCollection($selectRow);
                }
            }
        }

        return $localForeignCollection;
    }

    private function checkForeignSelect(SelectRowRecord $record)
    {
        $selectKey = $this->getSelectKey($record);

        if (isset($this->foreignRecordsCollectionKeys[$selectKey])) {
            return false;
        }

        $this->foreignRecordsCollectionKeys[$selectKey] = true;
        return true;
    }

    private function checkForeigners(SelectRowRecord $record)
    {
        $foreignKeyCollection = $this->findForeignKeyOfTable($record->getTableFrom());
        $recordsToCopy = $this->getRecordToCopy(
            $record->getTableFrom(),
            $record->getTableColumn(),
            $record->getWhereValue(),
            'c'
        );

        if ($recordsToCopy === null || !count($recordsToCopy)) {
            return;
        }
        foreach ($recordsToCopy as $recordToCopy) {
            $foreignCollection = $this->collectForeignRecords($foreignKeyCollection, $recordToCopy);
            if (count($foreignCollection)) {
                foreach ($foreignCollection as $foreignRecord) {
                    $this->checkForeigners($foreignRecord);
                }
            }
        }
    }

    /**
     * @param SelectRowRecord $record
     * @return string
     */
    private function getSelectKey(SelectRowRecord $record)
    {
        return $record->getTableFrom() . '-' . $record->getTableColumn() . '-' . $record->getWhereValue();
    }

    private function addToForeignCollection(SelectRowRecord $selectRow)
    {
        $key = $this->getSelectKey($selectRow);
        $records =  $this->getRecordToCopy(
            $selectRow->getTableFrom(),
            $selectRow->getTableColumn(),
            $selectRow->getWhereValue(),
            'f'
        );

        if ($records === null) {
            return;
        }

        $foreignKeyCollection = $this->findForeignKeyOfTable($selectRow->getTableFrom());
        $primaryColumnsOfTable = $this->getPrimaryColumnsOfTable($selectRow->getTableFrom());
        $saveRecordObject = null;

        foreach ($records as $data) {
            $saveRecordObject = new SaveRecordObject($selectRow->getTableFrom(), $data, $primaryColumnsOfTable, $foreignKeyCollection);
        }
        if ($saveRecordObject) {
            Logger::log("Pobieranie: Dodano do zapisu jako FOREIGN {$key}\n", 'blue');
            $this->foreignRecordsCollection[$key] = [
                'record' => $selectRow,
                'key' => $key,
                'data' => $saveRecordObject,
            ];
        }
    }

    /**
     * @param string $tableName
     * @param array $records
     * @param array $foreignKeyCollection
     * @param array $referencesOfTable
     * @param array $primaryColumnsOfTable
     */
    private function fetchData($tableName, array $records, array $foreignKeyCollection, array $referencesOfTable, array $primaryColumnsOfTable)
    {
        foreach ($records as $record) {
            $foreignCollection = $this->collectForeignRecords($foreignKeyCollection, $record);

            if (count($foreignCollection)) {
                foreach ($foreignCollection as $foreignRecord) {
                    $this->checkForeigners($foreignRecord);
                }
            }

            $saveRecordObject = new SaveRecordObject($tableName, $record, $primaryColumnsOfTable, $foreignKeyCollection);
            $this->saveRecordsManger->add($saveRecordObject);
            $this->getForeignRecordsForReferencesTables($referencesOfTable, $record);
            $this->getReferencesData($referencesOfTable, $record);
        }
    }
}
