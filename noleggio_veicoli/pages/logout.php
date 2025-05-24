<?php
session_start();

// Rimuove tutte le variabili di sessione
$_SESSION = [];

// Distrugge la sessione
session_destroy();

// Reindirizza alla homepage
header("Location: ../index.php");
exit;
?>
