<?php 
    include('session.php');
    include('SQLFunctions.php');
    $table = 'system';
    $q = $_POST["q"];
    $title = "SVBX - Update System";
    $Loc = "SELECT SystemName FROM $table WHERE SystemID = ".$q;
    include('filestart.php');
    $link = f_sqlConnect();
    $lead = $link
        ->query("SELECT lead FROM system WHERE systemID = $q;")
        ->fetch_assoc()['lead'];
    
    if($role <= 20) {
        header('location: unauthorised.php');
    }
?>
    <div>
        <header class="container page-header">
            <h1 class="page-title">Update System</h1>
        </header>
        <?php       
            if($stmt = $link->prepare($Loc)) {
                echo
                "<div class='container'> 
                    <FORM action='UpdateSystemCommit.php' method='POST'>
                        <input type='hidden' name='SystemID' value='$q'>";
                $stmt->execute();
                $stmt->bind_result($System);
                while ($stmt->fetch()) {
                    echo "
                                <table class='table'>
                                    <tr class='usertr'>
                                        <th class='userth'>System Name:</th>
                                        <td class='usertd'>
                                            <input type='text' name='SystemName' maxlength='50' required value='$System'/>
                                        </td>
                                    </tr>
                                </table>";
                }
                $stmt->close();
                if ($res = $link->query("SELECT userid, CONCAT(firstname, ' ', lastname) AS userFullName FROM users_enc;")) {
                    echo
                    "<div>
                        <label for='lead'>Select system lead</label>
                        <select id='lead' name='lead'>
                            <option></option>";
                    while ($row = $res->fetch_assoc()) {
                        echo
                        "<option value='{$row['userid']}'";
                        if ($row['userid'] === $lead) {
                            echo ' selected';
                        }
                        echo ">{$row['userFullName']}</option>";
                    }
                    echo "</select></div>";
                }
                echo 
                        "<input type='submit' value='submit' class='btn btn-primary btn-lg'/>
                        <input type='reset' value='reset' class='btn btn-primary btn-lg' />
                    </FORM>
                </div>";
            } else {
                echo '<br>Unable to connect';
                exit();
            }
        echo "</div>";
        include('fileend.php') ?>
