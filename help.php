<?php
include('SQLFunctions.php');
session_start();


    $title = "SVBX - Help";
    $link = f_sqlConnect();
    include('filestart.php');
?>

        <header class="container page-header">
            <h1 class="display-3">Help</h1>
            <p>If you are having difficulties with the database please contact one of the people below who have administrator rights,<br>otherwise email <a href="mailto:robert.burns@vta.org?subject=SVBX Deficiency Database Issue"  style='color:black'>Robert Burns</a> for assistance.</p>
        </header>
        <main class="container main-content">        
<?php
    $table = 'users_enc';
    
    $sql = "SELECT firstname, lastname, Email FROM $table WHERE Role >= 40 ORDER BY lastname";
    
    if($result = mysqli_query($link,$sql)) {
        echo"   <table class='table svbx-table'>
                    <tr class='svbx-tr'>
                        <th class='svbx-th'>First name</th>
                        <th class='svbx-th'>Last name</th>
                        <th class='svbx-th'>Email</th>
                    </tr>"; 
                    while ($row = mysqli_fetch_array($result)) {
                    echo "       
                    <tr class='svbx-tr'>
                        <td class='svbx-td'>{$row[0]}</td>
                        <td class='svbx-td'>{$row[1]}</td>
                        <td class='svbx-td'><a href='mailto:{$row[2]}' style='color:black'>{$row[2]}</a></td>
                    </tr>";
                    }
    }
            echo "</table><br></main>";
    mysqli_free_result($result);
    
    if(mysqli_error($link)) {
        echo '<br>Error: ' .mysqli_error($link);
    } else //echo '<br>Success';
    
    mysqli_close($link);
    include('fileend.php') 
?>