<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simple PHP Webpage</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        main {
            padding: 20px;
        }
    </style>
</head>
<body>

    <?php
    // Header section
    echo "<header>";
    echo "<h1>Welcome to My PHP Webpage</h1>";
    echo "<p>This is a simple page with a header made using PHP.</p>";
    echo "</header>";
    ?>

    <main>
        <p>This content is part of the main section of the webpage.</p>
    </main>

</body>
</html>
