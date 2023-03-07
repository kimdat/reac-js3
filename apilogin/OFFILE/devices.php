<?php
function getAllDevices()
{
    $currentFile = basename(__FILE__);
    global $conn;
    // Khởi tạo giá trị ban đầu cho biến @rownum
    try {
        $conn->query("SET @rownum = 0");
        $sql = "SELECT  d." . COLUMN_Devices_ID . " as id, d." . COLUMN_Devices_NAME . " as Name, 
        false as showChild, (@rownum := @rownum + 1) as STT
        FROM " . TABLE_DEVICES . " d 
        WHERE d." . COLUMN_STATUS . " <> 'D'
       
        ORDER BY d." . COLUMN_Devices_NAME . "
        LIMIT 10;
        ";
        $stmt = $conn->prepare($sql);

        $stmt->execute();
        $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sql_count = "SELECT " . COLUMN_Devices_ID . "," . COLUMN_Devices_NAME . "  FROM " . TABLE_DEVICES . " WHERE " . COLUMN_STATUS . " <> 'D'";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->execute();
        $dataDevice = $stmt_count->fetchAll();
        $idNameFilter = array_map(function ($item) {
            return [
                "id" => $item["id"],
                "name" => $item["Name"]
            ];
        },  $dataDevice);
        $data = array(
            "inventories" => $inventories,
            "total_row" => sizeof($dataDevice),
            "devices" => $idNameFilter
        );
        return json_encode($data);
    } catch (PDOException $th) {
        throw new Error("Error in $currentFile ->" . $th->getMessage());
    } catch (Exception $th) {
        throw new Error("Error in $currentFile ->" . $th->getMessage());
    } catch (Error $th) {
        throw new Error("Error in $currentFile ->" . $th->getMessage());
    }
}
