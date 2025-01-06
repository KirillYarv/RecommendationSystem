<?php

class PgQuery
{
    private $pgPDO;

    public function __construct($pgConnection)
    {
        $this->pgPDO = $pgConnection->getPDO();
    }
    public function getUsers()
    {
        $result = $this->pgPDO->query("select * from \"Users\"");
        while ($item = $result->fetch()) {
            var_dump($item);
        }
    }
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