<?php

namespace Online;

use Error;
use Exception;
use PDOException;
use Throwable;

//xóa những device trùng(update status D)
class  removeDeviceDuplicate
{
    //xóa 1 ip
    function removeDeviceDuplicate($conn, $device_ip)
    {
        global $devicesDefine;

        try {
            $sql = "UPDATE " . $devicesDefine::TABLE_DEVICES . " SET "
                . $devicesDefine::COLUMN_DEVICES_STATUS . " = 'D', "
                . $devicesDefine::COLUMN_DEVICES_TIME_DELETED . " = NOW() WHERE STATUS <>'D' AND "
                . $devicesDefine::COLUMN_DEVICES_IP . "=:ip";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':ip', $device_ip);
            $stmt->execute();
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
    }
    //xoá array ip
    function removeDeviceDuplicateArr($conn, $devices_ip)
    {
        global $devicesDefine;

        try {
            $placeholders = implode(',', array_fill(0, count($devices_ip), '?'));
            $sql = "UPDATE " . $devicesDefine::TABLE_DEVICES . " SET "
                . $devicesDefine::COLUMN_DEVICES_STATUS . " = 'D', "
                . $devicesDefine::COLUMN_DEVICES_TIME_DELETED . " = NOW() WHERE STATUS <>'D' AND "
                . $devicesDefine::COLUMN_DEVICES_IP  . " IN ($placeholders)";
            $stmt = $conn->prepare($sql);

            $stmt->execute($devices_ip);
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
        }
    }
}
