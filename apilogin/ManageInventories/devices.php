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
        $contrainstStatusDevice = new  \constraintStatusDevices();
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
        WHERE d." .  $devicesDefine::COLUMN_DEVICES_STATUS . "=" . $contrainstStatusDevice::STATUS_MANAGED . "
        or " . $devicesDefine::COLUMN_DEVICES_STATUS . "=''" . "ORDER BY d." . $devicesDefine::COLUMN_DEVICES_NAME;
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
            $data = array(
                "inventories" => array_slice($inventories, 0, $limit),
                "total_row" => sizeof($inventories),
                "devices" => $inventories,

            );
            return json_encode($data);
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
