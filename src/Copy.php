<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 24.04.18
 * Time: 14:34
 */

namespace ParisEngineers\DeepRecordCopy;

class Copy
{
    private $from;
    private $to;

    /**
     * Set Db user and other credentials for connect to source db
     *
     * @param $host
     * @param $dbName
     * @param $dbUser
     * @param $dbPassword
     *
     * @return \ParisEngineers\DeepRecordCopy\Copy
     */
    public function setFrom($host, $dbName, $dbUser, $dbPassword)
    {
        $this->from = new DBUser();
        $this->from->setHost($host)
            ->setName($dbName)
            ->setUser($dbUser)
            ->setPassword($dbPassword);

        return $this;
    }

    /**
     * Set Db user and other credentials for connect to destination db
     *
     * @param $host
     * @param $dbName
     * @param $dbUser
     * @param $dbPassword
     *
     * @return \ParisEngineers\DeepRecordCopy\Copy
     */
    public function setTo($host, $dbName, $dbUser, $dbPassword)
    {
        $this->to = new DBUser();
        $this->to->setHost($host)
            ->setName($dbName)
            ->setUser($dbUser)
            ->setPassword($dbPassword);

        return $this;
    }

    /**
     * This function copy one base record with all dependencies
     * If record exist will be updated
     *
     * @param string $tableName
     * @param string $columnName
     * @param int    $recordId
     */
    public function copy($tableName, $columnName, $recordId)
    {
        $copyMachine = new CopyMachine($this->from);
        $copyMachine->selectRecordFrom($tableName, $columnName, $recordId);

        $saver = new Saver($this->to);
        $saver->setCollection($copyMachine->getSaveRecordCollection());
        $saver->save();
    }

    public function copyMany($tableName, $columnName, array $recordsIds)
    {
        if (empty($recordsIds)) {
            return false;
        }

        $copyMachine = new CopyMachine($this->from);

        foreach ($recordsIds as $recordId) {
            $copyMachine->selectRecordFrom($tableName, $columnName, $recordId);
        }

        $saver = new Saver($this->to);
        $saver->setCollection($copyMachine->getSaveRecordCollection());
        $saver->save();
    }
}
