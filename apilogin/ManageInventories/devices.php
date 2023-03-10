<?php

namespace ManageInventories;

use Error;
use Exception;
use PDO;
use PDOException;

class devices
{
    function getAllDevices()
    {
        global  $devicesDefine;
        $currentFile = basename(__FILE__);
        global $conn;
        // Khởi tạo giá trị ban đầu cho biến @rownum
        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT  d." .  $devicesDefine::COLUMN_DEVICES_ID . " as id, d." .  $devicesDefine::COLUMN_DEVICES_NAME . " as Name, 
        false as showChild, (@rownum := @rownum + 1) as STT
        FROM " .  $devicesDefine::TABLE_DEVICES . " d 
        WHERE d." .  $devicesDefine::COLUMN_DEVICES_STATUS . " NOT IN('0','D')
       
        ORDER BY d." . $devicesDefine::COLUMN_DEVICES_NAME . "
        LIMIT 10;
        ";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $sql_count = "SELECT " . $devicesDefine::COLUMN_DEVICES_ID . " as id , " .  $devicesDefine::COLUMN_DEVICES_NAME  .
                " as Name FROM " . $devicesDefine::TABLE_DEVICES  .
                " WHERE " .  $devicesDefine::COLUMN_DEVICES_STATUS . " NOT IN('0','D') ORDER BY "
                . $devicesDefine::COLUMN_DEVICES_NAME;
            $stmt_count = $conn->prepare($sql_count);
            $stmt_count->execute();
            $dataDevice = $stmt_count->fetchAll();
            $idNameFilter = array_map(function ($item) {
                return [
                    "id" => $item["id"],
                    "name" => $item["Name"]
                ];
            },  $dataDevice);
            $data = array(
                "inventories" => $inventories,
                "total_row" => sizeof($dataDevice),
                "devices" => $idNameFilter
            );
            return json_encode($data);
        } catch (PDOException $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        } catch (Error $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
