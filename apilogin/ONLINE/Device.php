<?php

namespace Online;

use DeviceStatus;
use Error;

use PDO;

use Throwable;
use Online\createOnline;
use Online\connectDevice;

class Device
{
    public static function getAllDevices()
    {
        global $devicesDefine;
        global $conn;
        // Khởi tạo giá trị ban đầu cho biến @rownum
        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT * FROM " . $devicesDefine::TABLE_DEVICES . " WHERE " . $devicesDefine::COLUMN_DEVICES_STATUS . " <>'D'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array("devices" => $devices));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }

    public static function getDeviceById($id)
    {
        global $devicesDefine;
        global $conn;

        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT * FROM " . $devicesDefine::TABLE_DEVICES
                . " WHERE " . $devicesDefine::COLUMN_DEVICES_ID . " = :id LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array('id' => $id));
            $device = $stmt->fetch();

            if ($stmt->rowCount() == 0) {
                return json_encode(array('device' => (object)[]));
            }

            return json_encode(array('device' => $device));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }

    public static function getDeviceByDeviceName($name)
    {
        global $devicesDefine;
        global $conn;

        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT * FROM " . $devicesDefine::TABLE_DEVICES
                . " WHERE " . $devicesDefine::COLUMN_DEVICES_NAME . " = :name";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array('name' => $name));
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array('devices' => $devices));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }

    public static function getDeviceByDeviceType($type)
    {
        global $devicesDefine;
        global $conn;

        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT * FROM " . $devicesDefine::TABLE_DEVICES
                . " WHERE " . $devicesDefine::COLUMN_DEVICES_TYPE . " = :type";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array('type' => $type));
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array('devices' => $devices));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }

    public static function getAllDeviceStatus()
    {
        global $conn;

        try {
            $sql = "SELECT * FROM " . DeviceStatus::TABLE_DEVICE_STATUS;
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array('status' => $devices));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }

    public static function addDevice1($device, $status = 0)
    {
        global $conn;
        global $devicesDefine;


        try {
            $conn->query("SET @rownum = 0");
            $sql = "INSERT INTO " . $devicesDefine::TABLE_DEVICES . " ("
                . $devicesDefine::COLUMN_DEVICES_ID . ", "
                . $devicesDefine::COLUMN_DEVICES_TYPE . ", "
                . $devicesDefine::COLUMN_DEVICES_NAME . ","
                . $devicesDefine::COLUMN_DEVICES_IP . ", "
                . $devicesDefine::COLUMN_DEVICES_REGION_ID . ", "
                . $devicesDefine::COLUMN_DEVICES_PROVINCE_ID . ", "
                . "`" . $devicesDefine::COLUMN_DEVICES_LONG . "`" . ", "
                . $devicesDefine::COLUMN_DEVICES_LAT . ", "
                . $devicesDefine::COLUMN_DEVICES_ADDRESS . ", "
                . $devicesDefine::COLUMN_DEVICES_STATUS
                . ") VALUES (:Id, :Device_Type, :DeviceName, :Ip, :region_id, :province_id, :long, :lat, :address,:" . $devicesDefine::COLUMN_DEVICES_STATUS . ");";
            $stmt = $conn->prepare($sql);

            $timestamp = time();
            $random_string = uniqid('rd', true);
            $device_id = $timestamp . $random_string;
            $stmt->execute(
                array(
                    $devicesDefine::COLUMN_DEVICES_ID => $device_id,
                    $devicesDefine::COLUMN_DEVICES_TYPE => $device[$devicesDefine::COLUMN_DEVICES_TYPE],
                    $devicesDefine::COLUMN_DEVICES_NAME => $device[$devicesDefine::COLUMN_DEVICES_NAME],
                    $devicesDefine::COLUMN_DEVICES_IP => $device[$devicesDefine::COLUMN_DEVICES_IP],
                    $devicesDefine::COLUMN_DEVICES_REGION_ID => $device[$devicesDefine::COLUMN_DEVICES_REGION_ID],
                    $devicesDefine::COLUMN_DEVICES_PROVINCE_ID => $device[$devicesDefine::COLUMN_DEVICES_PROVINCE_ID],
                    $devicesDefine::COLUMN_DEVICES_LONG => $device[$devicesDefine::COLUMN_DEVICES_LONG],
                    $devicesDefine::COLUMN_DEVICES_LAT => $device[$devicesDefine::COLUMN_DEVICES_LAT],
                    $devicesDefine::COLUMN_DEVICES_ADDRESS => $device[$devicesDefine::COLUMN_DEVICES_ADDRESS],
                    $devicesDefine::COLUMN_DEVICES_STATUS => $status,
                )
            );

            return $device_id;
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
    function addDevice($devices_list, $flagUpDate = false)
    {

        global $conn;
        $status = 0;
        $device_id = null;
        $deviceName = "";
        global $devicesDefine;
        try {
            $createOnline = new createOnline();

            $devices_list = $createOnline->trimParameter($devices_list);


            //Mappindhardware
            $devices_list =  $createOnline->mappingHardware($devices_list);

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
                    $ip = $device->{$devicesDefine::COLUMN_DEVICES_IP};

                    if (trim($ip) == "") {
                        throw new Error("No have ip");
                    }

                    $dataInventory = $inventory->$ip;
                    $deviceName = $device->{$devicesDefine::COLUMN_DEVICES_NAME};

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
                    $device_id = self::addDevice1(get_object_vars($device), $status);

                    //Nếu không lỗi khi connect thì insertData
                    $children = [];
                    $step = "insertData";
                    if ($status == 1) {
                        $children =  $createOnline->insertDataOnline($conn,  $dataInventory, $device_id);
                        $dataSuccess[] = array("ip" => $ip, "id" => $device_id, "Name" => $deviceName, "children" => $children);
                    } else {
                        $dataFail[] = array("ip" => $ip, "id" => $device_id, "Name" => $deviceName, "children" => $children);
                    }
                    $conn->commit();
                } catch (Throwable $th) {
                    if ($ip == null || $ip == "") {
                        $err[] = array($ip => $th->getMessage());
                    } else {
                        $mess =  $createOnline->rollBackData($conn, $th->getMessage() . " at step $step at $ip", $device_id, $status);
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
    public static function deleteDevices($deviceIdList)
    {
        global $conn;
        global $devicesDefine;
        $deviceIdList = array_map(function ($string) {
            return "'" . $string . "'";
        }, $deviceIdList);
        try {
            $sql = "UPDATE "
                . $devicesDefine::TABLE_DEVICES
                . " SET " . $devicesDefine::COLUMN_DEVICES_STATUS . " = 'D'"
                . " WHERE " . $devicesDefine::COLUMN_DEVICES_ID
                . " IN (" . implode(",", $deviceIdList) . ")";
            var_dump($sql);
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            return json_encode(array('status' => true));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
