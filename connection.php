<?php

class Connection extends PDO
{
    private $options = [
        PDO::ATTR_CASE               => PDO::CASE_UPPER,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    public function __construct (Credencials $credencials)
    {
        if ($credencials = $credencials->get()) {
            $user        = $credencials['user'];
            $pass        = $credencials['pass'];
            $host        = $credencials['host'];
            $dbname      = $credencials['dbname'];
            $dsn         = "pgsql:host=$host;dbname=$dbname";

            parent::__construct($dsn, $user, $pass, $this->options);
        }
    }
}