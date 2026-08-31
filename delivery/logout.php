```php
<?php

session_start();


// ======================================
// DESTROY DELIVERY SESSION
// ======================================

$_SESSION = [];


// ======================================
// DESTROY SESSION COOKIE
// ======================================

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


// ======================================
// DESTROY SESSION
// ======================================

session_destroy();


// ======================================
// REDIRECT TO DELIVERY LOGIN
// ======================================

header("Location: login.php");
exit;

?>
```
