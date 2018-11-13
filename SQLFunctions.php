<?php
    require_once 'vendor/autoload.php';

    $rejectredirecturl = 'Fail.html';
    $successredirecturl = 'dashboard.php';
    $duplicate = 'duplicate.php'; // this file doesn't exist

    function connect() {
        return new MysqliDB(DB_HOST, DB_USER, DB_PWD, DB_NAME);
    }

    function f_sqlConnect() {
        $link = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        if ($link->connect_error) {
            die("Connection failed: " .$link->connect_error);

        }
        //echo "<br>Connected successfully to the database<br><br>";
        return $link;

    }

    function f_sqlConnect1() {
        $Link = new mysqli(DB_HOST, DB_USER, DB_PWD, DB_NAME);
        if ($Link->connect_error) {
            die("Connection failed: " .$Link->connect_error);

        }
        //echo "<br>Connected successfully to the database<br><br>";
        return $Link;

    }

function f_validIP($ip) {
    if (empty($ip) && ip2long($ip)!=-1) {
        /*Create an array of arrays standard non routable IPs so we can filter out IPs that aren't useful*/
        $reserved_ips = array (
            array('0.0.0.0','2.255.255.255'),
            array('10.0.0.0','10.255.255.255'),
            array('127.0.0.0','127.255.255.255'),
            array('169.254.0.0','169.254.255.255'),
            array('172.16.0.0','172.31.255.255'),
            array('192.0.2.0','192.0.2.255'),
            array('192.168.0.0','192.168.255.255'),
            array('255.255.255.0','255.255.255.255'),
        );
            /*Compare the IP to each array and return a false if the IP is within any of the ranges*/

        foreach ($reserved_ips as $r) {
            $min = ip2long($r[0]);
            $max = ip2long($r[1]);
            if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
        }
                /*if the ip is opulated and isn't in the non routable range, return true*/
        return true;
    } else {
            return false;
    }

}

function f_getIP() {
    if (f_validip($_SERVER["HTTP_CLIENT-IP"])) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
        if (f_validIP(trim($ip))) {
            return $ip;
            }
        }
    if (f_validIP($SERVER["HTTP_X_FORWARDED"])) {
        return $_SERVER["HTTP_X_FORWARDED"];
    } elseif (f_validIP($_SERVER["HTTP_FORWARDED_FOR"])) {
        return $_SERVER["HTTP_FORWARDED_FOR"];
    } elseif (f_validIP($_SERVER["HTTP_FORWARDED"])) {
        return $_SERVER["HTTP_FORWARDED"];
    } elseif (f_validIP($_SERVER["HTTP_X_FORWARDED"])) {
        return $_SERVER["HTTP_X_FORWARDED"];
    } else {
        return $_SERVER["REMOTE_ADDR"];
    }
}

