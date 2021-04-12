<?php
require "config.php";

function get_conn() {
  try {
    $conn = new PDO($dbconnstr, $dbuser, $dbpass);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
  } catch(PDOException $e) {
    throw $e;
  }
}

function create_db($conn) {
  $conn->beginTransaction();
  try {
    $conn->exec("
  CREATE TABLE IF NOT EXISTS {$prefix}user (
    id BIGINT AUTOINCREMENT,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    deactivated BOOL DEFAULT false,
    deleted BOOL DEFAULT false,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,

    CONSTRAINT PK_USER PRIMARY KEY (id),
    CONSTRAINT UNIQ_USER UNIQUE (username)
  );

  CREATE TABLE IF NOT EXISTS {$prefix}registration (
    id BIGINT AUTOINCREMENT,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    submitted TIMESTAMP NOT NULL,

    CONSTRAINT PK_REGISTRATION PRIMARY KEY (id),
    CONSTRAINT UNIQ_REGISTRATION_EMAIL UNIQUE (email),
    CONSTRAINT UNIQ_REGISTRATION_USER UNIQUE (username)
  );

  CREATE TABLE IF NOT EXISTS {$prefix}story (
    id BIGINT AUTOINCREMENT,
    creator BIGINT NOT NULL,
    title VARCHAR(128) NOT NULL,
    description TEXT NOT NULL,
    deactivated BOOL DEFAULT false,
    deleted BOOL DEFAULT false,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT PK_STORY PRIMARY KEY (id),
    CONSTRAINT FK_STORY_USER FOREIGN KEY (creator) REFERENCES user(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT UNIQ_STORY UNIQUE (creator, title)
  );

  CREATE TABLE IF NOT EXISTS {$prefix}page (
    id BIGINT AUTOINCREMENT,
    story BIGINT NOT NULL,
    creator BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT PK_PAGE PRIMARY KEY (id),
    CONSTRAINT FK_PAGE_STORY FOREIGN KEY (story) REFERENCES story(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_PAGE_USER FOREIGN KEY (creator) REFERENCES user(id) ON UPDATE CASCADE ON DELETE RESTRICT
  );

  CREATE TABLE IF NOT EXISTS {$prefix}page_link (
    parent BIGINT NOT NULL,
    child BIGINT NOT NULL,
    index INT NOT NULL,

    CONSTRAINT PK_PAGE_LINK PRIMARY KEY (parent, child),
    CONSTRAINT FK_PAGE_LINK_PARENT FOREIGN KEY (parent) REFERENCES page(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_PAGE_LINK_CHILD FOREIGN KEY (child) REFERENCES page(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT UNIQ_PAGE_LINK_INDEX UNIQUE (parent, index)
  );

  CREATE TABLE IF NOT EXISTS {$prefix}story_access_control (
    story BIGINT NOT NULL,
    user BIGINT NOT NULL,
    access_type INT NOT NULL,

    CONSTRAINT PK_STORY_ACS (story, user),
    CONSTRAINT FK_STORY_ACS_STORY FOREIGN KEY (story) REFERENCES story(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT FK_STORY_ACS_USER FOREIGN KEY (user) REFERENCES user(id) ON UPDATE CASCADE ON DELETE CASCADE
  );
");
    $conn->commit();
  } catch(PDOException $e) {
    $conn->rollback();
  }
}


?>
