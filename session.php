<?php  
session_start();

if (empty($_SESSION['userID'])) {
    /* Redirect If Not Logged In */
    header("Location: login.php");
    exit; /* prevent other code from being executed*/
} else {
  // check for session timeout
  if ($_SESSION['timeout'] + 15 * 60 < time()) {
    /* session timed out */
    header("Location: logout.php");
  } else {
    /*if the user isn't timed out, update the session timeout variable to the current time.*/
     $_SESSION['timeout'] = time();
  }
}
