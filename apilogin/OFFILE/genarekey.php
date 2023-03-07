
<?php
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;

$secret_key = 'MAHOACISCO2023_JWT_KEY_CTINDEV.NET';
$key = 'MAHOACISCO2023_AES-256-CBC_KEY_CTINDEV.NET';

$plaintext = "khIgvBfp59NBC54X";

$iv = '_IV_MAHOA_CISCO_';


$ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
$encrypted_data = base64_encode($iv . $ciphertext); // Mã hóa chuỗi sau khi mã hóa và vector khởi tạo

//Đăng ký jwt
$payload = array(
    "key" => $encrypted_data,
    "exp" => 2147483647 // Thời gian hết hạn (vô cực)
);

$jwt = JWT::encode($payload, $secret_key, 'HS256'); // Tạo JWT
echo $jwt;



// Giải mã dữ liệu
$encrypted_data = base64_decode($encrypted_data); // Giải mã chuỗi sau khi mã hóa và vector khởi tạo
$iv = substr($encrypted_data, 0, 16); // Lấy vector khởi tạo từ chuỗi đã giải mã
$ciphertext = substr($encrypted_data, 16);
$decrypted_data = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
echo $decrypted_data;


/*
echo "\n";
$base64_encrypted_key = base64_encode($encrypted_key);
$payload = array(
    "key" => $base64_encrypted_key,
    "exp" => 2147483647 // Thời gian hết hạn (vô cực)
);

echo "<br/>";
echo "\n " . $base64_encrypted_key . "\n";


$jwt = JWT::encode($payload, $secret_key, 'HS256'); // Tạo JWT
echo $jwt;*/
//file_put_contents('config.php', '<?php return ' . var_export(array('jwt' => $jwt), true) . ';');
function getName($n)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

?>
