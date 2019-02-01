<?php
include('SQLFunctions.php');
include('session.php');
$aUsername = $_SESSION['username'];
$title = "Update User Commit";

if (!isset($_POST['username'])) {
    $message = 'Please enter a valid username';
}
elseif (strlen( $_POST['username']) > 20 || strlen($_POST['username']) < 4) {
    $message = 'Incorrect length for Username';
}
elseif (ctype_alnum($_POST['username']) != true) {
    $message = "Username must be alpha numeric";
}
elseif (ctype_alnum($_POST['company']) != true) {
    $message = "Company must be alpha numeric";
}
elseif (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) !=true) {
    $message = "Email is not a valid email address";
}
elseif (!empty($_POST)) {
    $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
    
    $fields = [
        'username' => $post['username'],
        'firstname' => $post['firstname'],
        'lastname' => $post['lastname'],
        'password' => $post['password'],
        'company' => $post['company'],
        'email' => $post['email'],
        'role' => $post['role'],
        'inspector' => $post['inspector'],
        'bdPermit' => $post['bdPermit'],
        'updated_by' => $aUsername,
        'lastUpdated' => date('Y-m-d H:i:s')
    ];
    
    $fields = array_filter($fields, function($val) {
        return $val !== null && $val !== false;
    });
    
    try {
        if (!empty($message)) throw new Exception($message);
        
        if (!empty($fields['password'])) {
            if (!empty($post['conPwd']) && $fields['password'] !== $post['conPwd'])
                throw new Exception('Confirmation password does not match new password');
            elseif (!$fields['password'] = password_hash($fields['password'], PASSWORD_DEFAULT))
                throw new Exception('Unable to encrypt new password');
        }

        if (!$post['userID']) throw new Exception('Could not find userID');

        $link = connect();
        $link->where('userID', $post['userID']);
        
        if (!$link->update('users_enc', $fields))
            throw new Exception('There was a problem updating the record: ' . $link->getLastError());
        else $location = '/displayUsers.php';
    } catch (Exception $e) {
        $_SESSION['errorMsg'] = $e->getMessage();
        $location = "/UpdateUser.php?userID={$post['userID']}";
    } finally {
        if (!empty($link) && is_a($link, 'MysqliDb')) $link->disconnect();
        if (!empty($message)) $_SESSION['errorMsg'] = $message;
        header("Location: $location");
        exit;
    }
}
