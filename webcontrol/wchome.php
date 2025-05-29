<?php

  // If $uuid and $name are not set, someone tries to access to the page without using main menu
  // In this case ALWAYS abort the script for obvious security reason
  if (!isset($_SESSION['uuid']) || !isset($_SESSION['name'])) { exit(); }

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Navbar Apps</title>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; }
    .navbar { 
      background: #333; 
      color: white; 
      padding: 10px; 
      display: flex; 
      align-items: center; 
      position: fixed; 
      width: 100%; 
      top: 0; 
      left: 0; 
      z-index: 1000; 
    }
    .menu { position: relative; cursor: pointer; }
    .menu img { width: 32px; height: 32px; }
    .dropdown { 
      display: none; 
      position: absolute; 
      background: white; 
      color: black; 
      top: 40px; 
      left: 0; 
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); 
      border-radius: 5px; 
    }
    .dropdown a { 
      display: flex; 
      align-items: center; 
      padding: 10px; 
      text-decoration: none; 
      color: black; 
    }
    .dropdown a img { 
      width: 24px; 
      height: 24px; 
      margin-right: 10px; 
    }
    .dropdown a:hover { background: #ddd; }
    .menu .dropdown.show { display: block; }
    .title { 
      margin-left: 20px; 
      font-size: 1.2em; 
    }
    .content { 
      margin-top: 50px; 
      padding: 20px; 
      height: calc(100vh - 60px);
      overflow-y: auto;
    }
    .user-menu {
    margin-left: auto;
    padding-right: 20px;
    position: relative;
    cursor: pointer;
    color: white;
    font-size: 1.1em;
    font-weight: bold;
    user-select: none;
    }
    .user-name {
        display: inline-block;
    }
    .user-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 30px;
        background: white;
        color: black;
        min-width: 120px;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        z-index: 2000;
    }
    .user-dropdown a {
        display: block;
        padding: 10px 18px;
        text-decoration: none;
        color: black;
        border-radius: 5px;
    }
    .user-dropdown a:hover {
        background: #ddd;
    }
    .user-menu.open .user-dropdown {
        display: block;
    }
  </style>
  <script>
  // Wait for the DOM to be fully loaded
  document.addEventListener("DOMContentLoaded", function () {
      // --- Navbar app menu dropdown logic ---
      const menu = document.querySelector(".menu");
      const dropdown = document.querySelector(".dropdown");
      const navTitle = document.getElementById("navTitle");
      const appFrame = document.getElementById("app-frame");

      if (menu && dropdown) {
          // Show/hide the main menu dropdown when menu is clicked
          menu.addEventListener("click", function (event) {
              event.stopPropagation();
              dropdown.classList.toggle("show");
          });

          // Hide the dropdown when clicking outside
          document.addEventListener("click", function () {
              dropdown.classList.remove("show");
          });
      }

      /**
       * Dynamically load an app into the iframe
       * @param {string} appId - The ID of the app to load
       */
      function loadApp(appId) {
          const id = '<?php echo htmlspecialchars($id); ?>';
          const url = `/webcontrol/wcapp.php?id=${id}&app=${appId}`;
          if (appFrame) {
              appFrame.src = url;

              // Update navbar title with the app name/ID
              navTitle.textContent = appId;

              // When iframe loads, inject parent styles for consistent look
              appFrame.onload = () => {
                  try {
                      const iframeDocument = appFrame.contentDocument || appFrame.contentWindow.document;
                      const parentStyles = document.querySelectorAll('style, link[rel="stylesheet"]');
                      parentStyles.forEach(style => {
                          iframeDocument.head.appendChild(style.cloneNode(true));
                      });
                  } catch (e) {
                      // Cross-origin restrictions might block this for some setups
                      console.warn("Could not inject styles into iframe:", e);
                  }
                  console.log(`App ${appId} loaded successfully.`);
              };
          }
      }

      // Listen to app link clicks in the dropdown to load the app dynamically
      if (dropdown) {
          dropdown.querySelectorAll("a[data-app]").forEach(link => {
              link.addEventListener("click", function (event) {
                  event.preventDefault();
                  const appId = this.getAttribute("data-app");
                  if (appId) loadApp(appId);
              });
          });
      }

      // --- User menu (right, logout dropdown) logic ---
      const userMenu = document.querySelector('.user-menu');
      const userDropdown = document.querySelector('.user-dropdown');
      const logoutLink = document.getElementById('logout-link');

      if (userMenu && userDropdown) {
          // Toggle user dropdown when clicking on username
          userMenu.addEventListener('click', function (e) {
              e.stopPropagation();
              userMenu.classList.toggle('open');
          });
          // Hide user dropdown when clicking outside
          document.addEventListener("click", function () {
              userMenu.classList.remove('open');
          });
      }

      // --- AJAX logout handler ---
      if (logoutLink) {
          logoutLink.addEventListener('click', function (e) {
              e.preventDefault();
              fetch('webcontrol/wcdisconnect.php', {
                  method: 'POST',
                  credentials: 'same-origin'
              })
              .then(response => response.json())
              .then(data => {
                  if (data.status === 'ok') {
                      // Redirect to site root after successful logout
                      window.location.href = "/";
                  } else {
                      alert('Logout failed: ' + (data.message || 'Unknown error'));
                  }
              })
              .catch(() => {
                  alert('Logout failed');
              });
          });
      }
  });
  </script>

</head>
<body>

<div class="navbar">
    <div class="menu">
        <img src="webcontrol/menu-icon.webp" alt="Menu">
        <div class="dropdown">
            <?php foreach ($apps as $key => $appName): ?>
                <a href="#" data-app="<?php echo $key; ?>">
                    <img src="apps/<?php echo $key; ?>/web/icon.webp" alt="<?php echo $appName; ?>">
                    <?php echo $appName; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="navTitle" class="title">← Choose your app</div>
    <div class="user-menu">
    <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?> &#x25BC;</span>
    <div class="user-dropdown">
        <a href="#" id="logout-link">Log out</a>
    </div>
</div>

</div>

<!-- Load the app into an iframe -->
<div class="content">
    <iframe id="app-frame" style="width: 100%; height: 100%; border: none;"></iframe>
</div>

</body>
</html>