<?php

namespace Online;

use Error;
use Exception;
use Throwable;

class connectDevice
{
    public  function connectDevice($data, $url = "http://localhost/NETMIKO/homeData.py")
    {
        try {
            $currentFile = basename(__FILE__);
            echo $url;
            // Tạo một curl handler
            $ch = curl_init();
            //Nếu là string thì convernt sang array
            if (is_object($data)) {
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
        } catch (Throwable $th) {
            $currentFunction = __FUNCTION__;
            throw new Error("Error in $currentFunction in $currentFile ->" . $th->getMessage());
        }
    }
}
