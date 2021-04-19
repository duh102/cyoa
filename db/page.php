<?php

require_once "util.php"

function get_page($conn, $pageid) {
  $pageTable = getTableName("page");
  $stmt = $conn->prepare("SELECT id, story, creator, filler, title, body, claimed, claim_time, created, created_time
    FROM {$pageTable} WHERE id = :pageid");
  $success = $stmt->execute(array(""));
  if(is_null($success) or !$success) {
    $stmt->closeCursor();
    return false;
  }
  $pageData = $stmt->fetch(PDO::FETCH_OBJ);
  $stmt->closeCursor();
  return $pageData;
}

function get_links($conn, $pageid) {
  $linkTable = getTableName("page_link");
  $pageTable = getTableName("page");

  $stmt = $conn->prepare("SELECT page.id, page.story, page.creator, page.filler, page.title,
      page.body, page.claimed, page.claim_time, page.created, page.created_time, page_link.child, page_link.index
    FROM {$pageTable} AS page, {$linkTable} AS page_link
    WHERE page.id = :pageid AND page.id = page_link.parent ORDER BY page_link.index DESC");
  $success = $stmt->execute(array("pageid"=>$pageid));
  if(is_null($success) or !$success) {
    $stmt->closeCursor();
    return false;
  }
  $pageData = $stmt->fetchAll(PDO::FETCH_OBJ);
  $stmt->closeCursor();
  return $pageData;
}

function reserve_link($conn, $pageid, $userid) {
}

function create_link($conn, $parentid, $title) {
}

?>
