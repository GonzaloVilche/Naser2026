<<<<<<< HEAD
<?php session_start();session_destroy();header('Location: login.php');exit;
=======
<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
>>>>>>> 012a951df26ec92c7c55cb72830605cd86664721
