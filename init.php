<?php
// init.php
session_start();
require_once __DIR__ . '/db.php';

function is_logged() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged()) {
        header("Location: login.php");
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit;
}