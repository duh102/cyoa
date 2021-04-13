<?php
require_once "util.php"

function createRegistration($conn, $username, $email, $password) {
  $conn->beginTransaction();
  try {
    $hashedpass = password_hash($password, PASSWORD_DEFAULT);
    $signedup = false;
    $tablename = getTableName("registration");
    $stmt = $conn->prepare("DELETE FROM {$tablename} WHERE LOWER(username) = LOWER(:username);");
    $stmt->execute(array('username'=>$username));
    $stmt->closeCursor();
    // There's probably an optimization we can put here to make sure we don't insert a new registration request for a
    // user that already exists; maybe we shouldn't have a registration table at all and just store the code in the user
    // table
    $stmt = $conn->prepare("INSERT INTO {$tablename} (username, email, password, code, submitted) VALUES (:username, :email, :password, :code, CURRENT_TIMESTAMP)");
    $signupCode = genRegistrationString();
    $stmt->execute(array('username'=>$username));
    $stmt->closeCursor();
    $conn->commit();
    return $signupCode;
  } catch( PDOException $pdoe) {
    $conn->rollback();
  }
  return false;
}

function confirmRegistration($conn, $username, $code) {
  $conn->beginTransaction();
  try {
    $regTable = getTableName("registration");
    $userTable = getTableName("user");
    //TODO: actually fill this in
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

// Prerequisite, must have sessions set up
function login($conn, $username, $password) {
  $conn->beginTransaction();
  try {
    $tablename = getTableName("user");
    $stmt = $conn.prepare("SELECT id, username, email, password, deactivated, deleted, created FROM {$tablename} WHERE LOWER(username) = LOWER(:username)");
    $success = $stmt->execute(array('username' => $username));
    // If we don't find the user, can't log them in obvs
    if(is_null($success) or !$success) {
      return false;
    }

    // If the user is deleted or their password is no good, also can't log them in
    $userData = $stmt->fetch(PDO::FETCH_OBJ);
    $stmt->closeCursor();
    $passGood = password_verify($password, $userData->password);
    if($userData->deleted or !$passGood) {
      return false;
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
    if(is_null($success) or !$success or $stmt->rowCount() < 1) {
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

?>
