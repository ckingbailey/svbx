<?php
include 'session.php';
include('SQLFunctions.php');

$title = "SVBX - Inspector's Daily Report";
$d = new DateTime();
$curDateNum = $d->format('Y-m-d');

$userID = $_SESSION['userID'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$contractQry = 'SELECT contractID, contractName FROM contract';

$userFullName = "{$_SESSION['firstname']} {$_SESSION['lastname']}";

$userAuth = ($_SESSION['inspector'] || $role >= 30) ? $role : 0;

$locQry = "SELECT LocationID, LocationName FROM Location ORDER BY LocationID";

if ($userAuth < 10) {
    // view is unauthorized to view
    include 'unauthorised.php';
} else {
    include('filestart.php');
    $link = f_sqlConnect();

    // view is authorized at some level
    echo "
        <header class='container page-header'>
            <h1 class='page-title'>Inspector's Daily Report</h1>
        </header>
        <main class='container main-content'>";
    if (isset($_GET['idrID'])) {
        $idrID = $_GET['idrID'];
        $idrQry = "SELECT idrID, i.userID, firstname, lastname, idrForDate, contractName, weather, shift, EIC, watchman, rapNum, sswpNum, tcpNum, approvedBy, editableUntil
            FROM ((IDR i
            LEFT JOIN users_enc u ON
            i.userID=u.userID)
            LEFT JOIN contract c ON
            i.contractID=c.contractID)
            WHERE i.idrID=$idrID";
        $laborQry = "SELECT laborID, laborTotal, laborDesc, idrID, laborNotes, LocationName FROM
            labor la JOIN
            location L ON
            la.LocationID=L.LocationID
            WHERE la.idrID=$idrID";
        $equipQry = "SELECT equipID, equipTotal, equipDesc, idrID, equipNotes, LocationName FROM
            equipment e JOIN
            location L ON
            e.LocationID=L.LocationID
            WHERE e.idrID=$idrID";

        if ($result = $link->query($idrQry)) {
            $numRows = intval($result->num_rows);

            if ($numRows) {
                while ($row = $result->fetch_assoc()) {
                    $expiry = new DateTime($row['editableUntil']);
                    $approved = $row['approvedBy'];

                    if ($row['userID'] === $userID || $userAuth > 1) {
                        // review view + comments
                        // + Approve btn if $userAuth > 1
                        echo ("
                        <h3 class='center-content font-italic'>Review</h3>
                        <div class='row item-margin-bottom'>
                            <div class='col-md-6'>
                                <div class='card h-100'>
                                    <div class='card-header grey-bg'>
                                        <h6 class='flex-row space-between'>
                                            <span>Inspector Name</span>
                                            <span>{$row['firstname']} {$row['lastname']}</span>
                                            <input type='hidden' name='userID' value='{$userID}' />
                                        </h6>
                                    </div>
                                    <div class='card-body'>
                                        <ul>
                                            <li class='flex-row space-between'>
                                                <span style='font-style: bold'>date</span>
                                                <span>{$row['idrForDate']}</span>
                                            </li>
                                            <li class='flex-row space-between'>
                                                <span>Contract</span>
                                                <span>{$row['contractName']}</span>
                                            </li>
                                            <li class='flex-row space-between'>
                                                <span>weather</span>
                                                <span>{$row['weather']}</span>
                                            </li>
                                            <li class='flex-row space-between'>
                                                <span>shift hours</span>
                                                <span>{$row['shift']}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <div class='card h-100'>
                                    <div class='card-header grey-bg'><h6>Track safety</h6></div>
                                    <div class='card-body'>
                                        <ul>");
                                        $safety = [
                                            'EIC' => $row['EIC'],
                                            'Watchman' => $row['watchman'],
                                            'Rap #' => $row['rapNum'],
                                            'SSWP #' => $row['sswpNum'],
                                            'TCP #' => $row['tcpNum']
                                        ];
                                        foreach ($safety as $key => $val) {
                                            if (!$val) {
                                                echo "
                                                <li class='flex-row space-between'>
                                                    <span style='font-style: bold'>$key</span>
                                                    <span class='text-secondary font-italic'>none</span>
                                                </li>";
                                            } else {
                                                echo "
                                                <li class='flex-row space-between'>
                                                    <span style='font-style: bold'>$key</span>
                                                    <span>$val</span>
                                                </li>";
                                            }
                                        }
                        echo ("
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>");
                        // iterate over results and display as nested <ul>s
                        if ($result = $link->query($laborQry)) {
                            $linkingT = 'laborAct_link';
                            $resourceT = 'labor';
                            $resourceID = 'laborID';
                            while ($row = $result->fetch_assoc()) {
                                $actQry = "SELECT * FROM (($linkingT link
                                    JOIN $resourceT rsrc ON
                                    link.{$resourceID}=rsrc.{$resourceID}
                                    AND rsrc.{$resourceID}={$row[$resourceID]})
                                    join activity a on
                                    link.activityID=a.activityID)";

                                echo "
                                <div class='row'>
                                    <p class='col-md-4'>
                                        <span class='d-block text-secondary'>Location</span>
                                        <span class='d-block border-radius thin-grey-border grey-bg pad-less'>{$row['LocationName']}</span>
                                    </p>
                                    <p class='col-md-2'>
                                        <span class='d-block text-secondary'># personnel:</span>
                                        <span class='d-block border-radius thin-grey-border grey-bg pad-less'>{$row['laborTotal']}</span>
                                    </p>
                                    <p class='col-md-6'>
                                        <span class='d-block text-secondary'>Description of labor:</span>
                                        <span class='d-block border-radius thin-grey-border grey-bg pad-less'>{$row['laborDesc']}</span>
                                    </p>
                                </div>";
                                if ($row['laborNotes']) {
                                    echo "
                                    <div class='row'>
                                        <p class='col-12 mb-2 thin-grey-border'>{$row['laborNotes']}</p>
                                    </div>";
                                }
                                if ($actResult = $link->query($actQry)) {
                                    echo "
                                    <div class='row'>
                                        <ul class='col'>";
                                        while ($row = $actResult->fetch_assoc()) {
                                            echo "
                                            <li class='row striped pad-less'>
                                                <p class='col-6 offset-md-1 mb-0'>
                                                    <span class='text-secondary font-italic mr-md-2'>task:</span>
                                                    <span> {$row['actDesc']}</span>
                                                </p>
                                                <p class='col-2 mb-0'>
                                                    <span class='text-secondary font-italic mr-md-2'># pers.:</span>
                                                    <span> {$row['numResources']}</span>
                                                </p>
                                                <p class='col-2 mb-0'>
                                                    <span class='text-secondary font-italic mr-md-2'>hours:</span>
                                                    <span>{$row['actHrs']}</span>
                                                </p>
                                            </li>";
                                        }
                                    echo "</ul></div>";
                                }
                            }
                        } else echo "<pre style='font-size: 2rem; color: orangeRed;'>{$link->error}</pre>";
                        if ($result = $link->query($equipQry)) {
                            $linkingT = 'equipAct_link';
                            $resourceT = 'equipment';
                            $resourceID = 'equipID';
                            while ($row = $result->fetch_assoc()) {
                                $actQry = "SELECT * FROM (($linkingT link
                                    JOIN $resourceT rsrc ON
                                    link.{$resourceID}=rsrc.{$resourceID}
                                    AND rsrc.{$resourceID}={$row[$resourceID]})
                                    join activity a on
                                    link.activityID=a.activityID)";

                                echo "
                                <div class='row'>
                                    <p class='col-md-4'>
                                        <span class='d-block text-secondary'>Location</span>
                                        <span class='d-block border-radius thin-grey-border grey-bg pad-less'>{$row['LocationName']}</span>
                                    </p>
                                    <p class='col-md-2'>
                                        <span class='d-block text-secondary'># equipment:</span>
                                        <span class='d-block border-radius thin-grey-border grey-bg pad-less'>{$row['equipTotal']}</span>
                                    </p>
                                    <p class='col-md-6'>
                                        <span class='d-block text-secondary'>Description of equip:</span>
                                        <span class='d-block border-radius thin-grey-border grey-bg pad-less'>{$row['equipDesc']}</span>
                                    </p>
                                </div>";
                                if ($row['equipNotes']) {
                                    echo "
                                    <div class='row'>
                                        <p class='col-12 thin-grey-border'>{$row['equipNotes']}</p>
                                    </div>";
                                }
                                if ($actResult = $link->query($actQry)) {
                                    echo "
                                    <div class='row'>
                                        <ul class='col'>";
                                        while ($row = $actResult->fetch_assoc()) {
                                            echo "
                                            <li class='row striped pad-less'>
                                                <p class='col-6 offset-md-1 mb-0'>
                                                    <span class='text-secondary font-italic mr-md-2'>task:</span>
                                                    <span> {$row['actDesc']}</span>
                                                </p>
                                                <p class='col-2 mb-0'>
                                                    <span class='text-secondary font-italic mr-md-2'># equip.:</span>
                                                    <span> {$row['numResources']}</span>
                                                </p>
                                                <p class='col-2 mb-0'>
                                                    <span class='text-secondary font-italic mr-md-2'>hours:</span>
                                                    <span>{$row['actHrs']}</span>
                                                </p>
                                            </li>";
                                        }
                                    echo "</ul></div>";
                                }
                            }
                        } else echo "<pre style='font-size: 2rem; color: orangeRed;'>{$link->error}</pre>";
                        // comment & Approve elements
                        echo ("
                        <hr />
                        <form class='item-margin-bottom'>
                            <input type='hidden' name='idrID' value='$idrID' />
                            <input type='hidden' name='userID' value='$userID' />
                            <div class='row item-margin-bottom'>
                                <div class='col-md-6 offset-md-3'>
                                    <label>Comment</label>
                                    <textarea id='commentBox' name='comment' class='form-control' rows='5'></textarea>
                                </div>
                            </div>");
                        if (!$approved && $userAuth > 1) {
                            echo "
                            <div class='row item-margin-bottom'>
                                <div class='col center-content'>
                                    <button type='button' class='btn btn-lg btn-primary' onclick='return submitAndApprove(event);'>Approve</button>
                                </div>
                            </div>
                            <div class='row item-margin-bottom'>
                                <div class='col center-content'>
                                    <button type='button' class='btn btn-light text-secondary' onclick='return submitNoApprove(event);'><u>Request revisions</u></button>
                                </div>
                            </div>";
                        } else {
                            echo "
                            <div class='row item-margin-bottom'>
                                <div class='col center-content'>
                                    <button type='button' class='btn btn-lg btn-primary' onclick='return submitNoApprove(event);'>Add comment</button>
                                </div>
                            </div>";
                        }
                        echo "
                        </form>";
                        // query for comments
                        $commentQry = "SELECT idrCommentID, commentDate, firstname, lastname, idrID, comment FROM
                            idrComments ic JOIN
                            users_enc ue ON
                            ic.userID = ue.userID
                            AND idrID=$idrID";
                        if ($result = $link->query($commentQry)) {
                            while ($row = $result->fetch_assoc()) {
                                echo "
                                <div class='row'>
                                    <div class='col-md-8 offset-md-2'>
                                        <blockquote class='blockquote pad border-radius thin-grey-border'>
                                            <p class='mb-2'>{$row['comment']}</p>
                                            <footer class='blockquote-footer'>{$row['firstname']} {$row['lastname']}, <cite class='title'>{$row['commentDate']}</cite></footer>
                                        </blockquote>
                                    </div>
                                </div>";
                            }
                        } else echo "<pre class='text-danger'>{$result->error}</pre>";
                        echo ("
                        <script>
                            function submitAndApprove(ev) {
                                submitReview(ev, 'true');
                            }

                            function submitNoApprove(ev) {
                                submitReview(ev, 'false');
                            }

                            function submitReview(ev, approval) {
                                ev.preventDefault();
                                const formData = new FormData(document.forms[0]);
                                formData.append('userID', {$userID});
                                formData.append('approve', approval);
                                window.fetch('submitIdrReview.php',
                                    {
                                        method: 'POST',
                                        body: formData
                                    }
                                ).then(res => {
                                    if (res.ok) return res.text();
                                    else {
                                        res.text().then(text => window.alert(text));
                                    }
                                })
                                .then(text => {
                                    window.alert(text);
                                    window.location.reload();
                                    return;
                                })
                            }
                        </script>");
                    } else {
                        http_response_code(401);
                        $code = http_response_code();
                        echo "<pre>";
                        var_dump($row);
                        echo "</pre>";
                        echo "
                        <ul>
                            <li>$code</li>
                            <li>$role</li>
                            <li>$userAuth</li>
                            <li>{$row['userID']} === $userID</li>
                            <li>{$row['approvedBy']}</li>
                        </ul>";
                        return;
                    }
                }
            } else {
                http_response_code(404);
                $code = http_response_code();
                echo "
                <div class='row'>
                    <div class='col-md-6 offset-md-3'>
                        <h4 class='text-center'>$code</h4>
                        <p class='text-light text-center bg-info pad'>No report found here</p>
                        <p class='text-primary text-center'>$numRows</p>
                    </div>
                </div>";
                return;
            }
        } else {
            http_response_code(500);
            $code = http_response_code();
            echo "
            <p class='text-danger text-center'>There was a problem retrieving the report data</p>
            <p class='text-secondary text-center'>{$link->error}</p>";
            return;
        }
    echo "</main>";
    } else {
        // initial input view
        echo "
            <h6><span class='text-danger'>*</span><span> = required</span></h6>
            <div class='row'>
                <form class='col-12' id='dailyReportForm'>
                    <div class='row item-margin-bottom'>
                        <div class='col-md-6' id='dayData'>
                            <div class='card h-100'>
                                <div class='card-header grey-bg'>
                                    <h6 class='flex-row space-between'>
                                        <span class='item-margin-right'>Inspector Name</span>
                                        <span>{$userFullName}</span>
                                    </h6>
                                    <input type='hidden' name='userID' value='{$userID}' />
                                </div>
                                <div class='card-body'>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label item-margin-right'>Date</label>
                                        </div>
                                        <div class='col-6'>
                                            <input name='idrForDate' type='date' value='{$curDateNum}' id='curDate' class='form-control' />
                                        </div>
                                    </div>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label item-margin-right required'>Contract</label>
                                        </div>
                                        <div class='col-6'>
                                            <select name='contractID' class='form-control' required>";
                                                if ($result = $link->query($contractQry)) {
                                                    while ($row = $result->fetch_array()) {
                                                        if ($row[1] === 'C700') $default='selected';
                                                        else $default = '';
                                                        echo "<option value ='{$row[0]}' $default>{$row[1]}</option>";
                                                    }
                                                } else echo "<option value=''>{$result->error}</option>";
                echo "
                                            </select>
                                        </div>
                                    </div>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label item-margin-right required'>Weather</label>
                                        </div>
                                        <div class='col-6'>
                                            <input type='text' id='weatherDescrip' name='weather' class='form-control' required />
                                        </div>
                                    </div>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label item-margin-right required'>Shift Hrs</label>
                                        </div>
                                        <div class='col-6'>
                                            <input type='text' id='shiftHrs' name='shift' class='form-control' required />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='col-md-6' id='safetyData'>
                            <div class='card h-100'>
                                <div class='card-header grey-bg'>
                                    <h6>Track safety</h6>
                                </div>
                                <div class='card-body'>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label'>EIC</label>
                                        </div>
                                        <div class='col-6'>
                                            <input type='text' id='eic' name='EIC' class='form-control' />
                                        </div>
                                    </div>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label'>Watchman</label>
                                        </div>
                                        <div class='col-6'>
                                            <input type='text' id='watchman' name='watchman' class='form-control' />
                                        </div>
                                    </div>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label'>RAP #</label>
                                        </div>
                                        <div class='col-6'>
                                            <input type='text' id='rapNum' name='rapNum' class='form-control' />
                                        </div>
                                    </div>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label'>SSWP #</label>
                                        </div>
                                        <div class='col-6'>
                                            <input type='text' id='sswpNum' name='sswpNum' class='form-control' />
                                        </div>
                                    </div>
                                    <div class='row item-margin-bottom'>
                                        <div class='col-6'>
                                            <label class='input-label'>TCP #</label>
                                        </div>
                                        <div class='col-6'>
                                            <input type='text' id='tcpNum' name='tcpNum' class='form-control' />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id='workInputList' class='row item-margin-bottom'>
                        <div id='workInputGroup_0' class='col-12 item-border-bottom item-margin-bottom'>
                            <div class='row item-margin-bottom'>
                                <div class='col-md-2 pl-1 pr-1'>
                                    <label class='input-label required'>Location</label>
                                    <select id='locationID_0' name='laborLocationID_0' class='form-control' required>";
                                    if ($result = $link->query($locQry)) {
                                        $locJSON = array();
                                        while ($row = $result->fetch_assoc()) {
                                            $locJSON[$row['LocationID']] = $row['LocationName'];
                                            echo "<option value='{$row['LocationID']}'>{$row['LocationName']}</option>";
                                        }
                                        $locJSON = json_encode($locJSON);
                                    } else echo "<option value=''>{$result->error}</option>";
            echo "
                                    </select>
                                </div>
                                <div class='col-md-2 pl-1 pr-1'>
                                    <label class='input-label required'>Equip/Labor</label>
                                    <select id='selectEquipLabor_0' class='form-control' required>
                                        <option value='labor' selected>Labor</option>
                                        <option value='equipment'>Equipment</option>
                                    </select>
                                </div>
                                <div class='col-md-5 pl-1 pr-1'>
                                    <label id='labelDescEquipLabor_0' class='input-label required'>Description of labor</label>
                                    <input type='text' id='equipOrLaborDesc_0' name='laborDesc_0' class='form-control full-width' required>
                                </div>
                                <div class='col-md-2 pl-1 pr-1 mw-50'>
                                    <label id='labelTotalEquipLabor_0' class='input-label required'>Tot. Personnel</label>
                                    <input type='number' id='equipOrLaborTotal_0' name='laborTotal_0' class='form-control' required>
                                </div>
                                <div class='col-md-1 pl-1 pr-1 flex-column align-end mw-50'>
                                    <label class='input-label'>Notes</label>
                                    <button type='button' id='showNotes_0' class='form-control' style='width: 40px'><i class='typcn typcn-document-text'></i></button>
                                    <aside
                                        id='notesField_0'
                                        style='
                                            display: none;
                                            position: absolute;
                                            right: 50px;
                                            bottom: -2px;
                                            border: 1px solid #3333;
                                            width: 260px;
                                            padding: .25rem;
                                            background-color: white;
                                        '
                                    >
                                        <textarea name='laborNotes_0' id='notes_0' rows='5' cols='30' maxlength='125' class='form-control'></textarea>
                                    </aside>
                                </div>
                            </div>
                            <div class='row item-margin-bottom pad border-radius grey-bg'>
                                <div class='col-md-6 pl-1 pr-1 item-margin-bottom'>
                                    <label class='input-label'>Description of task/activity</label>
                                    <input id='actInput_0' type='text' class='form-control full-width' />
                                </div>
                                <div class='col-md-3 pl-1 pr-1 mw-33 item-margin-bottom'>
                                    <label id='labelNumEquipLabor_0' class='input-label'># persons</label>
                                    <input type='number' id='numEquipOrLabor_0' class='form-control'/>
                                </div>
                                <div class='col-md-2 pl-1 pr-1 mw-33 item-margin-bottom'>
                                    <label class='input-label'>Hours</label>
                                    <input type='number' id='hours_0' class='form-control full-width' />
                                </div>
                                <div class='col-md-1 pl-1 pr-1 mw-33 item-margin-bottom'>
                                    <label class='input-label'>Add Task</label>
                                    <button type='button' id='addAct_0' class='btn btn-success block'>Add<i class='typcn typcn-chevron-right-outline'></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style='text-align: right'>
                        <button type='button' id='addLineBtn' class='btn btn-success'>Add Line</button>
                    </div>
                    <div class='row item-margin-bottom'>
                        <div class='col-md-6 offset-md-3'>
                            <label>Comment</label>
                            <textarea id='commentBox' name='comment' class='form-control' rows='5'></textarea>
                        </div>
                    </div>
                    <div class='center-content'>
                        <button type='submit' class='btn btn-primary btn-lg'>Submit</button>
                    </div>
                </form>
            </div>
        </main>
        <script>
            var locJSON = $locJSON;
        </script>;
        <script src='js/dailyReport.js'></script>";
    }
}

if (!empty($link) && is_a($link, 'mysqli')) $link->close();
include('fileend.php');
?>
