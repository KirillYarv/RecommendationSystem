<?php

class PgQuery
{
    private $pgPDO;
    private $svdResultData;
    public function __construct($pgConnection)
    {
        $this->pgPDO = $pgConnection->getPDO();
    }
    public function getUsers()
    {
        $result = $this->pgPDO->query("select * from \"Users\"");
        echo "<pre>";
        while ($item = $result->fetch()) {
            var_dump($item);
        }

        echo "</pre>";
    }
    public function getSVDDataTable()
    {

        $result = $this->pgPDO->query("SELECT p.\"id\" as \"pid\", \"Product Name\", u.\"id\" as \"uid\", \"name\", rating FROM public.\"Product\" p inner join \"UserRating\" \"ur\" on \"productId\" = p.id inner join \"Users\" u on \"userId\" = u.id order by u.\"id\"");

        $countUser = $this->pgPDO->query("select count(id) from \"Users\"")->fetch()["count"];
        $countProduct = $this->pgPDO->query("select count(id) from \"Product\"")->fetch()["count"];

        echo $countUser." ";
        echo $countProduct." ";
        echo $countProduct * $countUser;

        $ratingsMatrix = [];

        for ($i = 0; $i < $countUser; $i++) {
            $ratingsMatrix[$i] = array_fill(0, $countProduct, 0);
        }
        echo "<pre>";

        while ($item = $result->fetch()) {

            //$this->svdResultData[] = $item;
            //if($item["pid"]-1 < 500){
                $ratingsMatrix[$item["uid"]-1][$item["pid"]-1] = $item["rating"];
            //}

        }

        echo "</pre>";
        return $ratingsMatrix;
    }
}