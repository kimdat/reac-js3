<?php

namespace Online;

use Error;



use Online\connectDevice;
use Online\removeDeviceDuplicate;
use Throwable;



class createOnline
{
    function trimParameter($devices_list)
    {
        if (is_array($devices_list)) {

            $devices_list = array_map(function ($device) {
                global $devicesDefine;
                //nếu không có ip thì continue
                if (isset($device[$devicesDefine::COLUMN_DEVICES_IP])) {
                    $device = (object) array_map('trim', (array) $device);
                    return $device;
                }
            }, $devices_list);
            if (sizeof($devices_list) == 0) {
                throw new Error("ip invalid or notfound of all device you choosed");
            }
        } else {
            if (!isset($devices_list->ip)) {
                throw new Error("ip invalid or notfound");
            } else {
                $devices_list = (object) array_map('trim', (array) $devices_list);
            }
        }
        return $devices_list;
    }



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
            $devices_list = self::trimParameter($devices_list);
            //Mappindhardware
            $devices_list = self::mappingHardware($devices_list);
            $flagUpDate = $_POST["flagUpdate"];
            //connect thiết bị để lấy thông tin thiết bị con
            $connectDevice = new connectDevice();
            $res =  $connectDevice->connectDevice($devices_list);

            //Gía trị trả về là mảng json với key là ip
            $res = json_decode($res);
            //check và xóa device cũ nếu trùng id
            $remove_device_dup = new removeDeviceDuplicate();
            //data nventory

            $inventory = $res->deviceData;
            $inventory = json_decode($inventory);
            //data connect thành công
            $dataSuccess = [];
            //data connect thất bị
            $dataFail = [];
            $err = [];
            $step = "";
            foreach ($devices_list as $device) {
                try {
                    $status = 0;
                    $conn->beginTransaction();
                    $ip = $device->ip;
                    if (trim($ip) == "") {
                        throw new Error("No have ip");
                    }
                    $dataInventory = $inventory->$ip;
                    $deviceName = $device->deviceName;
                    //nếu là update  thỉ xóa thiết bị trùng ip

                    if ($flagUpDate == "true") {
                        $step = "remove_device_dup";
                        $remove_device_dup->removeDeviceDuplicate($conn, $ip);
                    }
                    //nếu có lỗi thì status là 0
                    $dataInventoryFirst = $dataInventory[0];
                    if (!isset($dataInventoryFirst->Err)) {
                        $status = 1;
                    }
                    $step = "insertparent";
                    $device_id = self::insertParentOnline($conn, $device, $status);
                    //Nếu không lỗi khi connect thì insertData
                    $children = [];
                    $step = "insertData";
                    if ($status == 1) {
                        $children = self::insertDataOnline($conn,  $dataInventory, $device_id);
                        $dataSuccess[] = array("ip" => $ip, "id" => $device_id, "Name" => $deviceName, "children" => $children);
                    } else {
                        $dataFail[] = array("ip" => $ip, "id" => $device_id, "Name" => $deviceName, "children" => $children);
                    }
                    $conn->commit();
                } catch (Throwable $th) {
                    if ($ip == null || $ip == "") {
                        $err[] = array($ip => $th->getMessage());
                    } else {
                        $mess = self::rollBackData($conn, $th->getMessage() . " at step $step at $ip", $device_id, $status);
                        //nếu không có lỗi
                        if ($mess == null  || isset($mess["Fail"])) {
                            $dataIpFail = array("ip" => $ip, "id" => $device_id, "Name" => $deviceName, "children" => $children);
                            if (!in_array($dataIpFail, $dataSuccess) && !in_array($dataIpFail, $dataFail)) {
                                $dataFail[] = $dataIpFail;
                            }
                        }
                        if (isset($mess["Err"]))
                            $err[] = array($ip => $mess["Err"]);
                    }
                }
            }
            return json_encode(array("success" => $dataSuccess, "Err" => $err, "fail" => $dataFail));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new  Error("Err in $currentFunction in $currentFile " . $th->getMessage());
        }
    }
    function mappingHardware($devices_list)
    {
        $devices_list = array_map(function ($device) {
            //nếu không có ip thì continue
            try {

                $device->device_type_S = self::getDataHardware($device->Device_Type);

                return $device;
            } catch (\Throwable $th) {
            }
        }, $devices_list);


        return $devices_list;
    }
    function getDataHardware($deviceType)
    {
        global $conn;
        $sql = "SELECT " . COLUMN_DEVICETYPE_S . " FROM " . TABLE_MAPPING_HARDWARE . " WHERE " . COLUMN_DEVICETYPE_H . "=:device_type";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":device_type", $deviceType);
        $stmt->execute();
        if ($stmt->rowCount() == 0)
            return "";
        return $stmt->fetchColumn(0);
    }
    function insertParentOnline($conn, $device, $status)
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
                . "," . $devicesDefine::COLUMN_DEVICES_REGION_ID
                . "," . $devicesDefine::COLUMN_DEVICES_PROVINCE_ID
                . ", " . $devicesDefine::COLUMN_DEVICES_LONG
                . "," . $devicesDefine::COLUMN_DEVICES_LAT
                . "," . $devicesDefine::COLUMN_DEVICES_ADDRESS
                . "," . $devicesDefine::COLUMN_DEVICES_TYPE
                . ") VALUES (?,?,?,?,?,?,?,?,?,?)";

            $stmtParent = $conn->prepare($sqlParent);
            $stmtParent->bindParam(1, $device->deviceName);
            $stmtParent->bindParam(2, $device->ip);
            $stmtParent->bindParam(3, $device_id);
            $stmtParent->bindParam(4, $status);
            $stmtParent->bindParam(5, $device->region);
            $stmtParent->bindParam(6, $device->province);
            $stmtParent->bindParam(7, $device->long);
            $stmtParent->bindParam(8, $device->lat);
            $stmtParent->bindParam(9, $device->address);
            $stmtParent->bindParam(10, $device->deviceType);
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

            foreach ($inventory as $item) {

                $values[] = array(
                    'Name' => $item->Name,
                    'CDESC' => $item->CDESC,
                    'PID' => $item->PID,
                    'VID' => $item->VID,
                    'Serial' => $item->Serial,
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
            return array("Err" => $mess, "Fail" => "DataFail");
        }
        //uplại status cha là 0
        return self::updateStatusParent($conn, $device_id, $mess);
    }
    public function updateStatusParent($conn, $device_id, $mess)
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
            return array("Err" => $mess, "Fail" => "DataFail");
        } catch (Throwable $e) {
            $currentFunction = __FUNCTION__;
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return array("Err" => "Err $currentFunction" . $e->getMessage());
        }
    }
}
