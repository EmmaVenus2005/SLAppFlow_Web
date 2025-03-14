<?php

// This is called when opening the webpage. It will display the navigation bar, authenticate the user
// with its key (given in URL using id GET property).
// Once authenticated, sets 'uuid' and 'name' as PHP session variables, used to know if the user is 
// well authenticated.
// If an app is selected, will include 'webcontrol/wcapp.php', and give the appid as GET parameter.

// Starting a new PHP session
session_start();

// Directory where apps are located
$appsDir = $homeDir . '/apps';

// Creating array that will contain the available apps list
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
if ($wcconn->connect_error) {
    die("Connection failed: " . $wcconn->connect_error);
}

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

// Checking for apps folder
if (is_dir($appsDir)) {
    foreach (scandir($appsDir) as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir("$appsDir/$dir/web")) {
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
      height: calc(100vh - 60px);
      overflow-y: auto;
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
        let content = document.getElementById("app-frame");
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

        /**
         * Load an app dynamically into an iframe
         * @param {string} appId - ID of the app to load
         */
        function loadApp(appId) {
          const id = '<?php echo htmlspecialchars($id); ?>';
          const url = `/webcontrol/wcapp.php?id=${id}&app=${appId}`;
          
          // Load into iframe without changing URL
          const iframe = document.getElementById('app-frame');
          iframe.src = url;

          // Update navbar title
          document.getElementById('navTitle').textContent = appId;

          // Wait until the iframe is loaded
          iframe.onload = () => {
              // Copy all styles from parent to iframe
              const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
              const parentStyles = document.querySelectorAll('style, link[rel="stylesheet"]');

              parentStyles.forEach(style => {
                  const newStyle = style.cloneNode(true);
                  iframeDocument.head.appendChild(newStyle);
              });

              console.log(`App ${appId} loaded successfully with parent styles.`);
          };
      }

      // Attach event listeners to app links
      document.querySelectorAll(".dropdown a").forEach(link => {
          link.addEventListener("click", function (event) {
              event.preventDefault();
              let appId = this.getAttribute("data-app");
              loadApp(appId);
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
                <a href="#" data-app="<?php echo $key; ?>">
                    <img src="apps/<?php echo $key; ?>/web/icon.webp" alt="<?php echo $appName; ?>">
                    <?php echo $appName; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="navTitle" class="title">← Choose your app</div>
    <div class="user-info"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
</div>

<!-- Load the app into an iframe -->
<div class="content">
    <iframe id="app-frame" style="width: 100%; height: 100%; border: none;"></iframe>
</div>

</body>
</html>