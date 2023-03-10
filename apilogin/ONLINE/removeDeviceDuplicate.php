<?php

namespace Online;

use Error;
use Exception;
use PDOException;
//xóa những device trùng(update status D)
class  removeDeviceDuplicate
{
    //xóa 1 ip
    function removeDeviceDuplicate($conn, $device_ip)
    {
        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
        try {
            $sql = "UPDATE " . TABLE_DEVICES_ONLINE . " SET " . COLUMN_DEVICES_ONLINE_STATUS . " = 'D', " . COLUMN_DEVICES_ONLINE_TIME_DELETED . " = NOW() WHERE STATUS <>'D' AND " . COLUMN_DEVICES_ONLINE_IP . "=:ip";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':ip', $device_ip);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        } catch (Error $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
    }
    //xoá array ip
    function removeDeviceDuplicateArr($conn, $devices_ip)
    {
        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
        try {
            $placeholders = implode(',', array_fill(0, count($devices_ip), '?'));
            $sql = "UPDATE " . TABLE_DEVICES_ONLINE . " SET " . COLUMN_DEVICES_ONLINE_STATUS
                . " = 'D', " . COLUMN_DEVICES_ONLINE_TIME_DELETED
                . " = NOW() WHERE STATUS <>'D' AND " . COLUMN_DEVICES_ONLINE_IP . " IN ($placeholders)";
            $stmt = $conn->prepare($sql);

            $stmt->execute($devices_ip);
        } catch (PDOException $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        } catch (Error $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
    }
}
