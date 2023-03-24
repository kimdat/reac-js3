<?php

namespace Online;

use DeviceStatus;
use Error;
use Exception;
use PDO;

use Throwable;

class Device
{
    protected static function getGUID()
    {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((float)microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid =
                substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
            return $uuid;
        }
    }

    protected static function queryBuilder($query, $conditions)
    {
        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        return $query;
    }

    public static function getAllDevices()
    {
        global $devicesDefine;
        global $conn;
        // Khởi tạo giá trị ban đầu cho biến @rownum
        try {
            $conn->query("SET @rownum = 0");
            $sql = "SELECT * FROM " . $devicesDefine::TABLE_DEVICES
                . " WHERE " . $devicesDefine::COLUMN_DEVICES_STATUS . " <>'D'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array("devices" => $devices));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }

    public static function getFilteredDevices($filters)
    {
        global $devicesDefine;
        global $conn;
        $paginationFields = array("currentPage", "rowsPerPage");
        $selectFields = array(
            $devicesDefine::COLUMN_DEVICES_STATUS,
            $devicesDefine::COLUMN_DEVICES_TYPE,
            $devicesDefine::COLUMN_DEVICES_REGION_ID,
            $devicesDefine::COLUMN_DEVICES_PROVINCE_ID
        );
        try {
            $deletedRowCondition = $devicesDefine::COLUMN_DEVICES_STATUS . " <>'D'";

            $conditions = array();
            array_push($conditions, $deletedRowCondition);

            foreach ($filters as $name => $value) {
                if ($value != '' && !in_array($name, $paginationFields)) {
                    if (in_array($name, $selectFields)) {
                        $conditions[] = "`$name` = '$value'";
                    } else {
                        $conditions[] = "`$name` LIKE '%$value%'";
                    }
                }
            }

            //get row count without pagination
            $rowCoutnWithoutPaginationQuery =
                Device::queryBuilder("SELECT count(*) FROM " . $devicesDefine::TABLE_DEVICES, $conditions);
            $stmt = $conn->prepare($rowCoutnWithoutPaginationQuery);
            $stmt->execute();
            $rowCountWithoutPagination = $stmt->fetchColumn();

            //building query
            $query = Device::queryBuilder("SELECT * FROM " . $devicesDefine::TABLE_DEVICES, $conditions);

            //add pagination to query
            if (isset($filters["currentPage"]) && isset($filters["rowsPerPage"])) {
                $currentPage = $filters["currentPage"];
                $rowsPerPage = $filters["rowsPerPage"];
                $offset = ($currentPage - 1) * $rowsPerPage;
                $pagination = " LIMIT $rowsPerPage OFFSET $offset";
                $query .= $pagination;
            }

            //execute query and get the result
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return json_encode(array(
                "devices" => $devices,
                "totalRowCount" => $rowCountWithoutPagination,
            ));
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
    public static function mappingHardware($device)
    {
        //nếu không có ip thì continue
        try {

            $device["device_type_S"] = self::getDataHardware($device["Device_Type"]);
            $device["username"] = 'epnm';
            $device["password"] = 'epnm@890!';
            return $device;
        } catch (\Throwable $th) {
        }


        return $device;
    }
    public static function getDataHardware($deviceType)
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
    public static function addDevice($device)
    {
        global $conn;
        global $devicesDefine;




        try {

            $device = self::mappingHardware($device);

            $connectDevice = new connectDevice();

            $res =  $connectDevice->connectDevice($device);
            return json_encode($res);

            $fieldNames = [
                $devicesDefine::COLUMN_DEVICES_ID,
                $devicesDefine::COLUMN_DEVICES_TYPE,
                $devicesDefine::COLUMN_DEVICES_NAME,
                $devicesDefine::COLUMN_DEVICES_IP,
                $devicesDefine::COLUMN_DEVICES_REGION_ID,
                $devicesDefine::COLUMN_DEVICES_PROVINCE_ID,
                $devicesDefine::COLUMN_DEVICES_LONG,
                $devicesDefine::COLUMN_DEVICES_LAT,
                $devicesDefine::COLUMN_DEVICES_ADDRESS
            ];
            $conn->query("SET @rownum = 0");
            $query = "INSERT INTO " . $devicesDefine::TABLE_DEVICES
                . "(" . implode(",", array_map(fn ($fieldName): string => "`$fieldName`", $fieldNames)) . ")"
                . " VALUES "
                . "(" . implode(',', array_map(fn ($fieldName): string => ":$fieldName", $fieldNames)) . ")";

            $stmt = $conn->prepare($query);

            $executeArray = array_reduce($fieldNames, function ($result, $fieldName) use ($devicesDefine, $device) {
                if ($fieldName == $devicesDefine::COLUMN_DEVICES_ID) {
                    $GUID = Device::getGUID();
                    $result[$fieldName] = $GUID;
                } else {
                    $result[$fieldName] = $device[$fieldName];
                }
                return $result;
            }, array());

            $stmt->execute($executeArray);

            return json_encode(array('status' => true));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }

    public static function modifyDevice($id, $device)
    {
        global $conn;
        global $devicesDefine;
        try {
            //check device existence
            $query = "SELECT COUNT(1) FROM "
                . $devicesDefine::TABLE_DEVICES . " WHERE "
                . $devicesDefine::COLUMN_DEVICES_ID . " = '$id'";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $exist = $stmt->fetchColumn();

            if ($exist) {
                $fieldNames = [
                    $devicesDefine::COLUMN_DEVICES_TYPE,
                    $devicesDefine::COLUMN_DEVICES_NAME,
                    $devicesDefine::COLUMN_DEVICES_IP,
                    $devicesDefine::COLUMN_DEVICES_REGION_ID,
                    $devicesDefine::COLUMN_DEVICES_PROVINCE_ID,
                    $devicesDefine::COLUMN_DEVICES_LONG,
                    $devicesDefine::COLUMN_DEVICES_LAT,
                    $devicesDefine::COLUMN_DEVICES_ADDRESS
                ];
                $conn->query("SET @rownum = 0");

                $assignedArray = array_map(fn ($fieldName) => "`$fieldName` = '$device[$fieldName]'", $fieldNames);

                $query = "UPDATE " . $devicesDefine::TABLE_DEVICES
                    . " SET " . implode(",", $assignedArray)
                    . " WHERE " . $devicesDefine::COLUMN_DEVICES_ID . " = '$id'";
                $stmt = $conn->prepare($query);
                $exist = $stmt->execute();
                return true;
            } else {
                return false;
            }
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
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
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            return json_encode(array('status' => true));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
