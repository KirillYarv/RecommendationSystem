<?php


class PgConnect{
    private static $pgConnection;

    private $pgPDO;
    public function getPDO()
    {
        return $this->pgPDO;
    }
    public function __construct()
    {
        global $arParameter;
        $arParameter = array();
        if(file_exists("DB/ConnectParameter.php")){
            require_once("DB/ConnectParameter.php");
        }

        $this->pgPDO = new PDO(
            "pgsql:host={$arParameter["host"]};port={$arParameter["port"]};dbname={$arParameter["dbname"]}",
            $arParameter["user"],
            $arParameter["password"],
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
const PGCONNECT = new PgConnect();