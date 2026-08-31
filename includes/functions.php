<?php

function clean($value)
{
    return htmlspecialchars(
        trim($value),
        ENT_QUOTES,
        'UTF-8'
    );
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) &&
           $_SESSION['role'] === 'admin';
}

function redirect($url)
{
    header("Location: " . $url);
    exit;
}
