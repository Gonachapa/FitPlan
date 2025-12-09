<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="button.css">
    <title>Sidebar Toggle</title>
</head>
<body>
    <button id="toggleSidebar" aria-label="Toggle Sidebar">></button>
    <iframe id="sidebarIframe" src="sidebar.php"></iframe>

    <script>
        const toggleButton = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframe');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            toggleButton.textContent = sidebar.classList.contains('show') ? '<' : '>';
            toggleButton.setAttribute('aria-expanded', sidebar.classList.contains('show'));
        });
    </script>
</body>
</html>
