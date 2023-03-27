<?php
// Load the PHPExcel classes

namespace Online;

use Error;
use Exception;
use PDO;
use PDOException;
use PhpOffice\PhpSpreadsheet\spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Slim\Psr7\Response;
use Throwable;

class exportFileExcelSSH1
{
    function downExcel()
    {

        $currentFile = basename(__FILE__);

        try {
            $rowId = json_decode($_POST['row']) ?? [];
            // filter value dựa trên valuesearch và value column trong file getAllId.php

            $dataExport = $rowId;
            //throw new Exception(json_encode($rowId));

            $writer = new Xlsx(self::setDataExcel($dataExport));
            $response = new Response();
            $writer->save('php://output');
            $response->getBody()->write(file_get_contents('php://output'));

            return $response;
        } catch (Throwable $th) {
            $currentFunction = __FUNCTION__;
            throw new Error("Err in $currentFunction in $currentFile ->" . $th->getMessage());
        }
    }
    function setDataExcel($datas)
    {
        global $currentFile, $inventoriesDefine;

        try {
            // Create a new spreadsheet object
            $spreadsheet = new Spreadsheet();
            // Set the active sheet
            $sheet = $spreadsheet->getActiveSheet();
            $data_array[] = ['No', 'Device Name', 'Slot', 'PID', 'Serial', 'Description'];
            $currentParentId = "";
            $stt = 1;


            foreach ($datas as $data) {

                //Tạo row cha
                $data_array[] = [$stt++, $data->Name, '', '', '', ''];
                //tạo row con
                foreach ($data->children as $child) {
                    $data_array[] = [
                        '', '', $child->Name, $child->PID,   $child->Serial, $child->CDESC
                    ];
                }
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
            throw new Error("Error  $currentFunction () in $currentFile" . $th->getMessage());
        }
    }
}
