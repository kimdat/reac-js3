<?php

namespace Online;

use Error;
use Exception;
use PDOException;
use Online\connectDevice;

class createOnline
{

    function createOnline()
    {

        global $conn;
        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
        try {
            // $ipDevice = json_decode($_POST["inputs"]);
            $ip = trim($_POST["ip"]);
            $deviceName = trim($_POST["deviceName"]);
            //connect thiết bị để lấy thông tin thiết bị con

            $connectDevice = new connectDevice();
            $res =  $connectDevice->connectDevice($ip);
            //Gía trị trả về là mảng json với key là ip
            $res = json_decode($res);
            $inventory = $res->$ip;

            $status = 1;

            //nếu lỗi thì status 0, không thì 1
            if (isset($inventory[0]->Err)) {
                $status = 0;
            }

            $device_id = self::insertParentOnline($conn, $deviceName, $ip, $status);

            //Nếu không lỗi khi connect thì insertData
            if ($status == 1) {
                self::insertDataOnline($conn, $inventory, $device_id);
            }
        } catch (Error $th) {
            throw new Error("Error $currentFunction in $currentFile ." . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error $currentFunction in $currentFile ." . $th->getMessage());
        }
    }
    function insertParentOnline($conn, $deviceName, $ip, $status)
    {
        try {
            $conn->beginTransaction();
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            $timestamp = time();
            $random_string = uniqid('rd', true);
            $device_id = $timestamp . $random_string;
            //insert cha
            $sqlParent = "INSERT INTO " . TABLE_DEVICES_ONLINE
                . " (" . COLUMN_DEVICES_ONLINE_NAME
                . "," . COLUMN_DEVICES_ONLINE_IP
                . "," . COLUMN_DEVICES_ONLINE_ID
                . "," . COLUMN_DEVICES_ONLINE_STATUS
                . ") VALUES (?,?,?,?)";
            $stmtParent = $conn->prepare($sqlParent);
            $stmtParent->bindParam(1, $deviceName);
            $stmtParent->bindParam(2, $ip);
            $stmtParent->bindParam(3, $device_id);
            $stmtParent->bindParam(4, $status);
            $stmtParent->execute();
            $conn->commit();
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
        try {
            $conn->beginTransaction();
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            // Chuyển đổi mảng JSON thành mảng PHP
            $values = array();
            foreach ($inventory as $item) {
                $values[] = array(
                    'name' => $item->NAME,
                    'descr' => $item->DESCR,
                    'pid' => $item->PID,
                    'vid' => $item->VID,
                    'sn' => $item->SN,
                    'ParentId' => $device_id
                );
            }
            $placeholders = array_fill(0, count($values), "(?, ?, ?, ?, ?,?)");
            $values_flat = array();
            foreach ($values as $row) {
                $values_flat = array_merge($values_flat, array_values($row));
            }
            $sql = "INSERT INTO " . TABLE_INVENTORIES_ONLINE . " (" . COLUMN_INVENTORIES_ONLINE_NAME . ", "
                . COLUMN_INVENTORIES_ONLINE_CDESC . ","
                . COLUMN_INVENTORIES_ONLINE_PID . ","
                . COLUMN_INVENTORIES_ONLINE_VID . ","
                . COLUMN_INVENTORIES_ONLINE_SERIAL . ","
                . COLUMN_INVENTORIES_ONLINE_PARENTID .
                ") VALUES " . implode(',', $placeholders);
            $stmt = $conn->prepare($sql);
            $stmt->execute($values_flat);
            $conn->commit();
        } catch (PDOException $e) {
            self::rollBackData($conn, "Error $currentFunction in $currentFile ." . $e->getMessage(), $device_id);
        } catch (Error $e) {
            self::rollBackData($conn, "Error $currentFunction in $currentFile ." . $e->getMessage(), $device_id);
        } catch (Exception $e) {
            self::rollBackData($conn, "Error $currentFunction in $currentFile ." . $e->getMessage(), $device_id);
        }
        return;
    }
    //rollback khi lỗi
    public static function rollBackData($conn, $mess, $device_id)
    {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw new Error($mess);
    }

    //Updatelaistatusparen la 0
}
