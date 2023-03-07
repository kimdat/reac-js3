<?php
// Tạo một mảng chứa URL của các API cần gọi
$urls = array(
    "https://example.com/api/device1",
    "https://example.com/api/device2",
    "https://example.com/api/device3"
);

// Tạo một đối tượng cURL Multi mới
$multi_handle = curl_multi_init();

// Tạo một mảng chứa các cURL handle tương ứng với từng yêu cầu API
$curl_handles = array();

// Tạo các cURL handle và thêm chúng vào đối tượng cURL Multi
foreach ($urls as $url) {
    $data = array(
        "ip" => "10.0.137.200",
        // Thêm các key-value pairs khác tùy vào yêu cầu API
    );
    $json_data = json_encode($data);

    $curl_handles[$url] = curl_init($url);
    curl_setopt($curl_handles[$url], CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_handles[$url], CURLOPT_POSTFIELDS, $json_data);
    curl_multi_add_handle($multi_handle, $curl_handles[$url]);
}

// Thực hiện các yêu cầu API đồng thời
$running = null;
do {
    curl_multi_exec($multi_handle, $running);
} while ($running);

// Thu thập các kết quả tương ứng với từng yêu cầu API
$results = array();
foreach ($urls as $url) {
    $response = curl_multi_getcontent($curl_handles[$url]);
    $time = curl_getinfo($curl_handles[$url], CURLINFO_TOTAL_TIME);
    $results[$url] = array("response" => $response, "time" => $time);
}

// Giải phóng các cURL handle và đối tượng cURL Multi
foreach ($urls as $url) {
    curl_multi_remove_handle($multi_handle, $curl_handles[$url]);
    curl_close($curl_handles[$url]);
}
curl_multi_close($multi_handle);

// Hiển thị mảng response
print_r($results);
