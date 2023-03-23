<?php

namespace ManageInventories;

use Error;
use Exception;
use PDO;
use PDOException;
use Throwable;

class devices
{
    function getAllDevices()
    {
        global  $devicesDefine;

        global $conn;
        // Khởi tạo giá trị ban đầu cho biến @rownum
        try {
            $limit = 10;
            if (isset($_GET["rowsPerPage"])) {
                $limit = $_GET["rowsPerPage"];
            }
            $conn->query("SET @rownum = 0");
            $sql = "SELECT  d." .  $devicesDefine::COLUMN_DEVICES_ID . " as id, d." .  $devicesDefine::COLUMN_DEVICES_NAME . " as Name, 
        false as showChild, (@rownum := @rownum + 1) as No
        FROM " .  $devicesDefine::TABLE_DEVICES . " d 
        WHERE d." .  $devicesDefine::COLUMN_DEVICES_STATUS . " NOT IN('0','D')
        ORDER BY d." . $devicesDefine::COLUMN_DEVICES_NAME . "
        LIMIT $limit; ";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($stmt->rowCount() == 0) {
                return json_encode(array(
                    'inventories' => array(array('statusNotFound' => true)),
                    "total_row" => 1,
                    "devices" =>  array('statusNotFound' => true)
                ));
            }


            $sql_count = "SELECT " . $devicesDefine::COLUMN_DEVICES_ID . " as id , " .  $devicesDefine::COLUMN_DEVICES_NAME  .
                " as Name FROM " . $devicesDefine::TABLE_DEVICES  .
                " WHERE " .  $devicesDefine::COLUMN_DEVICES_STATUS . " NOT IN('0','D') ORDER BY "
                . $devicesDefine::COLUMN_DEVICES_NAME;
            $stmt_count = $conn->prepare($sql_count);
            $stmt_count->execute();
            $dataDevice = $stmt_count->fetchAll();
            $stt = 1;
            $idNameFilter = array_map(function ($item) use (&$stt) {

                return [
                    "id" => $item["id"],
                    "name" => $item["Name"],
                    "No" => $stt++
                ];
            },  $dataDevice);
            $data = array(
                "inventories" => $inventories,
                "total_row" => sizeof($dataDevice),
                "devices" => $idNameFilter,

            );
            return json_encode($data);
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
