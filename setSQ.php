    <?php 
            include('SQLFunctions.php');
            include('session.php');
            $link = f_sqlConnect();
    ?>
<HTML>
    <HEAD>
        <TITLE>SVBX - Set Security Question</TITLE>
        <link rel="stylesheet" href="styles.css" type="text/css"/>
    </HEAD>
<BODY>
<div="background">
<div class="index">
  <img src="assets/img/vta_logo.png" alt="VTA - Solutions that move you" class="logo" />
    <h1 class='index-text'>Silicon Valley Berryessa Extension Project</h1>
    <h2>You are required to set your security question for password recovery before you continue</h2>
  <form action="SecQSubmit.php" method="post">
  <fieldset class="login">
    <p style="color:black">
      <label>Choose a Question</label>
      <?php
        $sqlY = "SELECT SecQID, secQ FROM secQ ORDER BY SecQID";
          echo "<select name='SecQ' value='' id='defdd' required></option>";
          echo "<option value=''></option>";
            foreach(mysqli_query($link,$sqlY) as $row) {
              echo "<option value=$row[SecQID]>$row[secQ]</option>";
          }
          echo "</select>";
      ?>
    </p>
    <p style="color:black">
      <label>Your Answer</label>
      <input type="text" name="SecA" value="" maxlength="20" />
    </p>
    <p>
      <input type="submit" name="submit" value="Submit" />
    </p>
  </fieldset>
  </form>
  </div>
  </div>
</body>
</html>