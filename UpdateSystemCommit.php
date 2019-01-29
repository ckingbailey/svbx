<?php
include('SQLFunctions.php');
session_start();

if(!empty($_POST)) {
    $SystemID = $_POST['SystemID'];
    $System = $_POST['SystemName'];
    $lead = filter_var($_POST['lead'], FILTER_SANITIZE_NUMBER_INT);
    $UserID = $_SESSION['userID'];
    $link = f_sqlConnect();
    
    $user = "SELECT username FROM users_enc WHERE UserID = ".$UserID;
    if($result=mysqli_query($link,$user)) 
        {
          /*from the sql results, assign the username that returned to the $username variable*/    
          while($row = mysqli_fetch_assoc($result)) {
            $Username = $row['username'];
          }
        }
    
    $sql = "UPDATE system
            SET SystemName = '$System'
                , lead = '$lead'
                , updatedBy = '$UserID'
                ,lastUpdated = NOW()
            WHERE SystemID = $SystemID";

            if(mysqli_query($link,$sql)) {
                echo "<br>Update Completed successfully";
        } else {
            echo "<br>Error: " .$sql. "<br>" .mysqli_error($link);
        }
        mysqli_close($link);
        header("Location: DisplaySystems.php?msg=1");
        //echo "<br>Username: ".$Username;
        //echo "<br>UserID: ".$user;        
}
?>