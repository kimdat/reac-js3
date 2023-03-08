<?php


$path = 'uploads/';

function insertDataUpload()
{
    global $conn;
    try {
        $fileuploadContents = $_POST['fileUploadContents'];
        $duplicateDeviesName = [];
        $file_err = [];
        //Nội dung file upload
        $file_content = json_decode($fileuploadContents);
        //kiểm tra xem có update thiết bị trùng
        if (isset($_POST['duplicateDevicesName'])) {
            $duplicateDeviesName = json_decode($_POST['duplicateDevicesName']);
        }
        //Mess khi upload file
        $messUpload = json_decode($_POST['messUpload']); // Chuyển đổi sang mảng
        for ($i = 0; $i < sizeof($file_content); $i++) {
            try {
                //nếu phần tử upload file đó bị lỗi
                if (isset($messUpload[$i]->Error)) {
                    throw new Error(("Error from fileupload.php." . $messUpload[$i]->Error));
                    // throw new Error(json_encode((array("Error" => $messUpload[$i]->Error, "ErrorShow" => "Error uploadFile at i"))));
                }
                $conn->beginTransaction();
                if (isset($duplicateDeviesName->$i)) {
                    updateDataTrung($conn, $duplicateDeviesName->$i);
                }
                insertData($conn, $file_content[$i]);
                $conn->commit();
            } catch (Error $th) {
                //Đường dẫn cần xóa khi insert lỗi
                $pathArrayToDelete = "";
                if (isset($messUpload[$i]->path)) {
                    $pathArrayToDelete = $messUpload[$i]->path;
                }
                $file_err[] = catchInserData($conn, $pathArrayToDelete, $th, $i);
            } catch (Exception $th) {
                $pathArrayToDelete = "";
                if (isset($messUpload[$i]->path)) {
                    $pathArrayToDelete = $messUpload[$i]->path;
                }
                $file_err[] = catchInserData($conn, $pathArrayToDelete, $th, $i);
            }
        }
        if (sizeof($file_err) != 0)
            return json_encode(array("Error" => $file_err));
        return "";
    } catch (Error $e) {

        throw new Error("Error  ->" . $e->getMessage());
    }
}

function catchInserData($conn, $pathArrayToDelete, $th, $i)
{
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    if (isset($pathArrayToDelete)) {
        deleteFile($pathArrayToDelete);
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

            $timestamp = time();
            $random_string = uniqid('rd', true);
            $device_id = $timestamp . $random_string;
            $deviceName = $deviceNames[$j];
            $deviceData = $deviceDatas[$j];
            //insert cha
            $sqlParent = "INSERT INTO " . TABLE_DEVICES . " (" . COLUMN_Devices_NAME . "," . COLUMN_Devices_ID . ") VALUES (?,?)";
            $stmtParent = $conn->prepare($sqlParent);
            $stmtParent->bindParam(1, $deviceName);
            $stmtParent->bindParam(2, $device_id);
            $stmtParent->execute();
            //$device_id = $conn->lastInsertId();
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
        throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
    } catch (Error $e) {
        throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
    } catch (Exception $e) {
        throw new Error("Error $currentFunction in $currentFile ." . $e->getMessage());
    }
}
