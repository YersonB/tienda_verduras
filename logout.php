<?php
// logout.php
require_once 'config/entorno.php';
session_start();
$_SESSION = array();
session_destroy();
header("Location: login.php");
exit;