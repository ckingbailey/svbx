<?php
    require_once('SQLFunctions.php');
    include('session.php');
    
    /* Check that question & answer are populated */
    if(!isset( $_POST['SecA'], $_POST['SecQ']))
    {
        $message = 'Please select a question and enter an answer';
    }
    /* Check answer for alpha numeric characters */
    elseif (ctype_alnum($_POST['SecA']) != true)
    {
        $message = "Answer must be alpha numeric";
    }
    else
    {
    
    //if(isset($_POST['submit'])) {
        
        $link = f_sqlConnect();
        $SecQ = $_POST['SecQ'];
        $seca = $_POST['SecA'];
        $SecA = filter_var($seca, FILTER_SANITIZE_STRING);
        $Username = $_SESSION['username'];
        
        if(!isset($Username)) {
            $message = "No user logged in";
        } else {
        
        $query = "
            UPDATE 
                users_enc 
            SET 
                SecQ = '$SecQ'
                ,SecA = '$SecA'
                ,updated_By = '$Username'
                ,LastUpdated = NOW()
            WHERE 
                Username = '$Username'";
            
        mysqli_query($link, $query) or
            die("Insert failed. " . mysqli_error($link));
              
            //$message = "<p class='message'>Your security question has been saved</p>";
            header('location: dashboard.php');
            } 
    }
mysqli_close($link);
?>
<HTML>
    <HEAD>
        <TITLE>
            SVBX - LOGON
        </TITLE>
        <link rel="stylesheet" href="styles.css" type="text/css"/>
    </HEAD>
    <BODY>
<?php
    include('filestart.php');
    if (!empty($message)) echo $message;
    include 'fileend.php';
?>
    </BODY>
</HTML>
