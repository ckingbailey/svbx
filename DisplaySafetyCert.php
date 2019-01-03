<?php
$title = "View Safety Certificate";
$Role = $_SESSION['role'];
include('filestart.php');
?>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
          <h1 class="display-3">System Safety Certification</h1>
        </div>
    </div>
    <div class="container main-content">
<?php
        echo "<p style='color:black'>Click System certificate Item Number to see full details</p>
            <table width='100%' class='deftable'>
                <tr class='svbxtr'>
                    <th class='svbxth' rowspan='2' width='5%'>Item No.</th>
                    <th class='col_2  col_3  col_4 svbxth' rowspan='2' width='35%'>Requirement</th>
                    <th class='col_3  col_4 svbxth' colspan='2' width='20%'>Design</th>
                    <th class='svbxth' colspan='3' width='30%'>Status</th>
                </tr>
                <tr>
                    <th class='col_2  col_3  col_4 svbxth' width='10%'>Code or<br />Standard</th>
                    <th class='col_3  col_4 svbxth' width='10%'>Specification<br />or Criteria</th>
                    <th class='col_2  col_3  col_4 svbxth' width='10%'>Design</th>
                    <th class='col_2  col_3  col_4 svbxth' width='10%'>Contruction</th>
                    <th class='col_2  col_3  col_4 svbxth' width='10%'>Testing</th>";
                    if($Role >= 20) 
                    {
                        echo "
                            <th class='col_1  col_2  col_3  col_4 svbxth'>Last Updated</th>
                            <th class='svbxth'>Edit</th>";
                    } else {
                        echo "
                            </tr>";
                            }
                            if($Role >= 40)
                            {
                            echo "
                                <th class='svbxth'>Delete</th>
                            </tr>";
                    }
                echo "  <tr class='svbxtr'>
                        <td class='svbxtd' style='text-align:center'><a href='ViewSC.php?DefID={$row[0]}' class='class1'>{$row[0]}</a></td>
                        <td class='col_2  col_3  col_4 svbxtd'>{$row[1]}</td>
                        <td class='col_3  col_4 svbxtd' style='text-align:center'>{$row[2]}</td>
                        <td class='col_1  col_2  col_3  col_4 svbxtd' style='text-align:center'>{$row[3]}</td>
                        <td class='svbxtd' style='text-align:center'>{$row[4]}</td>
                        <td class='col_2  col_3  col_4 svbxtd'>{$row[5]}</td>
                        <td class='svbxtd'>"; echo nl2br($row[6]);
                        echo "</td>";
                        if($Role >= 20)
                        {
                            echo "
                            <td class='col_1  col_2  col_3  col_4 svbxtd'>{$row[7]}</td>
                            <td class='svbxtd' style='text-align:center'><form action='UpdateSC.php' method='POST' onsubmit=''/>
                            <input type='hidden' name='q' value='".$row[0]."'/><input type='submit' value='Update'></form></td>";
                        } else {
                        echo "</tr>";
                            }
                            if($Role >= 40)
                            {
                            echo "
                            <td class='svbxtd' style='text-align:center'><form action='DeleteSC.php' method='POST' onsubmit='' onclick='return confirm(`ARE YOU SURE? Deficiencies should not be deleted, your deletion will be logged.`)'/>
                            <input type='hidden' name='q' value='".$row[0]."' /><input type='Submit' value='delete'></form></td>
                            </tr>";
                        }
                echo "
                </table>
                </div>";
include 'fileend.php';
