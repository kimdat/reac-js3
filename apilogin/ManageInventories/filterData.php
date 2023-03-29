<?php

namespace ManageInventories;

use Error;
use Exception;
use PDO;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Else_;
use Throwable;

class filterData
{
    function sqlGetData($valueSearch, $valueColumn,)
    {
        global $conn, $devicesDefine, $inventoriesDefine;
        $contrainstStatusDevice = new  \constraintStatusDevices();
        try {
            $where1 = " 1=1";

            //nếu online thì thêm điều kiện
            if (!isset($_SERVER['HTTP_FLAGOFFLINE'])) {
                $where1 = $inventoriesDefine::COLUMN_INVENTORIES_STATUS_DELETED . " <>'D'";
            }
            $params = [];

            $sqlDevices = "SELECT " . $devicesDefine::COLUMN_DEVICES_ID . ", "
                . $devicesDefine::COLUMN_DEVICES_NAME . ",
            ROW_NUMBER() OVER (ORDER BY " . $devicesDefine::COLUMN_DEVICES_NAME . ") as No
            FROM  " . $devicesDefine::TABLE_DEVICES . "
            WHERE " . $devicesDefine::COLUMN_DEVICES_STATUS . "=" . $contrainstStatusDevice::STATUS_MANAGED
                . " or " . $devicesDefine::COLUMN_DEVICES_STATUS . " =''";
            if ($valueSearch != "") {
                $valueSearch = trim($valueSearch);
            }
            foreach ($valueColumn as $column => $value) {
                //nếu là STT thì contnue
                $value = trim($value);

                if (!empty($value)) {
                    //nếu column là name thì map devices name hoặc inventories name
                    if ($column == "Name") {
                        $columnDevices = $devicesDefine::COLUMN_DEVICES_NAME;
                        $where1 .= " AND  (devices.$columnDevices Like :ParentName ) ";
                        $params[":ParentName"] = '%' . $value . '%';
                    }
                    //điều kiện filter by number
                    else if ($column == COLUMN_NO) {
                        $where1 .= " AND (devices." . COLUMN_NO . " LIKE :No )";
                        $params[":No"] = '%' . $value . '%';
                    } else {

                        switch ($column) {
                            case "InventoriesName":
                                $column = $inventoriesDefine::COLUMN_INVENTORIES_NAME;
                                break;
                            case "VID":
                                $column = $inventoriesDefine::COLUMN_INVENTORIES_VID;
                                break;
                            case "PID":
                                $column = $inventoriesDefine::COLUMN_INVENTORIES_PID;
                                break;
                            case "Serial":
                                $column =  $inventoriesDefine::COLUMN_INVENTORIES_SERIAL;
                                break;
                            case "CDESC":
                                $column =  $inventoriesDefine::COLUMN_INVENTORIES_CDESC;
                                break;
                            default:
                                // Do nothing
                        }
                        $where1 .= " AND  (inventories.$column Like :$column)";
                        $params[":$column"] = '%' . $value . '%';
                    }
                }
            }


            $sql = "  SELECT 
            devices." . $devicesDefine::COLUMN_DEVICES_ID . " as parentId, 
            devices." . $devicesDefine::COLUMN_DEVICES_NAME . " as parentName,
            devices." . COLUMN_NO . " as No,     
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_ID . " as childId,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_NAME . " as childName,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_PID . " as PID,
            inventories." .  $inventoriesDefine::COLUMN_INVENTORIES_VID . " as VID,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_SERIAL . " as Serial,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_CDESC . " as CDESC
        FROM (" .  $sqlDevices . ") devices 
        inner JOIN " . $inventoriesDefine::TABLE_INVENTORIES . " inventories 
        ON devices." . $devicesDefine::COLUMN_DEVICES_ID . " = inventories." . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . "
        WHERE $where1 
        ORDER BY devices." . COLUMN_NO . ",inventories." . $inventoriesDefine::COLUMN_INVENTORIES_ID;
            // Liên kết giá trị của các tham số với câu lệnh SQL
            $stmt = $conn->prepare($sql);
            if (sizeof($params) > 0) {
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            //$inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $stmt;
        } catch (Throwable $e) {

            $currentFunction = __FUNCTION__;
            throw new Error("Error   in $currentFunction ->" . $e->getMessage());
        }
    }

    function getData($stmt, $flagShowChild, $currentPage, $rowsPerPage, $valueSearch)
    {
        try {

            //inventories phân trang
            $filtered_inventories = [];
            //tất cả id inventories
            $all_inventories = [];
            //id cha
            $parent_row = [];
            $start = ($currentPage - 1) * $rowsPerPage;



            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $parentId = $item['parentId'];
                $parentName = $item['parentName'];
                $No = $item['No'];
                $childId = $item['childId'];
                $childName = $item['childName'];
                $pid = $item['PID'];
                $vid = $item['VID'];
                $serial = $item['Serial'];
                $cdesc = $item['CDESC'];
                $valueItem = "{$parentName}|{$childName}|" . ($pid ? $pid : "$") . "|" . ($vid ? $vid : "$") . "|" . ($serial ? $serial : "$") . "|{$cdesc}";
                $valueSearch = strtolower($valueSearch);
                $valueItem = strtolower($valueItem);

                if ($valueSearch != "" && str_contains($valueItem, $valueSearch) == false) {
                    continue;
                }

                if (!in_array($parentId, $parent_row)) {

                    $parent_row[] = $parentId;
                    $all_inventories[$parentId] = [
                        "id" => $parentId,
                        "Name" => $parentName,
                        "showChild" => $flagShowChild,
                        "No" => $No
                    ];
                }
                $all_inventories[$parentId]['children'][] = array(
                    'id' => $childId,
                    'Name' => $childName,
                    'VID' => $vid,
                    'PID' => $pid,
                    'Serial' => $serial,
                    'CDESC' => $cdesc,
                    'ParentId' => $parentId
                ) ?? [];
            } //ko có dữ liệu
            $all_inventories = array_values($all_inventories);
            $filtered_inventories = array_slice($all_inventories, $start, $rowsPerPage);
            if ($stmt->rowCount() == 0 || sizeof($filtered_inventories) == 0) {
                return array(
                    'searchapidata' => [array('statusNotFound' => true)],
                    'row_expand' => []
                );
            }
            $total_row = count($parent_row);
            $total_page = ceil($total_row / $rowsPerPage);
            $response = array(
                'searchapidata' => $filtered_inventories,
                'total_records' => $total_row,
                'total_pages' => $total_page,
                'row_expand' => array_slice($parent_row, $start, $rowsPerPage),
                'devices' => $all_inventories

            );
            return $response;
        } catch (Throwable $e) {

            $currentFunction = __FUNCTION__;
            throw new Error("Error  in $currentFunction ->" . $e->getMessage());
        }
    }
    function filterData()
    {

        try {
            $valueSearch = $_GET['valueSearch'] ?? '';
            $valueColumn = json_decode($_GET['valueColumn'] ?? '{}', true);
            $currentpage = $_GET['currentPage'];
            $rowsPerPage = $_GET['rowsPerPage'];
            $flagShowChild = $_GET['flagShowChild'];
            // Kiểm tra trước khi truy cập vào các biến $_GET và $_POST
            if (!is_string($valueSearch) || !is_array($valueColumn)) {
                echo json_encode(['Err' => 'Invalid parameters']);
                exit;
            }
            $stmt = self::sqlGetData($valueSearch, $valueColumn);

            $response = self::getData($stmt, $flagShowChild, $currentpage, $rowsPerPage, $valueSearch);


            return json_encode($response);
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFile  in $currentFunction ->" . $e->getMessage());
        }
    }
}
