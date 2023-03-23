<?php

namespace Online;

use Error;

use PDO;

use Throwable;

class getAllDevices
{
    function getAllDevices()
    {
        global  $devicesDefine;

        global $conn;
        // Khởi tạo giá trị ban đầu cho biến @rownum
        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT  d." .  $devicesDefine::COLUMN_DEVICES_ID . " as id, 
            d." .  $devicesDefine::COLUMN_DEVICES_NAME . " as Name, 
            d." .  $devicesDefine::COLUMN_DEVICES_IP . " as Ip,
            d." .  $devicesDefine::COLUMN_DEVICES_TYPE . " as Device_type,
            hard." . COLUMN_DEVICETYPE_S . " as Device_type_S,
        false as showChild
        FROM " .  $devicesDefine::TABLE_DEVICES . " d 
        LEFT join " . TABLE_MAPPING_HARDWARE . " hard ON d." . $devicesDefine::COLUMN_DEVICES_TYPE . "=" . COLUMN_DEVICETYPE_H . "
        WHERE d." .  $devicesDefine::COLUMN_DEVICES_STATUS . " NOT IN('D')
        ORDER BY d." . $devicesDefine::COLUMN_DEVICES_NAME;
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($stmt->rowCount() == 0) {
                return json_encode(array(
                    'devices' => array(array('statusNotFound' => true))

                ));
            }
            $STT = 1;
            //SET STT CHO DEVICES
            $devices = array_map(function ($device) use (&$STT) {

                $device["No"] = strval($STT++);
                return $device;
            },  $devices);

            $data = array(
                "devices" =>   $devices
            );
            return json_encode($data);
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
