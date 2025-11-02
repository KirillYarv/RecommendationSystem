<?php

class PgQuery
{
    private PDO $pgPDO;

    /**
     * @param PgConnect $pgConnection
     */
    public function __construct(PgConnect $pgConnection)
    {
        $this->pgPDO = $pgConnection->getPDO();
    }

    /**
     * @param string $tableName
     * @param int $id
     * @return array
     */
    public function getList(string $tableName, int $id = -1): array
    {
        $resultArray = [];
        try {
            $result = $this->pgPDO->query($id != -1 ? "select * from \"$tableName\" where id = $id" : "select * from \"$tableName\"");
            while ($item = $result->fetch()) {
                $resultArray[] = $item;
            }

            return $id != -1 ? $resultArray[0] : $resultArray;
        }
        catch (Exception $e){
            print_r($e);
            return array();
        }
    }

    /**
     * @return array
     */
    public function getSVDDataTable(): array
    {
        $result = $this->pgPDO->query("SELECT p.\"id\" as \"pid\", \"Product Name\", u.\"id\" as \"uid\", \"name\", rating FROM public.\"Product\" p inner join \"UserRating\" \"ur\" on \"productId\" = p.id inner join \"Users\" u on \"userId\" = u.id order by u.\"id\"");

        $countUser = $this->pgPDO->query("select count(id) from \"Users\"")->fetch()["count"];
        $countProduct = $this->pgPDO->query("select count(id) from \"Product\"")->fetch()["count"];

        $ratingsMatrix = [];

        for ($i = 0; $i < $countUser; $i++) {
            $ratingsMatrix[$i] = array_fill(0, $countProduct, 0);
        }

        while ($item = $result->fetch()) {
            $ratingsMatrix[$item["uid"]-1][$item["pid"]-1] = $item["rating"];
        }

        return $ratingsMatrix;
    }
}