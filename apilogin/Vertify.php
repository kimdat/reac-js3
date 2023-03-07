<?php
// Kiểm tra phiên bản PHP
if (version_compare(PHP_VERSION, '7.2.5', '<')) {
    die('Bạn cần cập nhật PHP lên phiên bản 7.2.5 trở lên để sử dụng mã này.');
}

require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Khai báo key secret để mã hóa và giải mã token
$secretKey = 'cxASDAQjGQLuNgpnjAyVF7Y9NIh5Vj5hzXTn';


function createToken($user, $secretKey)
{

    $payload = [
        'sub' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'iat' => time(),
        'exp' => time() + (60 * 60) // Hết hạn sau 1 giờ
    ];
    $jwt = JWT::encode($payload, $secretKey, 'HS256');
    header('Set-Cookie: access_token=' . $jwt . '; Max-Age=' . (60 * 60) . '; Path=/; HttpOnly; Secure', true);
    return $jwt;
}

// Giải mã token
function decodeToken($token, $secretKey)
{
    return JWT::decode($token, new Key($secretKey, 'HS256'));
}

// Kiểm tra token
function verifyToken($token, $secretKey)
{
    try {
        decodeToken($token, $secretKey);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Xác thực user
function authenticate($email, $password)
{
    // Tìm user trong database bằng email và password
    // Nếu tìm thấy, trả về user object, nếu không trả về null

    // Ví dụ:
    if ($email === 'user@example.com' && $password === 'password') {
        return (object) [
            'id' => 1,
            'name' => 'User',
            'email' => 'user@example.com'
        ];
    } else {
        return null;
    }
}

if ($_SERVER['HTTPS'] !== 'on') {
    $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirectUrl, true, 301);
    exit();
}

// Xử lý request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy email và password từ request
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Xác thực user bằng email và password
    $user = authenticate($email, $password);

    if ($user) {
        // Nếu user xác thực thành công, tạo token và trả về cho client
        $token = createToken($user, $secretKey);
        echo json_encode(['token' => $token]);
    } else {
        // Nếu user xác thực không thành công, trả về lỗi 401 Unauthorized
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Lấy token từ header hoặc query string
    $user = (object) [
        'id' => 1,
        'name' => 'User',
        'email' => 'user@example.com'
    ];
    $token = createToken($user, $secretKey);

    if (isset($_COOKIE['access_token'])) {
        $token = $_COOKIE['access_token'];
        if (verifyToken($token, $secretKey)) {
            // Nếu token hợp lệ, trả về dữ liệu cho client
            echo json_encode(['message' => 'Hello, world!']);
            exit(); // kết thúc chương trình sau khi trả về dữ liệu
        }
    }

    // Nếu không có token hoặc token không hợp lệ, trả về lỗi 401 Unauthorized
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
}
