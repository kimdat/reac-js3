<?php

namespace Online;

use Error;
use Exception;

use PDOException;
use Online\connectDevice;
use Online\removeDeviceDuplicate;


class createOnline
{
    function createOnline()
    {
        global $conn, $devicesDefine;
        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
        $status = 0;
        $device_id = null;
        $deviceName = "";
        $devices_ip = "";
        try {
            if (!isset($_POST["ip"]) || empty($_POST["ip"])) {
                return;
            }
            $devices_ip = json_decode($_POST["ip"]);
            if (is_array($devices_ip)) {
                $devices_ip = array_map('trim', $devices_ip);
            } else {
                $devices_ip = trim($devices_ip);
            }
            //connect thiết bị để lấy thông tin thiết bị con
            $connectDevice = new connectDevice();
            $res =  $connectDevice->connectDevice($devices_ip);
            //Gía trị trả về là mảng json với key là ip
            $res = json_decode($res);
            //check và xóa device cũ nếu trùng ip
            $remove_device_dup = new removeDeviceDuplicate();
            /*check xem ip có phải array
            if (!is_array($devices_ip)) {
                $remove_device_dup->removeDeviceDuplicate($conn, $devices_ip);
            } else {
                //trim khoảng trắng
                $remove_device_dup->removeDeviceDuplicateArr($conn, $devices_ip);
            }*/
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
                } catch (Error $th) {
                    $err[] = self::rollBackData($conn, $th->getMessage() . " at $ip", $device_id, $status);
                } catch (Exception $th) {
                    $err[] = self::rollBackData($conn, $th->getMessage() . " at $ip", $device_id, $status);
                }
            }
            return json_encode(array("success" => $dataSuccess, "Err" => $err, "fail" => $dataFail));
        } catch (Error $th) {
            throw new  Error("Err in $currentFunction in $currentFile" . $th->getMessage());
        } catch (Exception $th) {
            throw new  Error("Err in $currentFunction in $currentFile" . $th->getMessage());
        }
    }
    function insertParentOnline($conn, $deviceName, $ip, $status)
    {
        try {
            global $devicesDefine;
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
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
        } catch (PDOException $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        } catch (Error $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
    }
    function insertDataOnline($conn,  $inventory, $device_id)
    {
        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
        global $inventoriesDefine;
        try {

            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            // Chuyển đổi mảng JSON thành mảng PHP
            $values = array();
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
        } catch (PDOException $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        } catch (Error $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        } catch (Exception $e) {
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

        //insert cha thành công nhưng con fail thì cũng commit
        if ($status === 0) {
            $conn->commit();
            return array("Err" => $mess);;
        }
        //uplại status cha là 0
        self::updateStatusParent($conn, $device_id);
    }
    public function updateStatusParent($conn, $device_id)
    {

        $currentFunction = __FUNCTION__;
        global $devicesDefine;
        try {
            $sql = "UPDATE " . $devicesDefine::TABLE_DEVICES . " SET " .
                $devicesDefine::COLUMN_DEVICES_STATUS . " = '0' WHERE "
                .   $devicesDefine::COLUMN_DEVICES_ID . "=:id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $device_id);
            $stmt->execute();
            $conn->commit();
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return array("Err" => "Err $currentFunction" . $e->getMessage());
        } catch (Error $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return array("Err" => "Err $currentFunction" . $e->getMessage());
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return array("Err" => "Err $currentFunction" . $e->getMessage());
        }
    }
}
