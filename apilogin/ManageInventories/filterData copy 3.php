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
            WHERE " . $devicesDefine::COLUMN_DEVICES_STATUS . " NOT IN('0','D')";

            if ($valueSearch != "") {
                $valueSearch = trim($valueSearch);
                $where1 .= ' AND (Devices.No like :valueSearch or Devices.' . $devicesDefine::COLUMN_DEVICES_NAME . '  LIKE :valueSearch or inventories.'
                    . $inventoriesDefine::COLUMN_INVENTORIES_NAME . ' LIKE  :valueSearch OR inventories.'
                    .  $inventoriesDefine::COLUMN_INVENTORIES_PID . ' LIKE :valueSearch  OR inventories.'
                    // .  $inventoriesDefine::COLUMN_INVENTORIES_VID . ' LIKE :valueSearch  OR inventories.'
                    .  $inventoriesDefine::COLUMN_INVENTORIES_SERIAL . ' LIKE :valueSearch  OR inventories.'
                    .  $inventoriesDefine::COLUMN_INVENTORIES_CDESC . ' LIKE :valueSearch )';
                $params = [':valueSearch' => '%' . $valueSearch . '%'];
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
            $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $inventories;
        } catch (Throwable $e) {

            $currentFunction = __FUNCTION__;
            throw new Error("Error   in $currentFunction ->" . $e->getMessage());
        }
    }

    function getData($inventories, $flagShowChild, $currentPage, $rowsPerPage)
    {
        try {

            if (sizeof($inventories) == 0) {
                return array(
                    'searchapidata' => [array('statusNotFound' => true)],
                    'row_expand' => []
                );
            }
            //inventories phân trang
            $filtered_inventories = [];
            //tất cả id inventories
            $all_inventories = [];
            //id cha
            $parent_row = [];

            $start = ($currentPage - 1) * $rowsPerPage + 1;
            $end = $start + $rowsPerPage - 1;

            $indexParentItem = 0;
            foreach ($inventories as $item) {

                $parentId = $item['parentId'];
                $parentName = $item['parentName'];
                $No = $item['No'];
                if (!in_array($parentId, $parent_row)) {
                    $indexParentItem++;
                    $parent_row[] = $parentId;
                    $all_inventories[] = [
                        "id" => $parentId,
                        "name" => $parentName,
                        "No" => $No
                    ];
                }
                if ($indexParentItem >= $start && $indexParentItem <= $end) {
                    if (!isset($filtered_inventories[$parentId])) {
                        $filtered_inventories[$parentId] = array(
                            'id' => $parentId,
                            'Name' => $parentName,
                            'showChild' => $flagShowChild,
                            'No' => $No
                        );
                    }
                    $childId = $item['childId'];
                    $childName = $item['childName'];
                    $pid = $item['PID'];
                    $vid = $item['VID'];
                    $serial = $item['Serial'];
                    $cdesc = $item['CDESC'];
                    $filtered_inventories[$parentId]['children'][] = array(
                        'id' => $childId,
                        'Name' => $childName,
                        'VID' => $vid,
                        'PID' => $pid,
                        'Serial' => $serial,
                        'CDESC' => $cdesc,
                        'ParentId' => $parentId
                    ) ?? [];
                }
            }
            $total_row = count($parent_row);
            $total_page = ceil($total_row / $rowsPerPage);
            $response = array(
                'searchapidata' => array_values($filtered_inventories),
                'total_records' => $total_row,
                'total_pages' => $total_page,
                'row_expand' => array_slice($parent_row, $start - 1, $end),
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
            $data = self::sqlGetData($valueSearch, $valueColumn);

            $response = self::getData($data, $flagShowChild, $currentpage, $rowsPerPage);


            return json_encode($response);
        } catch (Throwable $e) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFile  in $currentFunction ->" . $e->getMessage());
        }
    }
}
