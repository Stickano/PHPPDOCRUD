<?php

class Credencials
{
    private $user   = 'DATABASE_USERNAME';
    private $pass   = 'DATABASE_PASSWORD';
    private $host   = 'HOST';
    private $dbname = 'DATABASE_NAME';

    /**
     * Return the credencials.
     *
     * Used for the connection object.
     *
     * @return array user, pass, host & dbname
     */
    public function get()
    {
        if (empty($this->user) ||
            empty($this->pass) ||
            empty($this->host) ||
            empty($this->dbname))
        {
            return false;
        }

        return array('user'   => $this->user,
                     'pass'   => $this->pass,
                     'host'   => $this->host,
                     'dbname' => $this->dbname);
    }
}

?>
