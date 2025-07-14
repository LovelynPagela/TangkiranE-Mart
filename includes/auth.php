<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer';
}

function current_user() {
    return $_SESSION['user'] ?? null;
}
function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}