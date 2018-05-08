<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 24.04.18
 * Time: 14:34
 */

namespace ParisEngineers\DeepRecordCopy;


class CopyMachine
{

    /**
     * @var \ParisEngineers\DeepRecordCopy\DBUser
     */
    private $from;

    /**
     * @var \ParisEngineers\DeepRecordCopy\PdoConnector
     */
    private $pdohFrom;

    /**
     * @var \ParisEngineers\DeepRecordCopy\SaveRecordsManger
     */
    private $saveRecordsManger;

    /**
     * @var array
     */
    private $isDownloaded = [];

    public function __construct(DBUser $from)
    {
        $this->from = $from;
        $this->pdohFrom = new PdoConnector($from);
        $this->saveRecordsManger = new SaveRecordsManger();
    }

    /**
     * @param        $tableName
     * @param string $columnName
     * @param int    $recordId
     *
     * @param bool   $isForeign
     *
     * @return bool
     */
    public function selectRecordFrom($tableName, $columnName, $recordId, $isForeign = false)
    {
        if ((!$tableName || !$columnName || !$recordId)) {
            Logger::log("Błędne argumenty dla selectRecordFrom \n", 'red', null, 1);
            return true;
        }

        $key = $tableName.'-'.$columnName.'-'.$recordId;

        if (isset($this->isDownloaded[$key])) {
            Logger::log("Wartość dla $key już była pobrana \n", null, null, 2);
            return true;
        }

        Logger::log("Pobieranie dla wartosci $key \n");

        $this->isDownloaded[$key] = true;

        $primaryColumnsOfTable = $this->getPrimaryColumnsOfTable($tableName);


        $foreignKeyCollection = $this->findForeignKeyOfTable($tableName);
        $referencesOfTable = $this->findReferencesOfTable($tableName, $primaryColumnsOfTable);

        $records = $this->getRecordToCopy($tableName, $columnName, $recordId);

        foreach ($records as $record) {
            $this->fillForeignKey($foreignKeyCollection, $record);

            $saveRecordObject = new SaveRecordObject($tableName, $record, $primaryColumnsOfTable);

            $this->saveRecordsManger->add($saveRecordObject, $isForeign);
            if (!$isForeign) {
                $this->fillReferences($referencesOfTable, $record);
            }
        }

        return true;
    }

    /**
     * @param $tableName
     *
     * @return array ForeignKey
     */
    private function findForeignKeyOfTable($tableName)
    {
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
        return $sth->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, ForeignKey::getClassName());
    }

    private function findReferencesOfTable($tableName, $primaryColumnsCollection)
    {
        if (empty($primaryColumnsCollection)) {
            return [];
        }

        $referencesCollection = [];

        /** @var \ParisEngineers\DeepRecordCopy\PrimaryColumn $primaryColumn */
        foreach ($primaryColumnsCollection as $primaryColumn) {
            $referencesCollection = array_merge($referencesCollection, $this->getReferencesByColumnName($tableName, $primaryColumn->getField()));
        }

        return $referencesCollection;
    }

    private function fillForeignKey(array $foreignKeyCollection, array $record)
    {
        /** @var \ParisEngineers\DeepRecordCopy\ForeignKey $foreignKey */
        foreach ($foreignKeyCollection as $foreignKey) {
            $this->getForeignReferences($foreignKey, $record);
        }
    }

    private function fillReferences(array $referencesCollection, array $record)
    {
        if(empty($referencesCollection)) {
            return;
        }

        /** @var \ParisEngineers\DeepRecordCopy\Reference $reference*/
        foreach ($referencesCollection as $reference) {
            $this->selectRecordFrom($reference->getTableName(), $reference->getColumnName(), $record[$reference->getReferencedColumnName()]);
        }
    }

    public function getSaveRecordCollection()
    {
        return $this->saveRecordsManger->getCollection();
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $recordId
     *
     * @return array
     */
    private function getRecordToCopy($tableName, $columnName, $recordId)
    {
        $sth = $this->pdohFrom->getPdo()->prepare("SELECT * FROM `{$tableName}` WHERE `{$columnName}` = :recordId");

        $sth->execute([
            ':recordId' => $recordId,
        ]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }


    private function getPrimaryColumnsOfTable($tableName)
    {
        try {
            $sth = $this->pdohFrom->getPdo()->prepare("SHOW COLUMNS FROM `{$tableName}`");
            $sth->execute();
        } catch (\PDOException $exception) {
            Logger::log("SHOW COLUMNS FROM {$tableName} \n", 'red');
            Logger::log($exception, 'red', null, 3);
            return [];
        }

        $columns = $sth->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, PrimaryColumn::getClassName());

        if(empty($columns)) {
            return [];
        }
        $collection = [];

        /** @var \ParisEngineers\DeepRecordCopy\PrimaryColumn $column */
        foreach ($columns as $column) {
            if ($column->getKey() === PrimaryColumn::PRIMARY_KEY) {
                $collection[] = $column;
            }
        }

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
        $sth = $this->pdohFrom->getPdo()->prepare("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM
            information_schema.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_NAME = :tableName AND REFERENCED_COLUMN_NAME = :columnName AND TABLE_SCHEMA = :schema ");

        $sth->execute([
            ':tableName' => $tableName,
            ':columnName' => $columnName,
            ':schema' => $this->from->getName(),
        ]);

        return $sth->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Reference::getClassName());
    }

    private function getForeignReferences(ForeignKey $foreignKey, array $record)
    {
        $this->selectRecordFrom($foreignKey->getReferencedTableName(), $foreignKey->getReferencedColumnName(), $record[$foreignKey->getColumnName()], true);

    }
}
