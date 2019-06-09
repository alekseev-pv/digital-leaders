<?php
// what we do?
// use POST, get it, JSON decode AND write it to file

$t = @file_get_contents('php://input');

@file_put_contents("raw.txt", $t);

//@file_put_contents("raw".date('i-s').".txt", $t);

//$j = json_decode($t, true);
//@file_put_contents("out.txt", $j);

?>