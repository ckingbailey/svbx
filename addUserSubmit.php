<?php
require_once('SQLFunctions.php');
include('session.php');

$AUserID = $_SESSION['userID'];
$AUser = "SELECT username FROM users_enc WHERE UserID = $AUserID";
$link = f_sqlConnect();

if($result = $link->query($AUser))
    {
      /*from the sql results, assign the username that returned to the $username variable*/
      while($row = $result->fetch_assoc()) {
        $AUsername = $row['username'];
      }
    }

if(!isset( $_POST['username'], $_POST['pwd']))
{
    $message = 'Please enter a valid username and password';
}
elseif (strlen( $_POST['username']) > 20 || strlen($_POST['username']) < 4)
{
    $message = 'incorrect length for username';
}
elseif (strlen( $_POST['pwd']) > 20 || strlen($_POST['pwd']) < 4)
{
    $message = 'incorrect length for password';
}
elseif (ctype_alnum($_POST['username']) != true)
{
    $message = "Username must be alpha numeric";
}
elseif (ctype_alnum($_POST['pwd']) != true)
{
    $message = "Password must be alpha numeric";
}
elseif (!filter_var($_POST['Email'], FILTER_VALIDATE_EMAIL))
{
    $message = "Invalid email format";
}
elseif (ctype_alnum($_POST['Company']) != true)
{
    $message = "Company must be alpha numeric";
}
else {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $pass = filter_var($_POST['pwd'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['Email'], FILTER_SANITIZE_EMAIL);
    $fname = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING);
    $firstname = $link->real_escape_string($fname);
    $lname = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
    $lastname = $link->real_escape_string($lname);
    $company = filter_var($_POST['Company'], FILTER_SANITIZE_STRING);
    $Company = $link->real_escape_string($company);
    $pwd = password_hash($pass, PASSWORD_BCRYPT);
    $dateAdded = date('Y-m-d H:i:s');

    try {
        $sql = "SELECT 1 FROM users_enc WHERE Username = '$username'";
        if ($result = $link->query($sql)) {
            if ($result->num_rows >= 1) {
                $link->close();
                throw new Exception("Username already exists");
            } else $result->close();
        }
        $sql = "SELECT 1 FROM users_enc WHERE Email = '$email'";
        if ($result = $link->query($sql)) {
            if ($result->num_rows >= 1) {
                $link->close();
                throw new Exception("Email already is already in use");
            }
        }
        $sql = "INSERT INTO users_enc (Username, Password, Email, firstname, lastname, Created_by, Company, DateAdded) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if (!$stmt = $link->prepare($sql)) throw new mysqli_sql_exception($link->error);
        if (!$stmt->bind_param('ssssssss',
            $username,
            $pwd,
            $email,
            $firstname,
            $lastname,
            $_SESSION['username'],
            $Company,
            $dateAdded
        )) throw new mysqli_sql_exception($stmt->error);
        if (!$stmt->execute()) throw new mysqli_sql_exception($stmt->error);
        header("location: dashboard.php");
    } catch(Exception $e) {
        echo "Unable to process request: $e";
    } finally {
        if (isset($result) && is_a($result, 'mysqli_result')) $result->close();
        if (isset($stmt) && is_a($stmt, 'mysqli_stmt')) $stmt->close();
        $link->close();
    }
}
?>
<html>
    <head>
        <title>Adding user failed</title>
        <link rel="stylesheet" href="styles.css" type="text/css"/>
    </head>
    <body>
        <?php include('filestart.php');
        echo "<h1>Adding user failed</h1>";
        if (!empty($message)) echo $message;
        include('fileend.php') ?>
    </body>
</html>
