<?php

namespace ManageInventories;

use Error;
use Exception;
use PDO;
use Throwable;

class filterData
{
    function sqlGetData($valueSearch, $valueColumn,)
    {
        global $conn, $devicesDefine, $inventoriesDefine;

        try {
            $where1 = "1=1";
            $where2 = "1=1";
            $where3 = " 1=1";
            //nếu online thì thêm điều kiện
            if (!isset($_SERVER['HTTP_FLAGOFFLINE'])) {
                $where3 = $inventoriesDefine::COLUMN_INVENTORIES_STATUS_DELETED . " <>'D'";
            }
            $params = [];

            if ($valueSearch != "") {
                $valueSearch = trim($valueSearch);
                $where1 .= ' AND (Devices.' . $devicesDefine::COLUMN_DEVICES_NAME . '  LIKE :valueSearch or inventories.'
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
                        $where2 .= " AND  (devices.$columnDevices Like :ParentName ) ";
                        $params[":ParentName"] = '%' . $value . '%';
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
                        $where2 .= " AND  (inventories.$column Like :$column)";
                        $params[":$column"] = '%' . $value . '%';
                    }
                }
            }
            $conn->query("SET @rownum = 0");
            $sqlDevices = "SELECT ". $devicesDefine::COLUMN_DEVICES_ID .", ".$devicesDefine::COLUMN_DEVICES_NAME . 
            "FROM  . $devicesDefine::TABLE_DEVICES  WHERE d." .  $devicesDefine::COLUMN_DEVICES_STATUS . " NOT IN('0','D')";
            $sql = "  SELECT  DENSE_RANK() OVER (ORDER BY devices." . $devicesDefine::COLUMN_DEVICES_NAME .
                ", COALESCE(inventories." . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID .
                ", devices." . $devicesDefine::COLUMN_DEVICES_ID . " )) AS device_rank, 
            devices." . $devicesDefine::COLUMN_DEVICES_ID . " as parentId, 
            devices." . $devicesDefine::COLUMN_DEVICES_NAME . " as parentName,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_ID . " as childId,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_NAME . " as childName,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_PID . " as PID,
            inventories." .  $inventoriesDefine::COLUMN_INVENTORIES_VID . " as VID,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_SERIAL . " as Serial,
            inventories." . $inventoriesDefine::COLUMN_INVENTORIES_CDESC . " as CDESC
        FROM " . $devicesDefine::TABLE_DEVICES . " devices 
        INNER JOIN " . $inventoriesDefine::TABLE_INVENTORIES . " inventories 
        ON devices." . $devicesDefine::COLUMN_DEVICES_ID . " = inventories." . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . "
        WHERE " . $devicesDefine::COLUMN_DEVICES_STATUS . " NOT IN('0','D') AND $where1 AND $where2  AND $where3
        ORDER BY devices." . $devicesDefine::COLUMN_DEVICES_NAME . ", inventories." . $inventoriesDefine::COLUMN_INVENTORIES_ID;


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
            $stt = $start;
            foreach ($inventories as $item) {
                if ($item["device_rank"] >= $start && $item["device_rank"] <= $end) {
                    $parentId = $item['parentId'];
                    $parentName = $item['parentName'];

                    if (!isset($filtered_inventories[$parentId])) {
                        $filtered_inventories[$parentId] = array(
                            'STT' => $stt++,
                            'id' => $parentId,
                            'Name' => $parentName,
                            'showChild' => $flagShowChild
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
                if (!in_array($item["parentId"], $parent_row)) {
                    $parent_row[] = $item["parentId"];
                    $all_inventories[] = [
                        "id" => $item["parentId"],
                        "name" => $item["parentName"]
                    ];
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
