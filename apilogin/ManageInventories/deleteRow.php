<?php

namespace ManageInventories;

use Error;
use Exception;
use PDOException;
use Throwable;

class deleteRow
{
    function deleteRow()
    {

        global $conn, $devicesDefine;
        // Khởi tạo giá trị ban đầu cho biến @rownum
        try {

            $rowsId = json_decode($_GET['rowsId'] ?? '{}', true);
            $placeholders = implode(',', array_fill(0, count($rowsId), '?'));
            $sql = "UPDATE " . $devicesDefine::TABLE_DEVICES .
                " SET " . $devicesDefine::COLUMN_DEVICES_STATUS . " = 'D' 
            WHERE " . $devicesDefine::COLUMN_DEVICES_ID . " IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($rowsId);

            return "";
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFile in $currentFunction->" . $th->getMessage());
        }
    }
}
