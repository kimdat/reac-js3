<?php
// Tạo một mảng chứa URL của các API cần gọi
$url = "http://localhost/NETMIKO/home.py";
// Tạo một curl handler
$ch = curl_init();
$data = array(
    "ip" => json_encode(array("10.0.137.200", "10.0.137.201"))
);
$json_data = json_encode($data);
// Thiết lập URL của API cần gọi
curl_setopt($ch, CURLOPT_URL, $url);
// Thiết lập các options cho curl handler
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
// Thực hiện curl request
$response = curl_exec($ch);
// Kiểm tra lỗi trong quá trình thực hiện curl request
if (curl_errno($ch)) {
    throw new Exception(curl_error($ch));
}
// Đóng curl handler
curl_close($ch);
print($response);
