<?php

namespace Online;

use RegionTable;
use PDO;
use Throwable;
use Error;

class Region
{
    public static function getAllRegions()
    {
        global $conn;

        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT * FROM " . RegionTable::TABLE_REGION;
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array('regions' => $regions));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
