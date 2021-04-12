<!doctype html>

<html lang="en">
	<head>
		<meta charset="utf-8">

		<title>CYOA</title>
		<meta name="description" content="CYOA">

	</head>

	<body>
<?php
$confirm = NULL;
$email = NULL;
$password = NULL;
$username = NULL;

if(isset($_GET["confirm"]) && !empty($_GET["confirm"])) {
  $confirm = $_GET["confirm"];
}
if(isset($_POST["email"]) && !empty($_POST["email"])) {
  $email = $_POST["email"];
}
if(isset($_POST["password"]) && !empty($_POST["password"])) {
  $password = $_POST["password"];
}
if(isset($_POST["username"]) && !empty($_POST["username"])) {
  $username = $_POST["username"];
}

// Registration confirmation check + apply
if(!isnull($confirm)) {
  
}

// Regsitration start

 ?>
	</body>
</html>
