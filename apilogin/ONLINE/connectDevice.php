<?php

namespace Online;

use Error;
use Exception;

class connectDevice
{
    public  function connectDevice($data)
    {
        try {
            $currentFile = basename(__FILE__);
            $currentFunction = __FUNCTION__;
            // Tạo một mảng chứa URL của các API cần gọi
            $url = "http://localhost/NETMIKO/home.py";
            // Tạo một curl handler
            $ch = curl_init();
            //Nếu là string thì convernt sang array
            if (is_string($data)) {
                $data = array($data);
            }
            $dataConnect = array(
                "ip" => json_encode($data)
            );
            // Thiết lập URL của API cần gọi
            curl_setopt($ch, CURLOPT_URL, $url);
            // Thiết lập các options cho curl handler
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataConnect);
            // Thực hiện curl request
            $response = curl_exec($ch);
            // Kiểm tra lỗi trong quá trình thực hiện curl request
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            // Đóng curl handler
            curl_close($ch);
            return $response;
        } catch (Error $th) {
            throw new Error("Error in $currentFunction in $currentFile ->" . $th->getMessage());
        } catch (Exception $th) {
            throw new Error("Error in $currentFunction in $currentFile ->" . $th->getMessage());
        }
    }
}
