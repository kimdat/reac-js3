<?php

namespace Offline;

use Error;
use Exception;
use PDO;
use PDOException;

class getDataChildren
{
    function sqlGetDataChildren($idToGet)
    {

        $currentFunction = __FUNCTION__;
        try {
            global $conn;
            $sql = "SELECT CONCAT('CHILD'," . COLUMN_INVENTORIES_ID . ") as id," .
                COLUMN_INVENTORIES_NAME . " as Name," .
                COLUMN_INVENTORIES_PARENTID . " as ParentId," .
                COLUMN_INVENTORIES_PID . " as PID," .
                COLUMN_INVENTORIES_VID . " as VID," .
                COLUMN_INVENTORIES_SERIAL . " as Serial," .
                COLUMN_INVENTORIES_CDESC . " as CDESC"
                . " FROM " . TABLE_INVENTORIES . " 
        WHERE " . COLUMN_INVENTORIES_PARENTID . " IN (" . implode(',', array_fill(0, count($idToGet), '?')) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_values($idToGet));
            return $stmt;
        } catch (PDOException $th) {
            throw new Error("Sql Error $currentFunction () " . $th->getMessage());
        } catch (Error $th) {
            throw new Error("Error  $currentFunction () " . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error  $currentFunction ()  " . $th->getMessage());
        }
    }
    //Gán giá trị mới cho children
    function assignNewDataChildren($stmt, $children)
    {

        $currentFunction = __FUNCTION__;
        try {
            $childData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($childData as $row) {
                $parentId = $row[COLUMN_INVENTORIES_PARENTID];
                $children[$parentId] = $children[$parentId] ?? [];
                $children[$parentId][] = $row;
            }

            return $children;
        } catch (Error $th) {
            throw new Error("Error  $currentFunction ()" . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error  $currentFunction ()" . $th->getMessage());
        }
    }
    //Gán giá trị  mới cho searchapidata và  children
    function assignNewSearchApiData($searchapidata, $children)
    {

        $currentFunction = __FUNCTION__;
        try {
            $tempData = [];
            foreach ($searchapidata as $key => $currentSearchData) {
                $currentSearchData['showChild'] = true;
                $tempData[] = $currentSearchData;
                //Nếu row kế tiếp là con thì bỏ qua 
                if (isset($searchapidata[$key + 1][COLUMN_INVENTORIES_PARENTID]) || isset($searchapidata[$key][COLUMN_INVENTORIES_PARENTID])) {
                    continue;
                }
                if (isset($children[$currentSearchData[COLUMN_Devices_ID]])) {
                    //Lấy mảng con để thêm vào
                    $tempData = array_merge($tempData, $children[$currentSearchData[COLUMN_Devices_ID]]);
                }
            }
            $searchapidata = $tempData;
            return array(
                'searchapidata' =>  $searchapidata,
                'children' => $children

            );
        } catch (Error $th) {
            throw new Error("Error  $currentFunction ()   " . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error  $currentFunction ()  " . $th->getMessage());
        }
    }
    //
    function getDataChildren($searchapidata, $children)
    {


        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
        try {

            // Lấy các id của searchData
            $searchDataIds = array_column($searchapidata, 'id');
            // Lấy các key của children
            $childrenKeys = array_keys($children);
            $diffKeys = array_diff($searchDataIds, $childrenKeys);
            if (!empty($diffKeys) > 0) {
                $stmt = self::sqlGetDataChildren($diffKeys);
                $children = self::assignNewDataChildren($stmt, $children);
            }
            $response = self::assignNewSearchApiData($searchapidata, $children);

            return $response;
        } catch (Error $e) {
            throw new Error("Error in $currentFunction in $currentFile ->" . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error in $currentFunction in $currentFile ->" . $e->getMessage());
        }
    }
}
