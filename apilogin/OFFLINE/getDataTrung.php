<?php

namespace Offline;

use Error;
use Exception;
use PDOException;

class getDataTrung
{
    function checkDataTrung($conn, $parentNames)
    {

        $currentFunction = __FUNCTION__;
        try {

            $parentNames = array_unique(array_merge(...$parentNames));


            $sqlCheckParents = "SELECT " . COLUMN_Devices_NAME . " FROM " . TABLE_DEVICES . " WHERE " . COLUMN_STATUS . " <> 'D'AND " . COLUMN_Devices_NAME . " IN (" . implode(",", array_fill(0, count($parentNames), "?")) . ")";
            $stmtCheckParents = $conn->prepare($sqlCheckParents);
            $stmtCheckParents->execute($parentNames);

            if ($stmtCheckParents->rowCount() > 0) {
                return array_column($stmtCheckParents->fetchAll(), 'Name');
            }
            return [];
        } catch (PDOException $e) {
            throw new Error("Error in $currentFunction ()  -> " . $e->getMessage());
        } catch (Error $e) {
            throw new Error("Error in $currentFunction () " . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error in $currentFunction ()  " . $e->getMessage());
        }
    }
    function compareValues($value1, $value2)
    {
        return strcmp(strtolower($value1), strtolower($value2));
    }
    function getDataTrung()
    {
        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
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
        } catch (PDOException $e) {
            throw new Error("Error in $currentFunction in $currentFile " . $e->getMessage());
        } catch (Error $e) {
            throw new Error("Error in $currentFunction in $currentFile " . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error in $currentFunction in $currentFile " . $e->getMessage());
        }
    }
}
