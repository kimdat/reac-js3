<?php

namespace ManageInventories;

use Error;
use Exception;
use PDO;
use PDOException;
use Throwable;

class getexpandall
{
    function getExpandAll()
    {
        try {
            $searchapidata = json_decode($_POST['searchapidata'], true);
            if (sizeof($searchapidata) == 0)
                return;
            $response = self::getDataChildren($searchapidata);
            return json_encode($response);
        } catch (Throwable $e) {
            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFunction ->" . $e->getMessage());
        }
    }

    function sqlGetDataChildren($idToGet)
    {
        try {
            global $conn, $inventoriesDefine;
            $where = "1=1";
            //nếu online thì thêm điều kiện
            if (!isset($_SERVER['HTTP_FLAGOFFLINE'])) {
                $where = $inventoriesDefine::COLUMN_INVENTORIES_STATUS_DELETED . "<>'D'";
            }
            $sql = "SELECT CONCAT('CHILD',i." . $inventoriesDefine::COLUMN_INVENTORIES_ID . ") as id," .
                "i." . $inventoriesDefine::COLUMN_INVENTORIES_NAME . " as Name," .
                $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . " as ParentId," .
                $inventoriesDefine::COLUMN_INVENTORIES_PID . " as PID," .
                $inventoriesDefine::COLUMN_INVENTORIES_VID . " as VID," .
                $inventoriesDefine::COLUMN_INVENTORIES_SERIAL . " as Serial," .
                $inventoriesDefine::COLUMN_INVENTORIES_CDESC . " as CDESC"
                . " FROM " . $inventoriesDefine::TABLE_INVENTORIES .
                /* " i inner join " .
            TABLE_DEVICES . " d on i." . COLUMN_INVENTORIES_PARENTID . " = d." . COLUMN_Devices_ID .*/
                " i WHERE  $where and " . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . " IN (" . implode(',', array_fill(0, count($idToGet), '?')) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_values($idToGet));
            return $stmt;
        } catch (Throwable $th) {
            $currentFunction = __FUNCTION__;
            throw new Error("Error $currentFunction () " . $th->getMessage());
        }
    }

    //Gán giá trị mới cho children
    function assignNewDataChildren($stmt, $searchapidata)
    {
        $currentFunction = __FUNCTION__;
        try {

            $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Lưu trữ các phần tử trong $searchapidata theo id
            $itemsById = [];
            foreach ($searchapidata as $item) {
                $item['showChild'] = true;
                $itemsById[$item['id']] = $item;
            }
            foreach ($inventory as $item) {
                $parentId = $item['ParentId'];
                if (isset($itemsById[$parentId])) {
                    $parent = &$itemsById[$parentId];
                    if (!isset($parent['children'])) {
                        $parent['children'] = [];
                    }
                    $parent['children'][] = $item;
                }
            }
            return array_values($itemsById);
        } catch (Throwable $th) {
            throw new Error("Error  $currentFunction ()" . $th->getMessage());
        }
    }

    // Hàm kiểm tra xem một phần tử có key "children" hay không
    function hasNoChildren($item)
    {
        return !isset($item['children']);
    }
    function getDataChildren($searchapidata)
    {
        try {
            // Lấy các id của searchData không có key children,phải thêm self do class gọi hàm chính nó
            $searchapidataFilter = array_filter($searchapidata, 'self::hasNoChildren');
            $searchDataIds = array_column($searchapidataFilter, 'id');
            if (!empty($searchDataIds) > 0) {
                $stmt = self::sqlGetDataChildren($searchDataIds);
                $response = self::assignNewDataChildren($stmt, $searchapidata);
            } else {
                return $searchapidata;
            }
            return $response;
        } catch (Throwable $e) {
            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFunction  ->" . $e->getMessage());
        }
    }
}
