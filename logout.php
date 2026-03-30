<?php
// ══════════════════════════════════════════════════════════════
// logout.php — Safely destroys user session and logs them out
// Called when user clicks the Logout button in dashboard.php
// Clears session data, deletes session cookie, redirects home
// ══════════════════════════════════════════════════════════════
session_start();

// ── CLEAR ALL SESSION VARIABLES ──────────────────────────────
$_SESSION = array();

// ── DELETE THE SESSION COOKIE FROM THE BROWSER ───────────────
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// ── DESTROY THE SESSION ON THE SERVER ────────────────────────
session_destroy();

// ── REDIRECT TO HOME PAGE ─────────────────────────────────────
header("Location: index.html");
exit;
?>