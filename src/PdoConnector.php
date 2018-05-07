<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 24.04.18
 * Time: 14:25
 */

namespace ParisEngineers\DeepRecordCopy;


class PdoConnector
{
    /**
     * @var \PDO
     */
    protected $pdoh;

    public function __construct(DBUser $dbUser)
    {
        $dsn = 'mysql:host='.$dbUser->getHost();
        $dsn .= ';dbname='.$dbUser->getName();

        $this->pdoh = new \PDO($dsn, $dbUser->getUser(), $dbUser->getPassword());
        $this->pdoh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function getPdo()
    {
        return $this->pdoh;
    }

    public function close()
    {
        $this->pdoh = null;
    }
}