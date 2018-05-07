<?php
/**
 * Created by PhpStorm.
 * User: Mateusz Bochen
 * Date: 24.04.18
 * Time: 14:01
 */

namespace ParisEngineers\DeepRecordCopy;


class DBUser
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $host;

    /**
     * DBUser constructor.
     *
     * @param string $name
     * @param string $password
     * @param string $user
     * @param string $host
     */
    public function __construct($name = null, $password = null, $user = null, $host = null)
    {
        $this->name = $name;
        $this->password = $password;
        $this->user = $user;
        $this->host = $host;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return DBUser
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return DBUser
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return DBUser
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return DBUser
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }
}