function f_tableExists(mysqli $link,$tablename,$database = false) {
    if(!$database) {
        $res = mysqli-query($link, "SELECT_DATABASE()"); // the '-' here is a typo. don't correct it until you are ready to debug the consequences
        $database = mysqli_result($res, 0);
    }
    $res = mysqli_query($link, "SELECT *
                                FROM information_schema.tables
                                WHERE table_schema = '$database'
                                AND table_name = '$tablename'");
   // echo '<br>Table exists: ' .($res->num_rows);
    return $res->num_rows == 1;
}

function checkUNEmail($uname,$email)
{
    global $link;
    $error = array('status'=>false,'userID'=>0);
    if (isset($email) && trim($email) != '') {
        //email was entered
        if ($SQL = $link->prepare("SELECT `UserID` FROM `users_enc` WHERE `Email` = ? LIMIT 1"))
        {
            $SQL->bind_param('s',trim($email));
            $SQL->execute();
            $SQL->store_result();
            $numRows = $SQL->num_rows();
            $SQL->bind_result($UserID);
            $SQL->fetch();
            $SQL->close();
            if ($numRows >= 1) return array('status'=>true,'userID'=>$UserID);
        } else { return $error; }
    } elseif (isset($uname) && trim($uname) != '') {
        //username was entered
        if ($SQL = $link->prepare("SELECT `UserID` FROM `users_enc` WHERE Username = ? LIMIT 1"))
        {
            $SQL->bind_param('s',trim($uname));
            $SQL->execute();
            $SQL->store_result();
            $numRows = $SQL->num_rows();
            $SQL->bind_result($UserID);
            $SQL->fetch();
            $SQL->close();
            if ($numRows >= 1) return array('status'=>true,'userID'=>$UserID);
        } else { return $error; }
    } else {
        //nothing was entered;
        return $error;
    }
}

function getSecurityQuestion($UserID)
{
    global $link;
    $questions = array();
    $questions[1] = "What is your mother's maiden name?";
    $questions[2] = "Which city were you born in?";
    $questions[3] = "Which is your favorite color?";
    $questions[4] = "Which year did you graduate from High School?";
    $questions[5] = "What was the name of your first boyfriend/girlfriend?";
    $questions[6] = "What was your first make of car?";
    if ($SQL = $link->prepare("SELECT `secQ` FROM `users_enc` WHERE `UserID` = ? LIMIT 1"))
    {
        $SQL->bind_param('i',$UserID);
        $SQL->execute();
        $SQL->store_result();
        $SQL->bind_result($secQ);
        $SQL->fetch();
        $SQL->close();
        return $questions[$secQ];
    } else {
        return false;
    }
}

function checkSecAnswer($UserID,$answer)
{
    global $link;
    if ($SQL = $link->prepare("SELECT `Username` FROM `users_enc` WHERE `UserID` = ? AND LOWER(`secA`) = ? LIMIT 1"))
    {
        $answer = strtolower($answer);
        $SQL->bind_param('is',$UserID,$answer);
        $SQL->execute();
        $SQL->store_result();
        $numRows = $SQL->num_rows();
        $SQL->close();
        if ($numRows >= 1) { return true; }
    } else {
        return false;
    }
}

function sendPasswordEmail($UserID)
{
    global $link;
    if ($SQL = $link->prepare("SELECT `Username`,`Email`,`Password` FROM `users_enc` WHERE `UserID` = ? LIMIT 1"))
    {
        $SQL->bind_param('i',$UserID);
        $SQL->execute();
        $SQL->store_result();
        $SQL->bind_result($uname,$email,$pword);
        $SQL->fetch();
        $SQL->close();
        $expFormat = mktime(date("H")+1, date("i"), date("s"), date("m")  , date("d"), date("Y"));
        $expDate = date("Y-m-d H:i:s",$expFormat);
        $key = md5($uname . '_' . $email . rand(0,10000) .$expDate . PW_SALT);
        if ($SQL = $link->prepare("INSERT INTO `recoveryemails_enc` (`UserID`,`Key`,`expDate`) VALUES (?,?,?)"))
        {
            $SQL->bind_param('iss',$UserID,$key,$expDate);
            $SQL->execute();
            $SQL->close();
            $passwordLink = "<a href=\"?a=recover&email=" . $key . "&u=" . urlencode(base64_encode($UserID)) . "\">https://deflist-rob1st.c9users.io/ForgotPassword.php?a=recover&email=" . $key . "&u=" . urlencode(base64_encode($UserID)) . "</a>";
            $message = "Dear $uname,\r\n";
            $message .= "Please visit the following link to reset your password:\r\n";
            $message .= "-----------------------\r\n";
            $message .= "$passwordLink\r\n";
            $message .= "-----------------------\r\n";
            $message .= "Please be sure to copy the entire link into your browser. The link will expire after 1 hour for security reasons.\r\n\r\n";
            $message .= "If you did not request this forgotten password email, no action is needed, your password will not be reset as long as the link above is not visited. However, you may want to log into your account and change your security password and answer, as someone may have guessed it.\r\n\r\n";
            $message .= "Thanks,\r\n";
            $message .= "-- SVBX Project Team";
            $headers .= "From: SVBX Project Team <webmaster@svbx.us> \n";
            $headers .= "To-Sender: \n";
            $headers .= "X-Mailer: PHP\n"; // mailer
            $headers .= "Reply-To: webmaster@svbx.us\n"; // Reply address
            $headers .= "Return-Path: webmaster@svbx.us\n"; //Return Path for errors
            $headers .= "Content-Type: text/html; charset=iso-8859-1"; //Enc-type
            $subject = "Your Password Reset Link";
            @mail($email,$subject,$message,$headers);
            return str_replace("\r\n","<br/ >",$message);
        }
    }
}
function checkEmailKey($key,$UserID)
{
    global $link;
    $curDate = date("Y-m-d H:i:s");
    if ($SQL = $link->prepare("SELECT `UserID` FROM `recoveryemails_enc` WHERE `Key` = ? AND `UserID` = ? AND `expDate` >= ?"))
    {
        $SQL->bind_param('sis',$key,$UserID,$curDate);
        $SQL->execute();
        $SQL->execute();
        $SQL->store_result();
        $numRows = $SQL->num_rows();
        $SQL->bind_result($UserID);
        $SQL->fetch();
        $SQL->close();
        if ($numRows > 0 && $UserID != '')
        {
            return array('status'=>true,'userID'=>$UserID);
        }
    }
    return false;
}

function updateUserPassword($UserID,$pw0,$key)
{
    global $link;
    if (checkEmailKey($key,$UserID) === false) return false;
    if ($SQL = $link->prepare("UPDATE `users_enc` SET `Password` = ? WHERE `UserID` = ?"))
    {
        $password = password_hash($pw0, PASSWORD_BCRYPT);
        $SQL->bind_param('si',$password,$UserID);
        $SQL->execute();
        $SQL->close();
        $SQL = $link->prepare("DELETE FROM `recoveryemails_enc` WHERE `Key` = ?");
        $SQL->bind_param('s',$key);
        $SQL->execute();
    }
}

function getUserName($UserID)
{
    global $link;
    if ($SQL = $link->prepare("SELECT `Username` FROM `users_enc` WHERE `UserID` = ?"))
    {
        $SQL->bind_param('i',$UserID);
        $SQL->execute();
        $SQL->store_result();
        $SQL->bind_result($uname);
        $SQL->fetch();
        $SQL->close();
    }
    return $uname;
}
?>
