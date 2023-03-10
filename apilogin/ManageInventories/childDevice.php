<?php

namespace ManageInventories;

use Error;
use Exception;
use PDO;
use PDOException;

class childDevice
{

    function getChildDevice()
    {
        $currentFile = basename(__FILE__);
        global $conn, $inventoriesDefine;
        try {
            $id = $_GET['id'];
            $sql = "SELECT CONCAT('CHILD'," .
                $inventoriesDefine::COLUMN_INVENTORIES_ID . ") as id," .
                $inventoriesDefine::COLUMN_INVENTORIES_NAME . " as Name," .
                $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . " as ParentId," .
                $inventoriesDefine::COLUMN_INVENTORIES_PID . " as PID," .
                $inventoriesDefine::COLUMN_INVENTORIES_VID . " as VID," .
                $inventoriesDefine::COLUMN_INVENTORIES_SERIAL . " as Serial," .
                $inventoriesDefine::COLUMN_INVENTORIES_CDESC . " as CDESC "
                . "FROM " .   $inventoriesDefine::TABLE_INVENTORIES .
                "  where " .  $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . " =:id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($inventories);
        } catch (PDOException $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        } catch (Error $th) {
            throw new Error("Error in $currentFile ->" . $th->getMessage());
        }
    }
}
