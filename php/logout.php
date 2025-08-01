<?php
require_once 'session_control.php';

session_unset();
session_destroy();

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>