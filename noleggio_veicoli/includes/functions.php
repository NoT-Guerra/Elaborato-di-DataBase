<?php
function check_logged_in() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /pages/login.php');
        exit();
    }
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>
