<?php
// Load the PHPExcel classes

namespace Online;

use Error;

use Throwable;



class INSTANTANEOUSCHECK
{
    function updateInventories()
    {

        $currentFile = basename(__FILE__);

        try {
            $method = $_SERVER['REQUEST_METHOD'];
            switch ($method) {
                case "POST":
                    return self::doPost();
                    break;
                case "GET":
                    return self::doGet();
                    break;
                default:
                    return self::doGet();
                    break;
            }
        } catch (Throwable $th) {
            $currentFunction = __FUNCTION__;
            throw new Error("Err in $currentFunction in $currentFile ->" . $th->getMessage());
        }
    }
    function doGet()
    {

        $action = $_REQUEST["action"];
        return $action;
    }
    function doPost()
    {
        $action = $_REQUEST["action"];
        $action = strtolower($action);
        switch ($action) {
            case "update":
                return self::Update();
                break;
            default:
                return "";
                break;
        }
        return $action;
    }
    function Update()
    {
        global $conn;
        $childData = json_decode($_POST["child"]);
        try {
            $conn->beginTransaction();
            $idParens = json_decode($_POST["idParents"]);
            self::deleteParentId($conn, $idParens);
            self::insertData($conn, $childData);
            $conn->commit();
        } catch (\Throwable $th) {
            $currentFile = basename(__FILE__);
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw new Error("Error in $currentFile " . $th->getMessage());
        }

        return "success";
    }
    function deleteParentId($conn, $idParents)
    {
        try {
            global $inventoriesDefine;
            $placeholders = implode(',', array_fill(0, count($idParents), '?'));

            $sql = "UPDATE " . $inventoriesDefine::TABLE_INVENTORIES . " SET "
                . $inventoriesDefine::COLUMN_INVENTORIES_STATUS_DELETED . " = 'D', 
            " . $inventoriesDefine::COLUMN_INVENTORIES_DELETE_AT . " = NOW() 
            WHERE " . $inventoriesDefine::COLUMN_INVENTORIES_STATUS_DELETED . " <>'D' AND " . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . " IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute($idParents);
        } catch (Throwable $e) {
            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFunction  " . $e->getMessage());
        }
    }

    function insertData($conn, $data)
    {
        try {
            global $inventoriesDefine;

            //$device_id = $conn->lastInsertId();
            // Chuyển đổi mảng JSON thành mảng PHP
            $values = array();
            foreach ($data as $item) {
                $values[] = array(
                    'name' => $item->Name,
                    'descr' => $item->CDESC,
                    'pid' => $item->PID,
                    'vid' => $item->VID,
                    'sn' => $item->Serial,
                    'ParentId' => $item->parentId
                );
            }
            $placeholders = array_fill(0, count($values), "(?, ?, ?, ?, ?,?)");
            $values_flat = array();
            foreach ($values as $row) {
                $values_flat = array_merge($values_flat, array_values($row));
            }
            $sql = "INSERT INTO " . $inventoriesDefine::TABLE_INVENTORIES
                . " (" . $inventoriesDefine::COLUMN_INVENTORIES_NAME . ", "
                . $inventoriesDefine::COLUMN_INVENTORIES_CDESC . ","
                . $inventoriesDefine::COLUMN_INVENTORIES_PID . ","
                . $inventoriesDefine::COLUMN_INVENTORIES_VID . ","
                . $inventoriesDefine::COLUMN_INVENTORIES_SERIAL . ","
                . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID .
                ") VALUES " . implode(',', $placeholders);
            $stmt = $conn->prepare($sql);
            $stmt->execute($values_flat);
        } catch (Throwable $e) {
            $currentFunction = __FUNCTION__;
            throw new Error("Error $currentFunction ." . $e->getMessage());
        }
    }
}
