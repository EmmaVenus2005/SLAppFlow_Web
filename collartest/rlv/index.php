<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RLV Command Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[readonly] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-group input[readonly],
        .form-group textarea[readonly] {
            background-color: #f9f9f9;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn-submit:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>RLV Command Validator <span style="font-size: 0.8em;">by EmmaVenus2005</span></h1>
        <form method="POST" action="">
            <!-- Command input -->
            <div class="form-group">
                <label for="rlv-command">RLV Command</label>
                <input type="text" id="rlv-command" name="rlv-command" placeholder="Enter RLV Command" required>
            </div>

            <!-- Read-only output fields -->
            <div class="form-group">
                <label for="matching-filter">Matching Filter</label>
                <input type="text" id="matching-filter" name="matching-filter" readonly>
            </div>

            <div class="form-group">
                <label for="category-short-name">Category Short Name</label>
                <input type="text" id="category-short-name" name="category-short-name" readonly>
            </div>

            <div class="form-group">
                <label for="functional-category">Functional Category</label>
                <input type="text" id="functional-category" name="functional-category" readonly>
            </div>

            <div class="form-group">
                <label for="command-type">Command Type</label>
                <input type="text" id="command-type" name="command-type" readonly>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" readonly></textarea>
            </div>

            <!-- Submit button -->
            <button type="submit" class="btn-submit">Check Command</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Fetch the input command from the form
            $rlvCommand = trim($_POST['rlv-command']);

            // Reading the config file that contains confidential data two levels up
            $configFilePath = realpath(__DIR__ . '/../../../config/config.ini');
            if ($configFilePath && file_exists($configFilePath)) {
                $config = parse_ini_file($configFilePath, true);

                // Database connection details
                $servername = $config['rlvrelaydb']['servername'];
                $username = $config['rlvrelaydb']['username'];
                $password = $config['rlvrelaydb']['password'];
                $dbname = $config['rlvrelaydb']['dbname'];

                // Create connection
                $rlvconn = new mysqli($servername, $username, $password, $dbname);

                if ($rlvconn->connect_error) {
                    echo "<p style='color:red;'>Error: Failed to connect to the database.</p>";
                    exit();
                }

                $stmt = $rlvconn->prepare("CALL find_matching(?)");

                if (!$stmt) {
                    echo "<p style='color:red;'>Error: Failed to prepare statement.</p>";
                    exit();
                }

                $stmt->bind_param("s", $rlvCommand);

                if (!$stmt->execute()) {
                    echo "<p style='color:red;'>Error: Execution failed.</p>";
                    $stmt->close();
                    $rlvconn->close();
                    exit();
                }

                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    echo "<script>
                        document.getElementById('matching-filter').value = " . json_encode($row['Filter']) . ";
                        document.getElementById('category-short-name').value = " . json_encode($row['CategoryShortName']) . ";
                        document.getElementById('functional-category').value = " . json_encode($row['FunctionalCategory']) . ";
                        document.getElementById('command-type').value = " . json_encode($row['CommandType']) . ";
                        document.getElementById('description').value = " . json_encode($row['Description']) . ";
                    </script>";
                } else {
                    echo "<p style='color:orange;'>No matching command found or multiple results returned.</p>";
                }

                $stmt->close();
                $rlvconn->close();
            } else {
                echo "<p style='color:red;'>Error: Configuration file not found.</p>";
            }
        }
        ?>
    </div>
</body>

</html>
