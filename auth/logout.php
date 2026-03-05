<?php
// 1. Initialize the session.
// We need to start the session in order to access and destroy it.
session_start();

// 2. Unset all of the session variables.
// This clears the data currently stored in the $_SESSION array.
$_SESSION = array();

// 3. Destroy the session cookie.
// This ensures that the user's browser completely forgets the session ID.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session file on the server.
session_destroy();

// 5. Redirect the user back to the main public page.
header("Location: ../index.php");
exit;
?>