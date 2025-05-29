<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging Out...</title>
    <script>
        // Show popup and redirect after a short delay
        window.onload = function() {
            alert("Bye, see you again!");
            window.location.href = "login.html";
        };
    </script>
</head>
<body>
</body>
</html>
