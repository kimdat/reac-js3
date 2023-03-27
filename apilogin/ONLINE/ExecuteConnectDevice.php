<?php

namespace Online;

use Error;

use Online\connectDevice;
use Online\removeDeviceDuplicate;
use Throwable;



class ExecuteConnectDevice
{
    function ExecuteConnectDevice()
    {

        $deviceName = "";
        try {
            if (!isset($_POST["device_list"]) || empty($_POST["device_list"])) {
                return;
            }
            $devices_id = json_decode($_POST["jsonId"]);
            $devices_list = json_decode($_POST["device_list"]);
            $connectDevice = new connectDevice();
            $res =  $connectDevice->connectDevice($devices_list);
            //Gía trị trả về là mảng json với key là ip
            $res = json_decode($res);
            $inventory = $res->deviceData;
            $inventory = json_decode($inventory);
            $devicesName = $res->deviceName;
            //data connect thành công
            $dataSuccess = [];
            //data connect thất bị
            $dataFail = [];
            $err = [];
            $stt = 1;
            foreach ($devicesName as $ip => $deviceName) {
                try {
                    $dataInventory = $inventory->$ip;
                    $dataInventoryFirst = $dataInventory[0];
                    $children = [];
                    //nếu không có lỗi
                    if (!isset($dataInventoryFirst->Err)) {
                        $children = $dataInventory;
                        $dataSuccess[] = array("STT" => $stt++, "ip" => $ip, "id" => $devices_id->$ip, "Name" => $deviceName, "children" => $children);
                    } else {
                        $dataFail[] = array("ip" => $ip, "id" => $devices_id->$ip, "Name" => $deviceName, "children" => $children);
                    }
                } catch (Throwable $th) {
                    $err[] = array($ip => $th->getMessage());
                }
            }
            return json_encode(array("success" => $dataSuccess, "Err" => $err, "fail" => $dataFail));
        } catch (Throwable $th) {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            throw new  Error("Err in $currentFunction in $currentFile " . $th->getMessage());
        }
    }
}
