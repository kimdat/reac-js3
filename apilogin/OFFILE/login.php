<?php


function login()
{
    global $conn;
    try {

        $email = $_POST['email'];
        $password = $_POST['password'];
        $sql = "SELECT * FROM " . TABLE_ACCOUNT . " where " . COLUMN_EMAIL . "=:email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch();
            if (strcmp($password, md5($result['PassWord'])) == 0) {
                $response = ['status' => 1, 'mess' => "Login succesfully"];
            } else {
                $response = ['status' => 0, 'mess' => "PassWord wrong"];
            }
        } else {
            $response = ['status' => 0, 'mess' => "Email doesn't exist"];
        }

        return $response;
    } catch (Error $th) {
        throw new Error($th->getMessage());
    } catch (Exception $th) {
        throw new Error($th->getMessage());
    }
}
