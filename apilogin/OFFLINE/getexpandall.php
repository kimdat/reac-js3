<?php

namespace Offline;

use Error;
use Exception;
use PDO;
use PDOException;

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
        } catch (Error $e) {
            throw new Error("Error  ->" . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error ->" . $e->getMessage());
        }
    }

    function sqlGetDataChildren($idToGet)
    {

        $currentFunction = __FUNCTION__;
        try {
            global $conn;
            $sql = "SELECT CONCAT('CHILD',i." . COLUMN_INVENTORIES_ID . ") as id," .
                "i." . COLUMN_INVENTORIES_NAME . " as Name," .
                COLUMN_INVENTORIES_PARENTID . " as ParentId," .

                COLUMN_INVENTORIES_PID . " as PID," .
                COLUMN_INVENTORIES_VID . " as VID," .
                COLUMN_INVENTORIES_SERIAL . " as Serial," .
                COLUMN_INVENTORIES_CDESC . " as CDESC"
                . " FROM " . TABLE_INVENTORIES .
                /* " i inner join " .
            TABLE_DEVICES . " d on i." . COLUMN_INVENTORIES_PARENTID . " = d." . COLUMN_Devices_ID .*/
                " i WHERE " . COLUMN_INVENTORIES_PARENTID . " IN (" . implode(',', array_fill(0, count($idToGet), '?')) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_values($idToGet));
            return $stmt;
        } catch (PDOException $th) {
            throw new Error("Sql Error $currentFunction () " . $th->getMessage());
        } catch (Error $th) {
            throw new Error("Error  $currentFunction ()  " . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error  $currentFunction ()  " . $th->getMessage());
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
        } catch (Error $th) {
            throw new Error("Error  $currentFunction ()" . $th->getMessage());
        } catch (Exception $th) {
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

        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
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
        } catch (Error $e) {
            throw new Error("Error in $currentFunction in $currentFile ->" . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error in $currentFunction in $currentFile ->" . $e->getMessage());
        }
    }
}
