<?php


function sqlGetDataId($valueSearch, $valueColumn)
{
    global $conn;
    $currentFunction = __FUNCTION__;
    try {
        $where1 = "1=1";
        $where2 = "1=1";
        $params = [];
        if ($valueSearch != "") {
            $where1 .= ' AND (Devices.' . COLUMN_Devices_NAME . '  LIKE :valueSearch or inventories.'
                . COLUMN_INVENTORIES_NAME . ' LIKE  :valueSearch OR inventories.'
                . COLUMN_INVENTORIES_PID . ' LIKE :valueSearch  OR inventories.'
                . COLUMN_INVENTORIES_VID . ' LIKE :valueSearch  OR inventories.'
                . COLUMN_INVENTORIES_SERIAL . ' LIKE :valueSearch  OR inventories.'
                . COLUMN_INVENTORIES_CDESC . ' LIKE :valueSearch )';
            $params = [':valueSearch' => '%' . $valueSearch . '%'];
        }
        foreach ($valueColumn as $column => $value) {
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

        $sql = "SELECT  devices." . COLUMN_Devices_ID . " as parentId, 
                devices." . COLUMN_Devices_NAME . " as parentName
             FROM " . TABLE_DEVICES . " devices 
            INNER JOIN " . TABLE_INVENTORIES . " inventories 
            ON devices." . COLUMN_Devices_ID . " = inventories." . COLUMN_INVENTORIES_PARENTID . "
            WHERE " . COLUMN_STATUS . " <> 'D' AND $where1 AND $where2 
            order by devices." . COLUMN_Devices_NAME . ",inventories." . COLUMN_INVENTORIES_ID . "
        ";
        $stmt = $conn->prepare($sql);
        // Liên kết giá trị của các tham số với câu lệnh SQL
        if (sizeof($params) > 0) {
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        return $stmt;
    } catch (Error $th) {
        throw new Error("Error  $currentFunction () -> " . $th->getMessage());
    } catch (Exception $th) {
        throw new Error("Error  $currentFunction ()  ->  " . $th->getMessage());
    } catch (PDOException $th) {
        throw new Error("Error  $currentFunction ()  ->  " . $th->getMessage());
    }
}

function getAllId()
{
    $currentFile = basename(__FILE__);
    try {
        $valueSearch = $_GET['valueSearch'] ?? '';
        $valueColumn = json_decode($_GET['valueColumn'] ?? '{}', true);

        // Kiểm tra trước khi truy cập vào các biến $_GET và $_POST
        if (!is_string($valueSearch) || !is_array($valueColumn)) {
            echo json_encode(['Err' => 'Invalid parameters']);
            exit;
        }
        $stmt = sqlGetDataId($valueSearch, $valueColumn);

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Lấy mảng chứa giá trị duy nhất của cột parent_id
        $parentIds = array_values(array_unique(array_column($data, 'parentId')));
        $parentName = array_values(array_unique(array_column($data, 'parentName')));
        $parentData = array_map(function ($id, $name) {
            return array('id' => $id, 'name' => $name);
        }, $parentIds, $parentName);
        return json_encode(array("parentData" => $parentData));
    } catch (Error $e) {
        throw new Error("Error in $currentFile ->" . $e->getMessage());
    } catch (Exception $e) {
        throw new Error("Error in $currentFile ->" . $e->getMessage());
    }
}
