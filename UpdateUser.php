<?php
    include('session.php');
    include('SQLFunctions.php');
    $table = 'users_enc';
    $targetUserID = filter_input(INPUT_GET, "userID", FILTER_SANITIZE_NUMBER_INT);
    $userRole = $_SESSION['role'];
    $title = "SVBX - Update User";
    $Loc = "SELECT Username, Role, firstname, lastname, Email, Company, inspector, bdPermit FROM $table WHERE UserID = $targetUserID";
    include('filestart.php');

    if($userRole < 30) {
        header('location: unauthorised.php');
        exit;
    }

    $link = f_sqlConnect();
?>
        <header class="container page-header">
            <h1 class="page-title">Update User Information</h1>
        </header>
<?php       if($stmt = $link->prepare($Loc)) {
            $stmt->execute();
            $stmt->bind_result($username, $targetRole, $firstname, $lastname, $email, $company, $inspector, $bdPermit);
            while ($stmt->fetch()) {
                echo "
                    <div class='container main-content'>";
                if (!empty($_SESSION['errorMsg'])) {
                    echo "
                        <div class='thin-grey-border bg-yellow pad'>
                            <p class='mt-0 mb-0'>{$_SESSION['errorMsg']}</p>
                        </div>";
                    unset($_SESSION['errorMsg']);
                }
                if ($userRole >= 30) {
                echo "
                        <form action='UpdateUserCommit.php' method='POST' onsubmit='' />
                            <input type='hidden' name='userID' value='$targetUserID'>
                            <table class='table'>
                                <tr class='usertr'>
                                    <th class='userth'>First name:</td>
                                    <td class='usertd'>
                                        <input type='text' name='firstname' maxlength='25' required value='$firstname'/>
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <th class='userth'>Last name:</td>
                                    <td class='usertd'>
                                        <input type='text' name='lastname' maxlength='25' required value='$lastname'/>
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <th class='userth'>Email Address:</td>
                                    <td class='usertd'>
                                        <input type='text' name='email' maxlength='55' required value='$email'/>
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <th class='userth'>Username:</td>
                                    <td class='usertd'>
                                        <input type='text' name='username' maxlength='25' required value='$username'/>
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <th class='userth'>Company:</td>
                                    <td class='usertd'>
                                        <input type='text' name='company' maxlength='25' required value='$company'/>
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <th class='userth'>Password:</td>
                                    <td class='usertd'>
                                        <input type='password' name='password' maxlength='25' value=''/><br />
                                        <i>only complete if you want to change a users password</i>
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <th class='userth'>Confirm Password:</td>
                                    <td class='usertd'>
                                        <input type='password' name='conPwd' maxlength='25' value=''/><br />
                                        <i>confirm new password</i>
                                    </td>
                                </tr>";
                                if ($userRole >= 40) {
                                echo "
                                <tr class='usertr'>
                                    <th class='userth' rowspan='4'>Role:</td>
                                    <td class='usertd'>
                                        <input type='radio' name='role' value='40'";
                                        echo ( $targetRole === 40 ? ' checked' : '' );
                                        echo "/>Super Admin
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <td class='usertd'>
                                        <input type='radio' name='role' value='30'";
                                        echo ( $targetRole === 30 ? ' checked' : '' );
                                        echo "/>Administrator
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <td class='usertd'>
                                        <input type='radio' name='role' value='20'";
                                        echo ( $targetRole === 20 ? ' checked' : '' );
                                        echo "/>User
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <td class='usertd'>
                                        <input type='radio' name='role' value='10'";
                                        echo ( $targetRole === 10 ? ' checked' : '' );
                                        echo "/>Read Only
                                    </td>
                                </tr>";
                                } elseif ($userRole >= 30) {
                                echo "
                                <tr class='usertr'>
                                    <th class='userth' rowspan='3'>Role:</td>
                                    <td class='usertd'>
                                        <input type='radio' name='role' value='30'";
                                        echo ( $targetRole === 30 ? ' checked' : '' );
                                        echo "/>Administrator
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <td class='usertd'>
                                        <input type='radio' name='role' value='20'";
                                        echo ( $targetRole === 20 ? ' checked' : '' );
                                        echo "/>User
                                    </td>
                                </tr>
                                <tr class='usertr'>
                                    <td class='usertd'>
                                        <input type='radio' name='role' value='10'";
                                        echo ( $targetRole === 10 ? ' checked' : '' );
                                        echo "/>Read Only
                                    </td>
                                </tr>";
                                } else {
                                }
                                echo "
                                </table>
                                <div id='inspector-fieldset' class='row item-margin-bottom'>
                                    <h6 class='col-md-4'>Can create Inspector Daily Reports?</h6>
                                    <div id='inspector-options' class='col-md-8'>
                                        <input type='radio' id='inspector-yes' name='inspector' value='1' required ";
                                        echo ($inspector === 1 ? 'checked' : '');
                                echo "
                                        />
                                        <label for='inspector-yes'>Yes</label>
                                        <input type='radio' id='inspector-no' name='inspector' value='0' required ";
                                        echo ($inspector === 0 ? 'checked' : '');
                                echo "
                                        />
                                        <label for='inspector-no'>No</label>
                                    </div>
                                </div>
                                <div class='row item-margin-bottom'>
                                    <h6 class='col-md-4'>Can see BART deficiencies?</h6>
                                    <div class='col-md-8'>
                                        <input type='radio' id='bdPermit_yes' name='bdPermit' value='1' ";
                                        echo ($bdPermit ? 'checked' : '');
                                        error_log('bart permission: ' . $bdPermit);
                                echo "
                                        />
                                        <label for='bdPermit_yes'>Yes</label>
                                        <input type='radio' id='bdPermit_no' name='bdPermit' value='0' ";
                                        echo ($bdPermit ? '' : 'checked');
                                echo "
                                        />
                                        <label for='bdPermit_no'>No</label>
                                    </div>
                                </div>
                                <div class='row item-margin-bottom'></div>
                                <input type='submit' value='submit' class='btn btn-primary btn-lg'/>
                                <input type='reset' value='reset' class='btn btn-primary btn-lg' />
                            </form>";
                        } else {
                            echo "<p style='text-align:center'>You do not have the authority to amend this user";
                        }
                        echo "</div>";
                    }
                } else {
                    echo '<br>Unable to connect';
                    echo '<br />SQL: '.$Loc;
                    echo '<br />UserID: '.$targetUserID;
                    exit();
                }
        include('fileend.php') ?>
