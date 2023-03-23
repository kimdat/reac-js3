<?php
// Load the PHPExcel classes

namespace ManageInventories;

use Error;
use Exception;
use PDO;
use PDOException;
use PhpOffice\PhpSpreadsheet\spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpParser\Node\Stmt\Return_;
use Slim\Psr7\Response;
use stdClass;
use Throwable;

class exportFileExcel
{
    function downExcel()
    {

        $currentFile = basename(__FILE__);

        try {
            $rows = json_decode($_POST['row']) ?? [];
            $rowId = [];
            $objNo = new stdClass();
            foreach ($rows as $row) {
                $id = $row->id;
                $objNo->$id = $row->No;
                # code...
                $rowId[] = $id;
            }
            // filter value dựa trên valuesearch và value column trong file getAllId.php
            $smtDataExport = self::sqlGetDataExport($rowId);


            $dataExport = $smtDataExport->fetchAll(PDO::FETCH_ASSOC);
            return self::writeFileExcel($dataExport, $objNo);
        } catch (Throwable $th) {
            $currentFunction = __FUNCTION__;
            throw new Error("Err in $currentFunction in $currentFile ->" . $th->getMessage());
        }
    }
    function writeFileExcel($dataExport, $objNo)
    {
        try {

            $writer = new Xlsx(self::setDataExcel($dataExport, $objNo));
            $response = new Response();
            $writer->save('php://output');
            $response->getBody()->write(file_get_contents('php://output'));
            return $response;
        } catch (\Throwable $th) {
            $currentFunction = __FUNCTION__;
            throw new Error("Err in $currentFunction ->" . $th->getMessage());
        }
    }
    function setDataExcel($datas, $objNo)
    {
        global $currentFile, $inventoriesDefine;
        try {
            // Create a new spreadsheet object
            $spreadsheet = new Spreadsheet();
            // Set the active sheet
            $sheet = $spreadsheet->getActiveSheet();
            $data_array[] = ['No', 'Device Name', 'Slot', 'PID', 'Serial', 'Description'];
            $currentParentId = "";
            foreach ($datas as $data) {
                //Tạo row cha
                if ($currentParentId != $data[$inventoriesDefine::COLUMN_INVENTORIES_PARENTID]) {
                    $currentParentId = $data[$inventoriesDefine::COLUMN_INVENTORIES_PARENTID];
                    $data_array[] = [$objNo->$currentParentId, $data['ParentName'], '', '', '', ''];
                }
                $data_array[] = [
                    '', '', $data[$inventoriesDefine::COLUMN_INVENTORIES_NAME], $data[$inventoriesDefine::COLUMN_INVENTORIES_PID],
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
        } catch (Throwable $th) {
            $currentFunction = __FUNCTION__;
            throw new Error("Error  $currentFunction () " . $th->getMessage());
        }
    }
    function sqlGetDataExport($idToGet)
    {
        try {
            global $conn, $currentFile, $devicesDefine, $inventoriesDefine;
            $where2 = " 1=1";
            //nếu online thì thêm điều kiện
            if (!isset($_SERVER['HTTP_FLAGOFFLINE'])) {
                $where2 = $inventoriesDefine::COLUMN_INVENTORIES_STATUS_DELETED . " <>'D'";
            }
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
                $where . " and " . $where2 . "  ORDER BY d." .
                $devicesDefine::COLUMN_DEVICES_NAME . ",i." . $inventoriesDefine::COLUMN_INVENTORIES_ID;;
            $stmt = $conn->prepare($sql);
            //NẾU có mảng id thì param id
            $stmt->execute(sizeof($idToGet) > 0 ? array_values($idToGet) : "");
            return $stmt;
        } catch (Throwable $th) {
            $currentFunction = __FUNCTION__;
            throw new Error("Sql Error $currentFunction () in $currentFile " . $th->getMessage());
        }
    }
}
