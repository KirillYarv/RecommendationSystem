<?php
class PgConnect{
    private static $pgConnection;
    private PDO $pgPDO;
    public function getPDO(): PDO
    {
        return $this->pgPDO;
    }
    private function __construct()
    {
        global $env;
        $env = array();
        if(file_exists($_SERVER['DOCUMENT_ROOT']."/.env")){
            $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/.env');
        } else {
            throw new Exception('ENV file not found');
        }

        $this->pgPDO = new PDO(
            "pgsql:host={$env["HOST"]};port={$env["PORT"]};dbname={$env["DBNAME"]}",
            $env["USER"],
            $env["PASSWORD"],
            []
        );
    }
    public static function getInstance(): PgConnect
    {
        if (is_null(self::$pgConnection)) {
            self::$pgConnection = new self();
        }
        return self::$pgConnection;
    }
}

define("PGCONNECT", PgConnect::getInstance());