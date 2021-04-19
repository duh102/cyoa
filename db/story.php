<?php
require_once "util.php"

function get_allowed_stories($conn, $readerid, $number, $page) {
  $storyTable = getTableName("story");
  $storyACLTable = getTableName("story_access_control");
  $idx = $page * $number;
  $stmt = $conn->prepare("SELECT story.id, story.creator, story.title, story.description, story.deactivated, story.deleted, story.created, storyacl.access_allow
    FROM {$storyTable} AS story LEFT OUTER JOIN {$storyACLTable} AS storyacl
    ON story.id = storyacl.story AND storyacl.user = :user LIMIT :idx,:number");
  $success = $stmt->execute(array("user"=>$readerid, "idx"=>$idx, "number"=>$number));
  if(is_null($success) or !$success) {
    $stmt->closeCursor();
    return false;
  }
  $storyData = $stmt->fetchAll(PDO::FETCH_OBJ);
  $stmt->closeCursor();
  return $storyData;
}

function get_story_details($conn, $storyid, $readerid) {
  $storyTable = getTableName("story");
  $storyACLTable = getTableName("story_access_control");
  $stmt = $conn->prepare("SELECT story.id, story.creator, story.title, story.description, story.deactivated, story.deleted, story.created, storyacl.access_allow
    FROM {$storyTable} as story LEFT OUTER JOIN {$storyACLTable} AS storyacl
    ON story.id = storyacl.story AND storyacl.user = :user");
  $success = $stmt->execute(array("user"=>$readerid));
  if(is_null($success) or !$success) {
    $stmt->closeCursor();
    return false;
  }
  $storyData = $stmt->fetch(PDO::FETCH_OBJ);
  $stmt->closeCursor();
  return $storyData;
}

?>
