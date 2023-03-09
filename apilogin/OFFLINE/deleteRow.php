<?php

namespace Offline;

use Error;
use Exception;
use PDOException;

class deleteRow
{
    function deleteRow()
    {
        $currentFile = basename(__FILE__);
        global $conn;
        // Khởi tạo giá trị ban đầu cho biến @rownum
        try {

            $rowsId = json_decode($_GET['rowsId'] ?? '{}', true);
            $placeholders = implode(',', array_fill(0, count($rowsId), '?'));
            $sql = "UPDATE " . TABLE_DEVICES . " SET status = 'D' WHERE " . COLUMN_Devices_ID . " IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($rowsId);

            return "";
        } catch (PDOException $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        } catch (Error $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
