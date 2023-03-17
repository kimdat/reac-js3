<?php

namespace Online;

use ProvinceTable;
use PDO;
use Throwable;
use Error;

class Province
{
    public static function getAllProvinces()
    {
        global $conn;

        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT * FROM " . ProvinceTable::TABLE_PROVINCE;
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array('provinces' => $provinces));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
