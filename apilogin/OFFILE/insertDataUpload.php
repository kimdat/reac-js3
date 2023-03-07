<?php


$path = 'uploads/';

function insertDataUpload()
{
    global $conn;
    try {

        $fileuploadContents = $_POST['fileUploadContents'];
        $pathArray = [];
        $pathArray = json_decode($_POST['pathArray']);

        $duplicateDeviesName = [];
        $file_err = [];
        //Số lượng phần tử trong fileuploadContens tương ứng file_content
        $file_content = json_decode($fileuploadContents);
        if (isset($_POST['duplicateDevicesName'])) {
            $duplicateDeviesName = json_decode($_POST['duplicateDevicesName']);
        }


        $arrIndexesSuccess = json_decode($_POST['arrIndexesSuccess']); // Chuyển đổi sang mảng


        for ($i = 0; $i < sizeof($file_content); $i++) {
            //nếu như errorFileIndex lỗi
            try {
                if (!in_array($i, $arrIndexesSuccess)) {
                    throw new Error("Loi khi uploadfile at $i ");
                }
                $conn->beginTransaction();
                if (isset($duplicateDeviesName->$i)) {
                    updateDataTrung($conn, $duplicateDeviesName->$i);
                }
                insertData($conn, $file_content[$i]);
                $conn->commit();
            } catch (Error $th) {
                $file_err[] = catchInserData($conn, $pathArray[$i], $th, $i);
            } catch (Exception $th) {
                $file_err[] = catchInserData($conn, $pathArray[$i], $th, $i);
            }
        }

        if (sizeof($file_err) != 0)
            return json_encode(array("Error" => $file_err));
        return "";
    } catch (Error $e) {

        throw new Error("Error  ->" . $e->getMessage());
    }
}

function catchInserData($conn, $pathArray, $th, $i)
{
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    if (isset($pathArray->path)) {
        deleteFile($pathArray->path);
    }
    return array("ErrorMess" => $th->getMessage(), "FileErrorIndex" => $i);
}


function updateDataTrung($conn, $duplicateDeviesName)
{
    try {
        $currentFunction = __FUNCTION__;
        global $currentFile;
        $placeholders = implode(',', array_fill(0, count($duplicateDeviesName), '?'));
        //  $placeholders = rtrim(str_repeat('?,', count($duplicaeDeviesName)), ',');
        $sql = "UPDATE " . TABLE_DEVICES . " SET " . COLUMN_STATUS . " = 'D', " . COLUMN_DEVICES_TIME_DELETE . " = NOW() WHERE STATUS <>'D' AND " . COLUMN_Devices_NAME . " IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($duplicateDeviesName);
    } catch (PDOException $e) {
        throw new Error("Error in $currentFunction in $currentFile " . $e->getMessage());
    } catch (Exception $e) {
        throw new Error("Error in $currentFunction  in $currentFile " . $e->getMessage());
    }
}
function deleteFile($pathToDelete)
{
    if (file_exists($pathToDelete)) {
        unlink($pathToDelete);
        // File path đã được xóa
    }
}
function insertData($conn, $file_content)
{

    try {
        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
        $deviceDatas = $file_content->deviceDatas;
        $deviceNames = $file_content->deviceNames;
        //Số lượng deviceNames tương ứng với số lượng devicsDatas và mỗi phần tử trong devicedatas lưu mảng data thiết bị con
        for ($j = 0; $j < sizeof($deviceNames); $j++) {
            $deviceName = $deviceNames[$j];
            $deviceData = $deviceDatas[$j];
            //insert cha
            $sqlParent = "INSERT INTO " . TABLE_DEVICES . " (" . COLUMN_Devices_NAME . ") VALUES (?)";
            $stmtParent = $conn->prepare($sqlParent);
            $stmtParent->bindParam(1, $deviceName);
            $stmtParent->execute();
            $device_id = $conn->lastInsertId();
            // Chuyển đổi mảng JSON thành mảng PHP
            $values = array();
            foreach ($deviceData as $item) {
                $values[] = array(
                    'name' => $item->NAME,
                    'descr' => $item->DESCR,
                    'pid' => $item->PID,
                    'vid' => $item->VID,
                    'sn' => $item->SN,
                    'ParentId' => $device_id
                );
            }
            $placeholders = array_fill(0, count($values), "(?, ?, ?, ?, ?,?)");
            $values_flat = array();
            foreach ($values as $row) {
                $values_flat = array_merge($values_flat, array_values($row));
            }
            $sql = "INSERT INTO " . TABLE_INVENTORIES . " (" . COLUMN_INVENTORIES_NAME . ", "
                . COLUMN_INVENTORIES_CDESC . ","
                . COLUMN_INVENTORIES_PID . ","
                . COLUMN_INVENTORIES_VID . ","
                . COLUMN_INVENTORIES_SERIAL . ","
                . COLUMN_INVENTORIES_PARENTID .
                ") VALUES " . implode(',', $placeholders);
            $stmt = $conn->prepare($sql);
            $stmt->execute($values_flat);
        }
    } catch (PDOException $e) {
        throw new Error("Error $currentFunction in $currentFile " . $e->getMessage());
    } catch (Error $e) {
        throw new Error("Error $currentFunction in $currentFile " . $e->getMessage());
    } catch (Exception $e) {
        throw new Error("Error $currentFunction in $currentFile " . $e->getMessage());
    }
}
