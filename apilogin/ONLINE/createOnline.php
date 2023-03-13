<?php

namespace Online;

use Error;
use Exception;

use PDOException;
use Online\connectDevice;
use Online\removeDeviceDuplicate;
use Throwable;

use function PHPUnit\Framework\throwException;

class createOnline
{
    function createOnline()
    {
        global $conn;
        $status = 0;
        $device_id = null;
        $deviceName = "";
        try {
            if (!isset($_POST["device_list"]) || empty($_POST["device_list"])) {
                return;
            }
            $devices_list = json_decode($_POST["device_list"]);

            if (is_array($devices_list)) {
                $devices_list = array_map(function ($device) {
                    $device->device_type = "cisco_xr";
                    $device->ip = trim($device->ip);
                    $device->username = trim($device->username);
                    $device->password = trim($device->password);
                    $device->port = "22";
                    return $device;
                }, $devices_list);
            } else {
                $devices_list->device_type = "cisco_xr";
                $devices_list->ip = trim($devices_list->ip);
                $devices_list->username = trim($devices_list->username);
                $devices_list->password = trim($devices_list->password);
                $devices_list->port = "22";
            }
            //connect thiết bị để lấy thông tin thiết bị con
            $connectDevice = new connectDevice();
            $res =  $connectDevice->connectDevice($devices_list);
            //Gía trị trả về là mảng json với key là ip
            $res = json_decode($res);


            //check và xóa device cũ nếu trùng ip
            $remove_device_dup = new removeDeviceDuplicate();
            $inventory = $res->deviceData;
            $inventory = json_decode($inventory);
            $devicesName = $res->deviceName;
            //data connect thành công
            $dataSuccess = [];
            //data connect thất bị
            $dataFail = [];
            $err = [];
            foreach ($devicesName as $ip => $deviceName) {
                try {
                    $conn->beginTransaction();
                    $dataInventory = $inventory->$ip;


                    $remove_device_dup->removeDeviceDuplicate($conn, $ip);
                    //nếu không có lỗi thì status là 1
                    if (!isset($dataInventory[0]->Err)) {
                        $status = 1;
                    }

                    $device_id = self::insertParentOnline($conn, $deviceName, $ip, $status);
                    //Nếu không lỗi khi connect thì insertData
                    $children = [];
                    if ($status == 1) {
                        $children = self::insertDataOnline($conn,  $dataInventory, $device_id);
                        $dataSuccess[] = array("ip" => $ip, "id" => $device_id, "Name" => $deviceName, "children" => $children);
                    } else {
                        $dataFail[] = array("ip" => $ip, "id" => $device_id, "Name" => $deviceName, "children" => $children);
                    }

                    $conn->commit();
                } catch (Throwable $th) {
                    $mess = self::rollBackData($conn, $th->getMessage() . " at $ip", $device_id, $status);
                    //nếu không có lỗi
                    if ($mess == null  || isset($mess["DataFail"])) {
                        $dataFail[] = array("ip" => $ip, "id" => $device_id, "Name" => $deviceName, "children" => $children);
                    }
                    if (isset($mess["Err"]))
                        $err[] = array($ip => $mess["Err"]);
                }
            }
            return json_encode(array("success" => $dataSuccess, "Err" => $err, "fail" => $dataFail));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new  Error("Err in $currentFunction in $currentFile " . $th->getMessage());
        }
    }
    function insertParentOnline($conn, $deviceName, $ip, $status)
    {
        try {
            global $devicesDefine;

            $timestamp = time();
            $random_string = uniqid('rd', true);
            $device_id = $timestamp . $random_string;

            $sqlParent = "INSERT INTO " .  $devicesDefine::TABLE_DEVICES
                . " (" . $devicesDefine::COLUMN_DEVICES_NAME
                . "," .  $devicesDefine::COLUMN_DEVICES_IP
                . "," . $devicesDefine::COLUMN_DEVICES_ID
                . "," . $devicesDefine::COLUMN_DEVICES_STATUS
                . ") VALUES (?,?,?,?)";
            $stmtParent = $conn->prepare($sqlParent);
            $stmtParent->bindParam(1, $deviceName);
            $stmtParent->bindParam(2, $ip);
            $stmtParent->bindParam(3, $device_id);
            $stmtParent->bindParam(4, $status);
            $stmtParent->execute();

            return $device_id;
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
    }
    function insertDataOnline($conn,  $inventory, $device_id)
    {

        global $inventoriesDefine;
        try {
            // Chuyển đổi mảng JSON thành mảng PHP
            $values = array();
            throw new Exception("123");
            foreach ($inventory as $item) {
                $values[] = array(
                    'Name' => $item->NAME,
                    'CDESC' => $item->DESCR,
                    'PID' => $item->PID,
                    'VID' => $item->VID,
                    'Serial' => $item->SN,
                    'ParentId' => $device_id
                );
            }
            $placeholders = array_fill(0, count($values), "(?, ?, ?, ?, ?,?)");
            $values_flat = array();
            foreach ($values as $row) {
                $values_flat = array_merge($values_flat, array_values($row));
            }
            $sql = "INSERT INTO " . $inventoriesDefine::TABLE_INVENTORIES . " ("
                . $inventoriesDefine::COLUMN_INVENTORIES_NAME . ", "
                . $inventoriesDefine::COLUMN_INVENTORIES_CDESC . ","
                . $inventoriesDefine::COLUMN_INVENTORIES_PID . ","
                . $inventoriesDefine::COLUMN_INVENTORIES_VID . ","
                . $inventoriesDefine::COLUMN_INVENTORIES_SERIAL . ","
                . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID .
                ") VALUES " . implode(',', $placeholders);
            $stmt = $conn->prepare($sql);
            $stmt->execute($values_flat);
            return $values;
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
        return;
    }
    //rollback khi lỗi
    public   function rollBackData($conn, $mess, $device_id, $status)
    {
        //device_id null là insert parent không thành công
        if (!$device_id) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return array("Err" => $mess);
        }
        //nếu đã có device_id rồi thì commit luôn
        if ($status === 0) {
            $conn->commit();
            return array("Err" => $mess, "FAIL" => "DataFail");
        }
        //uplại status cha là 0
        self::updateStatusParent($conn, $device_id);
    }
    public function updateStatusParent($conn, $device_id)
    {
        global $devicesDefine;
        try {
            $sql = "UPDATE " . $devicesDefine::TABLE_DEVICES . " SET " .
                $devicesDefine::COLUMN_DEVICES_STATUS . " = '0' WHERE "
                .   $devicesDefine::COLUMN_DEVICES_ID . "=:id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $device_id);
            $stmt->execute();
            $conn->commit();
            return;
        } catch (Throwable $e) {
            $currentFunction = __FUNCTION__;
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return array("Err" => "Err $currentFunction" . $e->getMessage());
        }
    }
}
