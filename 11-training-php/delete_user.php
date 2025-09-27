<?php
require_once 'models/UserModel.php';
session_start();
$userModel = new UserModel();

$user = NULL; //Add new user
$id = NULL;

$sent = $_POST['csrf_token'] ?? '';
$stored = $_SESSION['csrf'] ?? '';
if (!hash_equals($stored, $sent)) {
    http_response_code(403);
    exit('Invalid CSRF token');
}


if (!empty($_POST['id'])) {
    $id = $_POST['id'];
    $userModel->deleteUserById($id);//Delete existing user
}
header('location: list_users.php');
exit

?>