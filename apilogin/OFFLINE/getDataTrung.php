<?php

namespace Offline;

use Error;
use Exception;
use PDOException;
use Throwable;

class getDataTrung
{
    function checkDataTrung($conn, $parentNames)
    {

        global $devicesDefine;
        try {
            $parentNames = array_unique(array_merge(...$parentNames));
            $sqlCheckParents = "SELECT " . $devicesDefine::COLUMN_DEVICES_NAME

                . " FROM " . $devicesDefine::TABLE_DEVICES . " WHERE " . $devicesDefine::COLUMN_DEVICES_STATUS . " <> 'D'   AND " . $devicesDefine::COLUMN_DEVICES_NAME . " IN (" . implode(",", array_fill(0, count($parentNames), "?")) . ")";
            $stmtCheckParents = $conn->prepare($sqlCheckParents);
            $stmtCheckParents->execute($parentNames);
            if ($stmtCheckParents->rowCount() > 0) {
                return array_column($stmtCheckParents->fetchAll(), 'Name');
            }
            return [];
        } catch (Throwable $e) {

            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFunction ()  -> " . $e->getMessage());
        }
    }
    function compareValues($value1, $value2)
    {
        return strcmp(strtolower($value1), strtolower($value2));
    }
    function getDataTrung()
    {
        global $conn;
        $fileuploadContents = $_POST['fileUploadContents'];
        //Lấy tất cả tên thiết bị của tất cả các file
        $parentNames =  json_decode($fileuploadContents);
        $sqlDataTrung = self::checkDataTrung($conn, $parentNames);
        try {
            $duplicateDevices = [];
            foreach ($parentNames as $i =>     $thisFileDevicesName) {
                $duplicateNames =  array_uintersect($thisFileDevicesName, $sqlDataTrung, 'self::compareValues');
                if (!empty($duplicateNames)) {
                    $duplicateDevices[] = ['duplicateNames' => $duplicateNames, 'index' => $i];
                }
            }
            return json_encode(array("duplicateDevices" => $duplicateDevices));
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFunction in $currentFile " . $e->getMessage());
        }
    }
}
