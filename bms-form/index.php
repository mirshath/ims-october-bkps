<?php
// Reached when someone requests the bare folder — e.g. ".../google-form/"
// or ".../google-form" with no specific file. There's nothing useful to
// show here, so send them to the college site instead of a directory
// listing or blank page.
include("../external_redirect.php");
header("Location: " . EXTERNAL_REDIRECT_URL);
exit();