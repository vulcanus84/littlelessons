<?php
  include_once("php/class_db.php");
  try
  {
    //$db = new db("localhost","id11142996_huebsche_bm","badminton123$","id11142996_tournament");
    $db = new db("localhost","littlelessons","littlelessons123$","littlelessons");
  }
  catch (Exception $e)
  {
    die("<div style='border:1px dotted black;color:red;text-align:center;padding:5px;margin:5px;font-weight:bold;'>
              ".$e->getMessage()."
          </div>");
  }
?>