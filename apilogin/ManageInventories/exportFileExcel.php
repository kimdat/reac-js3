<?php
// Load the PHPExcel classes

namespace ManageInventories;

use Error;
use Exception;
use PDO;
use PDOException;
use PhpOffice\PhpSpreadsheet\spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Slim\Psr7\Response;

class exportFileExcel
{
    function downExcel()
    {

        $currentFile = basename(__FILE__);
        $currentFunction = __FUNCTION__;
        try {
            $rowId = json_decode($_POST['rowId']) ?? [];
            // filter value dựa trên valuesearch và value column trong file getAllId.php
            $smtDataExport = self::sqlGetDataExport($rowId);
            $dataExport = $smtDataExport->fetchAll(PDO::FETCH_ASSOC);


            $writer = new Xlsx(self::setDataExcel($dataExport));
            $response = new Response();
            $writer->save('php://output');
            $response->getBody()->write(file_get_contents('php://output'));

            return $response;
        } catch (Exception $th) {
            throw new Error("Err in $currentFunction in $currentFile ->" . $th->getMessage());
        } catch (Error $th) {
            throw new Error("Err in $currentFunction in $currentFile ->" . $th->getMessage());
        }
    }
    function setDataExcel($datas)
    {
        global $currentFile, $devicesDefine, $inventoriesDefine;
        $currentFunction = __FUNCTION__;
        try {
            // Create a new spreadsheet object
            $spreadsheet = new Spreadsheet();
            // Set the active sheet
            $sheet = $spreadsheet->getActiveSheet();
            $data_array[] = ['STT', 'Device Name', 'Slot', 'PID', 'Serial', 'Description'];
            $currentParentId = "";
            $stt = 1;
            foreach ($datas as $data) {
                //Tạo row cha
                if ($currentParentId != $data[$inventoriesDefine::COLUMN_INVENTORIES_PARENTID]) {
                    $currentParentId = $data[$inventoriesDefine::COLUMN_INVENTORIES_PARENTID];
                    $data_array[] = [$stt++, $data['ParentName'], '', '', '', ''];
                }
                $data_array[] = [
                    '', '', $data[$inventoriesDefine::COLUMN_INVENTORIES_NAME], $data[COLUMN_INVENTORIES_PID],
                    $data[$inventoriesDefine::COLUMN_INVENTORIES_SERIAL],  $data[$inventoriesDefine::COLUMN_INVENTORIES_CDESC]
                ];
            }
            $sheet->fromArray($data_array);
            // Đặt in đậm và độ rộng cột
            $column_titles = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
            foreach ($column_titles as $title) {
                $sheet->getStyle($title . '1')->getFont()->setBold(true);
                $sheet->getColumnDimension($title)->setWidth(15);
            }
            return $spreadsheet;
        } catch (Error $th) {
            throw new Error("Error  $currentFunction () in $currentFile" . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error  $currentFunction () in $currentFile" . $th->getMessage());
        }
    }
    function sqlGetDataExport($idToGet)
    {
        $currentFunction = __FUNCTION__;

        try {
            global $conn, $currentFile, $devicesDefine, $inventoriesDefine;

            //điều kiện filter theo id
            $where = sizeof($idToGet) > 0 ? " WHERE i." . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . " IN (" . implode(',', array_fill(0, count($idToGet), '?')) . ")" : "";
            $sql = "SELECT i." . $inventoriesDefine::COLUMN_INVENTORIES_ID . ", i." . $inventoriesDefine::COLUMN_INVENTORIES_NAME
                . ", i." . $inventoriesDefine::COLUMN_INVENTORIES_PID . ", i." . $inventoriesDefine::COLUMN_INVENTORIES_VID
                . ", i." . $inventoriesDefine::COLUMN_INVENTORIES_SERIAL . ", i." . $inventoriesDefine::COLUMN_INVENTORIES_CDESC
                . ", i." . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID . ",d." . $devicesDefine::COLUMN_DEVICES_NAME . " as ParentName
        FROM " . $inventoriesDefine::TABLE_INVENTORIES . " i
        INNER JOIN " . $devicesDefine::TABLE_DEVICES . " d on d."
                . $devicesDefine::COLUMN_DEVICES_ID . "=i."
                . $inventoriesDefine::COLUMN_INVENTORIES_PARENTID .
                $where;
            $stmt = $conn->prepare($sql);
            //NẾU có mảng id thì param id
            $stmt->execute(sizeof($idToGet) > 0 ? array_values($idToGet) : "");
            return $stmt;
        } catch (PDOException $th) {
            throw new Error("Sql Error $currentFunction () in $currentFile " . $th->getMessage());
        } catch (Error $th) {
            throw new Error("Error  $currentFunction () in $currentFile" . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error  $currentFunction () in $currentFile " . $th->getMessage());
        }
    }
}
