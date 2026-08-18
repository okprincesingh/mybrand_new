<?php
// Entry point for /admin and /admin/. Authentication and post-login routing
// continue to be handled by the existing login page.
header('Location: login.php', true, 302);
exit;
