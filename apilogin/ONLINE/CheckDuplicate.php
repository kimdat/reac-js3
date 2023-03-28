<?php


namespace Online;

use Error;
use Exception;
use PDOException;
use Throwable;

//xóa những device trùng(update status D)
class  CheckDuplicate
{ //xóa 1 ip
    function CheckDuplicate2()
    {
        global $devicesDefine, $conn;
        try {
            $device = json_decode($_POST["device"]);
            $device_ip = trim($device->ip);
            $device_name = trim($device->deviceName);
            $sql = "SELECT  " . $devicesDefine::COLUMN_DEVICES_IP . " FROM " . $devicesDefine::TABLE_DEVICES . " WHERE "
                . $devicesDefine::COLUMN_DEVICES_STATUS . " <> 'D' AND ("
                . $devicesDefine::COLUMN_DEVICES_IP . "=:ip OR "
                . $devicesDefine::COLUMN_DEVICES_NAME . "=:name )";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':ip', $device_ip);
            $stmt->bindParam(':name', $device_name);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return json_encode(array("duplicate" => true));
            }
            return "";
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
    }
    function CheckDuplicate()
    {
        global $devicesDefine, $conn;
        try {

            if (!isset($_POST["name"])) {
                return "";
            }
            $name = $_POST["name"];
            $value = $_POST["value"];

            //nếu là ip thì check theo ip, không thì check theo name
            $where = $name == "Ip" ? $devicesDefine::COLUMN_DEVICES_IP . "=:value" : $devicesDefine::COLUMN_DEVICES_NAME . "=:value";
            $sql = "SELECT  " . $devicesDefine::COLUMN_DEVICES_IP . " FROM " . $devicesDefine::TABLE_DEVICES . " WHERE "
                . $devicesDefine::COLUMN_DEVICES_STATUS . " <> 'D' AND (" . $where . ")";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':value', $value);

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return json_encode(array("duplicate" => true));
            }
            return "";
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
    }
}
