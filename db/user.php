<?php
require_once "util.php"

function create_registration($conn, $username, $email, $password) {
  $conn->beginTransaction();
  try {
    $hashedpass = password_hash($password, PASSWORD_DEFAULT);
    $signupCode = genRegistrationString();

    $tablename = getTableName("user");
    $stmt = $conn->prepare("INSERT INTO {$tablename}
      (username,   email,  password,  confirmcode, reg_submit_time,   confirmed, deactivated) VALUES
      (:username, :email, :password, :code,        CURRENT_TIMESTAMP, false,     false)");
    $success = $stmt->execute(array('username'=>$username, 'email'=>$email, 'password'=>$hashedpass, 'code'=>$signupCode));
    $inserted = $stmt->rowCount()
    if(is_null($success) or !$success or $inserted != 1) {
      $stmt->closeCursor();
      $conn->rollback();
      return false;
    }
    $stmt->closeCursor();
    $conn->commit();
    return $signupCode;
  } catch( PDOException $pdoe) {
    $conn->rollback();
  }
  return false;
}

function confirm_registration($conn, $username, $code) {
  $conn->beginTransaction();
  try {
    $userTable = getTableName("user");

    $stmt = $conn->prepare("SELECT username, confirmcode, reg_submit_time FROM {$userTable} WHERE LOWER(username) = LOWER(:username)");
    $success = $stmt->execute(array('username'=>$username));
    if(is_null($success) or !$success) {
      $stmt->closeCursor();
      return false;
    }
    $userData = $stmt->fetch(PDO::FETCH_OBJ);
    $stmt->closeCursor();
    $dbTime = strtotime($userData->reg_submit_time);
    // Adjust the DateInterval for how long a code should expire after; here it's set to 2 hours.
    $codegood = $userData->confirmcode == $code and ($dbTime->add(new DateInterval('P2H'))) > time();
    if(!$codegood) {
      return false;
    }
    // Code's good, let's activate this sucker
    // Let's add in the confirm code just in case; it's unnecessary but it's extra insurance in case the above function
    // is ever modified incorrectly
    $stmt = $conn->prepare("UPDATE {$userTable} SET confirmed = true WHERE LOWER(username) = LOWER(:username) AND confirmcode = :code");
    $success = $stmt->execute(array('username'=>$username, 'code'=>$code));
    $updated = $stmt->rowCount()
    if(is_null($success) or !$success or $updated != 1) {
      $stmt->closeCursor();
      $conn->rollback();
      return false;
    }
    $stmt->closeCursor();
    $conn->commit();
    return true;
  } catch( PDOException $pdoe) {
    $conn->rollback();
  }
  return false;
}

function logout() {
  unset($_SESSION);
  if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"],$params["httponly"]);
  }

  session_destroy();
}

function genRegistrationString() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
  
    for ($i = 0; $i < 128; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }
  
    return $randomString;
}

function login($conn, $username, $password) {
  $conn->beginTransaction();
  try {
    $tablename = getTableName("user");
    $userData = get_user_data($conn, null, $username);

    // If the user is not yet confirmed, deleted, or their password is no good, also can't log them in
    $passGood = password_verify($password, $userData->password);
    if(!$userData->confirmed or $userData->deleted or !$passGood) {
      return false;
    }
    if(session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    // The username is good, their password checks out, and they're not deleted, set up the session variables
    $_SESSION["id"] = $userData->id;
    $_SESSION["username"] = $userData->username;
    $_SESSION["email"] = $userData->email;
    $_SESSION["deactivated"] = $userData->deactivated;
    $_SESSION["created"] = $userData->created;

    // And update their last-login to now
    $stmt = $conn->prepare("UPDATE {$tablename} SET last_login = CURRENT_TIMESTAMP WHERE LOWER(username) = LOWER(:username)");
    $success = $stmt->execute(array('username'=>$username));
    if(is_null($success) or !$success or $stmt->rowCount() != 1) {
      throw new Exception("Problem while updating last-login date for {$username}");
    }
    $stmt->closeCursor();

    $conn->commit();
    return true;
  } catch( Exception $pdoe ) {
    logout();
    $conn->rollback();
  }
  return false;
}

function get_user_data($conn, $userid, $username) {
  $stmt = null;
  $success = null;
  $tablename = getTableName("user");
  if(!is_null($userid)) {
    $stmt = $conn->prepare("SELECT id, username, email, password, confirmed, deactivated, deleted, created, last_login FROM {$tablename} WHERE id = :id");
    $success = $stmt->execute(array('id'=>$userid));
  } else if(!is_null($username)) {
    $stmt = $conn->prepare("SELECT id, username, email, password, confirmed, deactivated, deleted, created, last_login FROM {$tablename} WHERE LOWER(username) = LOWER(:username)");
    $success = $stmt->execute(array('username'=>$username));
  } else {
    return null;
  }
  if(is_null($success) or !$success or $stmt->rowCount() != 1) {
    $stmt->closeCursor();
    return null;
  }

  $userData = $stmt->fetch(PDO::FETCH_OBJ);
  $stmt->closeCursor();
  return $userData
}

?>
