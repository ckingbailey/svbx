<?PHP
    include('SQLFunctions.php');
    Session_start();
    $table = 'severity';
    

    echo '<br>display full contents of the _POST: <br>';
    var_dump($_POST);
    
    $link = f_sqlConnect();
    $check = "SELECT * FROM $table WHERE SeverityName = '".$_POST['SeverityName']."'";
    $UserID = $_SESSION['userID'];
    
    $keys = implode(", ", (array_keys($_POST)));
    echo '<br>Parsed Key: ' .$keys;
    $values = implode("', '", (array_values($_POST)));
    echo '<br>Parsed Values: ' .$values;
    
    if(!f_tableExists($link, $table, DB_NAME)) {
        die('<br>Destination table does not exist:'.$table);
    }
    
    $result = mysqli_query($link,$check);
    $num_rows = mysqli_num_rows($result);

    if ($num_rows > 0) {
      header("location: $duplicate?msg=1");
    }
    else {
    $sql = "INSERT INTO $table($keys, lastUpdated, updatedBy) VALUES ('$values', NOW(), '$UserID')";
    //echo '<br>sql: ' .$sql;
    //echo '<br>Num_rows: ' .$num_rows;
    
    if (!mysqli_query($link,$sql)) {
		echo '<br>Error: ' .mysqli_error($link);
		if(!empty($rejectredirecturl)) {
	    	//header("location: $rejectredirecturl?msg=1");
	    	echo $sql;
    }    
	
    }else if(!empty ($rejectredirecturl)) {
            header("location: DisplaySeverities.php");
            //echo "Success";
    }
}
    
	mysqli_close($link);
?>