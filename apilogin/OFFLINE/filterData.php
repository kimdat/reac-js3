<?php

namespace Offline;

use Error;
use Exception;
use PDO;

class filterData
{
    function sqlGetData($valueSearch, $valueColumn,)
    {
        global $conn;
        $currentFunction = __FUNCTION__;
        try {
            $where1 = "1=1";
            $where2 = "1=1";
            $params = [];
            if ($valueSearch != "") {
                $valueSearch = trim($valueSearch);
                $where1 .= ' AND (Devices.' . COLUMN_Devices_NAME . '  LIKE :valueSearch or inventories.'
                    . COLUMN_INVENTORIES_NAME . ' LIKE  :valueSearch OR inventories.'
                    . COLUMN_INVENTORIES_PID . ' LIKE :valueSearch  OR inventories.'
                    . COLUMN_INVENTORIES_VID . ' LIKE :valueSearch  OR inventories.'
                    . COLUMN_INVENTORIES_SERIAL . ' LIKE :valueSearch  OR inventories.'
                    . COLUMN_INVENTORIES_CDESC . ' LIKE :valueSearch )';
                $params = [':valueSearch' => '%' . $valueSearch . '%'];
            }
            foreach ($valueColumn as $column => $value) {
                $value = trim($value);
                if (!empty($value)) {
                    //nếu column là name thì map devices name hoặc inventories name
                    if ($column == "Name") {
                        $columnDevices = COLUMN_Devices_NAME;
                        $columnInventories = COLUMN_INVENTORIES_NAME;
                        $where2 .= " AND  (devices.$columnDevices Like :Name or  inventories.$columnInventories Like :Name) ";
                        $params[":Name"] = '%' . $value . '%';
                    } else {
                        switch ($column) {
                            case "VID":
                                $column = COLUMN_INVENTORIES_VID;
                                break;
                            case "PID":
                                $column = COLUMN_INVENTORIES_PID;
                                break;
                            case "Serial":
                                $column = COLUMN_INVENTORIES_SERIAL;
                                break;
                            case "CDESC":
                                $column = COLUMN_INVENTORIES_CDESC;
                                break;
                            default:
                                // Do nothing
                        }
                        $where2 .= " AND  (inventories.$column Like :$column)";
                        $params[":$column"] = '%' . $value . '%';
                    }
                }
            }

            $sql = "  SELECT  DENSE_RANK() OVER (ORDER BY devices." . COLUMN_Devices_NAME . ", COALESCE(inventories." . COLUMN_INVENTORIES_PARENTID .
                ", devices." . COLUMN_Devices_ID . " )) AS device_rank, 
            devices." . COLUMN_Devices_ID . " as parentId, 
            devices." . COLUMN_Devices_NAME . " as parentName,
            inventories." . COLUMN_INVENTORIES_ID . " as childId,
            inventories." . COLUMN_INVENTORIES_NAME . " as childName,
            inventories." . COLUMN_INVENTORIES_PID . " as PID,
            inventories." . COLUMN_INVENTORIES_VID . " as VID,
            inventories." . COLUMN_INVENTORIES_SERIAL . " as Serial,
            inventories." . COLUMN_INVENTORIES_CDESC . " as CDESC
        FROM " . TABLE_DEVICES . " devices 
        INNER JOIN " . TABLE_INVENTORIES . " inventories 
        ON devices." . COLUMN_Devices_ID . " = inventories." . COLUMN_INVENTORIES_PARENTID . "
        WHERE " . COLUMN_STATUS . " <> 'D' AND $where1 AND $where2 
        ORDER BY devices." . COLUMN_Devices_NAME . ", inventories." . COLUMN_INVENTORIES_ID;
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
        } catch (Error $th) {
            throw new Error("Error  $currentFunction ()  " . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error  $currentFunction () " . $th->getMessage());
        }
    }

    function getData($inventories, $flagShowChild, $currentPage, $rowsPerPage)
    {
        $currentFunction = __FUNCTION__;
        if (sizeof($inventories) == 0) {
            return array(
                'searchapidata' => [array('statusNotFound' => true)],

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

        foreach ($inventories as $item) {
            if ($item["device_rank"] >= $start && $item["device_rank"] <= $end) {
                $parentId = $item['parentId'];
                $parentName = $item['parentName'];
                $stt = 0;
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
            'row_expand' => $parent_row,
            'devices' => $all_inventories

        );
        return $response;
        try {
        } catch (Error $th) {
            throw new Error("Error  $currentFunction ()  " . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error  $currentFunction ()  " . $th->getMessage());
        }
    }
    function filterData()
    {
        $currentFile = basename(__FILE__);
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
        } catch (Error $e) {
            throw new Error("Error in $currentFile ->" . $e->getMessage());
        } catch (Exception $e) {
            throw new Error("Error in $currentFile ->" . $e->getMessage());
        }
    }
}
