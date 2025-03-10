<?php

// Starting a new PHP session
session_start();

// Directory where apps are located
//$appsDir = __DIR__ . '/apps';
$appsDir = $homeDir . '/apps';

$apps = [];

// Reading the config file that contains confidential data
$_SESSION['config'] = $config;

// Database connection details
$servername = $_SESSION['config']['webcontroldb']['servername'];
$username = $_SESSION['config']['webcontroldb']['username'];
$password = $_SESSION['config']['webcontroldb']['password'];
$dbname = $_SESSION['config']['webcontroldb']['dbname'];

// Create connection
$wcconn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($rlvconn->connect_error) {
    die("Connection failed: " . $wcconn->connect_error);
}

// If not already authenticated
if (!isset($_SESSION['uuid']) || !isset($_SESSION['name'])) 
{ 

  // Get the 'id' parameter from GET request
  $id = isset($_GET['id']) ? trim($_GET['id']) : '';

  if (empty($id)) {
      die("Unauthorized: Missing ID parameter.");
  }

  // Prepare SQL statement to prevent SQL injection
  $stmt = $wcconn->prepare("SELECT UserID, UserName FROM WebControl.Link WHERE Link = ?");
  $stmt->bind_param("s", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  // Check if any row is returned
  if ($result->num_rows === 0) {
      die("Unauthorized: Invalid link ID.");
  }

  // Fetch user details
  $row = $result->fetch_assoc();
  $_SESSION['uuid'] = $row['UserID'];
  $_SESSION['name'] = $row['UserName'];

  // Close statement and connection
  $stmt->close();
  $wcconn->close();

}

// Checking for apps folder
if (is_dir($appsDir)) 
{
  
  // Getting all apps available in the folder
  foreach (scandir($appsDir) as $dir) 
  {
      
    // If the app has a 'web' folder
    if ($dir !== '.' && $dir !== '..' && is_dir("$appsDir/$dir/web")) 
    {

      // Adds it to the list
      $apps[$dir] = $dir;

    }
  
  }

}

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
    }
    .user-info {
        margin-left: auto;
        padding-right: 20px;
        font-size: 1.1em;
        font-weight: bold;
        color: white;
    }
  </style>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      let menu = document.querySelector(".menu");
      let dropdown = document.querySelector(".dropdown");
      let content = document.getElementById("content");
      let navTitle = document.getElementById("navTitle");
      
      // Toggle dropdown menu visibility
      menu.addEventListener("click", function (event) {
        event.stopPropagation();
        dropdown.classList.toggle("show");
      });
      
      // Hide dropdown when clicking outside
      document.addEventListener("click", function () {
        dropdown.classList.remove("show");
      });
      
      // Attach click event listeners to app links
      document.querySelectorAll(".dropdown a").forEach(link => {
        link.addEventListener("click", function (event) {
          event.preventDefault();
          let appName = this.textContent.trim();
          
          // Updating title in navigation bar
          navTitle.textContent = appName;
          
          // Chargement du contenu de l'app via AJAX
          fetch(this.getAttribute("href"))
            .then(response => response.text())
            .then(html => {
              content.innerHTML = html;
              
              // Extracting and executing scripts in included app
              let scripts = content.querySelectorAll("script");
              scripts.forEach(script => {
                let newScript = document.createElement("script");
                if (script.src) {
                  newScript.src = script.src;
                } else {
                  newScript.text = script.textContent;
                }
                document.head.appendChild(newScript);
              });
            })
            .catch(error => {
              content.innerHTML = "<p style='color: red;'>Error while loading app.</p>";
              console.error(error);
            });
        });
      });
    });
  </script>
</head>
<body>
  <div class="navbar">
    <div class="menu">
      <img src="webcontrol/menu-icon.webp" alt="Menu">
      <div class="dropdown">
        <?php foreach ($apps as $key => $appName): ?>
          <a href="apps/<?php echo $key; ?>/web/index.php">
            <img src="apps/<?php echo $key; ?>/web/icon.webp" alt="<?php echo $appName; ?>">
            <?php echo $appName; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div id="navTitle" class="title"><- Choose your app</div>
    <div class="user-info"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
  </div>
  <div id="content" class="content">
    <p>Select an app</p>
  </div>
</body>
</html>
