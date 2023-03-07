<?php

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Slim\Psr7\Response;

// Giải mã token
function decodeToken($token, $secretKey)
{
    return JWT::decode($token, new Key($secretKey, 'HS256'));
}
function checkToken($request, $handler)
{
    try {
        $jwtToken = $request->getHeaderLine('Authorization');
        if (empty($jwtToken)) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Authorization header is missing']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $keyJwt = KEY_JWT; // Key JWT đã được định nghĩa trước
        // Giải mã JWT token
        $decoded = decodeToken($jwtToken, $keyJwt);
        // Giải mã dữ liệu AES
        $keyAes = KEY_AES; // Key AES đã được định nghĩa trước
        $iv = IV_AES;
        //Lấy chuỗi mã hóa
        $encrypted_data = $decoded->key;
        $encrypted_data = base64_decode($encrypted_data); // Giải mã chuỗi sau khi mã hóa và vector khởi tạo
        $iv = substr($encrypted_data, 0, 16); // Lấy vector khởi tạo từ chuỗi đã giải mã
        $ciphertext = substr($encrypted_data, 16);
        $decrypted_data = openssl_decrypt($ciphertext, 'AES-256-CBC', $keyAes, OPENSSL_RAW_DATA, $iv);
        // Kiểm tra tính hợp lệ của key
        if ($decrypted_data !== KEY_API) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Invalid AES key']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        // Lưu thông tin user vào request để sử dụng sau này
    } catch (\Exception $e) {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    $response = $handler->handle($request);
    return $response;
}
