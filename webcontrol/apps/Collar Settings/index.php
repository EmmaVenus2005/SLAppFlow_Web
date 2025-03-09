<?php

// PHP session (sould already exist from main file)
session_start();

// Database connection details
$servername = $_SESSION['config']['rlvrelaydb']['servername'];
$username = $_SESSION['config']['rlvrelaydb']['username'];
$password = $_SESSION['config']['rlvrelaydb']['password'];
$dbname = $_SESSION['config']['rlvrelaydb']['dbname'];

// Create connection
$rlvconn = new mysqli($servername, $username, $password, $dbname);

if ($rlvconn->connect_error) {
    echo "<p style='color:red;'>Error: Failed to connect to the database.</p>";
    exit();
}

// TO IMPLEMENT -> Get all the settings from view CommandDetails



// Define the settings in an associative array
$settings = array(
    "RLV Relay" => array(
        "Allow Restrictions" => array(),    // Empty because will be filled up using DB
        "Force Undress" => array(
            "Mode Selection" => array(
                "DressUp Override",
                "DressUp Priority",
                "Ignore DressUp"
            ),
            "Body Parts" => array(
                "Head",
                "Upper Body",
                "Lower Body",
                "Left Arm",
                "Right Arm",
                "Left Leg",
                "Right Leg",
                "Hands",
                "Feet",
                "Chest",
                "Back",
                "Pelvis",
                "Mouth",
                "Nose",
                "Ears",
                "Eyes"
                // Add other body parts as needed
            )
        )
    ),
    "Access Control" => array(
        "Groups" => array(
            "Owners" => array(
                "Add/Remove Owners",
                "Permissions"
            ),
            "Trusted" => array(
                "Add/Remove Trusted Users",
                "Permissions"
            )
        )
    ),
    "Appearance Settings" => array(
        "Change Color",
        "Change Texture",
        "Adjust Size",
        "Apply Styles"
    ),
    "Leash Settings" => array(
        "Attach/Detach Leash",
        "Set Leash Length",
        "Select Anchor Point"
    ),
    "Notifications" => array(
        "Login/Logout Alerts",
        "Movement Alerts",
        "Automatic Responses"
    ),
    "Security Settings" => array(
        "Enable/Disable Password",
        "Set Password",
        "Session Timeout"
    )
);

// Define documentation for each parameter
$documentation = array(
    
    // RLV Relay - Force Undress - Mode Selection
    "DressUp Override" => "When a force undress command is received for a specific body part, the collar instructs DressUp to remove items marked under that category. If DressUp doesn't have a category linked to that body part, the command is ignored. This provides a controlled and intelligent undressing process.",
    "DressUp Priority" => "If DressUp recognizes the body part to undress, it manages the action. Otherwise, the specified body part is stripped of all attachments. This ensures the undress command is executed, either via DressUp or by forcefully removing all items on the body part.",
    "Ignore DressUp" => "Processes force undress commands without involving DressUp. Allows manual selection of body parts to affect via checkboxes.",
    
    // RLV Relay - Force Undress - Body Parts
    "Head" => "Represents the head area of the avatar.",
    "Upper Body" => "Includes the torso and upper body regions.",
    "Lower Body" => "Covers the hips and legs.",
    "Left Arm" => "Refers to the avatar's left arm.",
    "Right Arm" => "Refers to the avatar's right arm.",
    "Left Leg" => "Refers to the avatar's left leg.",
    "Right Leg" => "Refers to the avatar's right leg.",
    "Hands" => "Includes both hands of the avatar.",
    "Feet" => "Includes both feet of the avatar.",
    "Chest" => "Represents the chest area.",
    "Back" => "Covers the back region.",
    "Pelvis" => "Refers to the pelvic area.",
    "Mouth" => "Includes items attached to the mouth.",
    "Nose" => "Includes items attached to the nose.",
    "Ears" => "Includes items attached to the ears.",
    "Eyes" => "Includes items attached to the eyes.",
    
    // Access Control - Groups - Owners
    "Add/Remove Owners" => "Allows adding or removing users with owner-level permissions.",
    "Permissions" => "Manage the permissions granted to owners.",
    
    // Access Control - Groups - Trusted
    "Add/Remove Trusted Users" => "Allows adding or removing users with trusted permissions.",
    "Permissions" => "Manage the permissions granted to trusted users.",
    
    // Appearance Settings
    "Change Color" => "Change the color of the collar to customize its appearance.",
    "Change Texture" => "Apply different textures to the collar for a unique look.",
    "Adjust Size" => "Resize the collar to fit the avatar perfectly.",
    "Apply Styles" => "Apply predefined styles or themes to the collar.",
    
    // Leash Settings
    "Attach/Detach Leash" => "Attach or detach a leash to the collar for roleplay purposes.",
    "Set Leash Length" => "Define the maximum length of the leash.",
    "Select Anchor Point" => "Choose the point where the leash is anchored.",
    
    // Notifications
    "Login/Logout Alerts" => "Receive alerts when the avatar logs in or out.",
    "Movement Alerts" => "Get notified when the avatar moves or changes location.",
    "Automatic Responses" => "Set up automatic replies to messages or events.",
    
    // Security Settings
    "Enable/Disable Password" => "Enable or disable password protection for accessing settings.",
    "Set Password" => "Set a password required to access or modify the collar's settings.",
    "Session Timeout" => "Define the duration before an inactive session is automatically logged out."
);

// Function to display settings recursively with folding, checkboxes, and documentation
function displaySettings($settings, $documentation, $level = 0) {
    echo "<ul>";
    foreach ($settings as $key => $value) {
        if ($key === "RLV Relay") {
            $rlvRelayID = uniqid('rlv_relay_');
            echo "<li>";
            echo '<span class="toggle-category" onclick="toggleCategory(\'' . $rlvRelayID . '\')">[+]</span> ';
            echo '<strong>' . htmlspecialchars($key) . '</strong>';
            echo '<div id="' . $rlvRelayID . '" style="display: none; margin-left: 20px;">';
            echo "<ul>";

            // "Allow Restrictions" category
            if (isset($value["Allow Restrictions"])) {
                $allowRestrictionsID = uniqid('restrictions_');
                echo "<li>";
                echo '<span class="toggle-category" onclick="toggleCategory(\'' . $allowRestrictionsID . '\')">[+]</span> ';
                echo '<strong>Allow Restrictions</strong>';
                echo '<div id="' . $allowRestrictionsID . '" style="display: none; margin-left: 20px;">';
                displayAllowRestrictions(); // Display subcategories from DB
                echo '</div>';
                echo "</li>";
            }

            // "Force Undress" category
            if (isset($value["Force Undress"])) {
                $forceUndressID = uniqid('force_undress_');
                echo "<li>";
                echo '<span class="toggle-category" onclick="toggleCategory(\'' . $forceUndressID . '\')">[+]</span> ';
                echo '<strong>Force Undress</strong>';
                echo '<div id="' . $forceUndressID . '" style="display: none; margin-left: 20px;">';
                displayForceUndress($value["Force Undress"], $documentation);
                echo '</div>';
                echo "</li>";
            }

            echo "</ul>";  // Close subcategories of "RLV Relay"
            echo '</div>';
            echo "</li>";
        } elseif (is_array($value)) {
            // Display other categories
            $id = uniqid('category_');
            echo "<li>";
            echo '<span class="toggle-category" onclick="toggleCategory(\'' . $id . '\')">[+]</span> ';
            echo '<strong>' . htmlspecialchars($key) . '</strong>';
            echo '<div id="' . $id . '" style="display: none; margin-left: 20px;">';
            displaySettings($value, $documentation, $level + 1);
            echo '</div>';
            echo "</li>";
        } else {
            // Display individual settings with checkboxes and documentation
            $docId = uniqid('doc_');
            $docText = isset($documentation[$value]) ? $documentation[$value] : 'Documentation not available.';
            echo '<li>';
            echo '<input type="checkbox" name="settings[]" value="' . htmlspecialchars($value) . '"> ';
            echo '<span class="info-icon" onclick="toggleDoc(\'' . $docId . '\')">(i)</span> ';
            echo htmlspecialchars($value);
            echo '<div id="' . $docId . '" class="doc-panel" style="display: none;">';
            echo '<p><strong>' . htmlspecialchars($value) . '</strong></p>';
            echo '<p>' . htmlspecialchars($docText) . '</p>';
            echo '</div>';
            echo '</li>';
        }
    }
    echo "</ul>";
}

function displayAllowRestrictions() {
    global $rlvconn;
    $result = $rlvconn->query("SELECT * FROM CommandDetails ORDER BY FunctionalCategory, Command");
    if ($result && $result->num_rows > 0) {
        $currentCategory = '';
        echo "<ul>";  // Start list for all categories inside "Allow Restrictions"
        while ($row = $result->fetch_assoc()) {
            if ($row['FunctionalCategory'] !== $currentCategory) {
                if ($currentCategory !== '') {
                    echo "</ul></div></li>";  // Close previous category
                }
                $currentCategory = $row['FunctionalCategory'];
                $categoryID = uniqid('category_');
                echo "<li>";
                echo '<span class="toggle-category" onclick="toggleCategory(\'' . $categoryID . '\')">[+]</span> ';
                echo '<strong>' . htmlspecialchars($currentCategory) . '</strong>';
                echo '<div id="' . $categoryID . '" style="display: none; margin-left: 20px;">';
                echo "<ul>";
            }
            $commandID = htmlspecialchars($row['CommandID']);
            echo '<li>';
            echo '<p><strong>' . htmlspecialchars($row['Command']) . '</strong> — ' . htmlspecialchars($row['Description']) . '</p>';
            echo '<label style="margin-right: 10px;"><input type="checkbox" name="settings[' . $commandID . '][Owner]" value="1" ' . ($row['DefAllowedForOwner'] ? 'checked' : '') . '> Owner</label>';
            echo '<label style="margin-right: 10px;"><input type="checkbox" name="settings[' . $commandID . '][Trusted]" value="1" ' . ($row['DefAllowedForTrusted'] ? 'checked' : '') . '> Trusted</label>';
            echo '<label><input type="checkbox" name="settings[' . $commandID . '][Group]" value="1" ' . ($row['DefAllowedForGroup'] ? 'checked' : '') . '> Group</label>';
            echo '</li>';
        }
        echo "</ul></div></li>";  // Close the last category and its container
    } else {
        echo "<p>No commands found for Allow Restrictions.</p>";
    }
}

// Function to display the Force Undress section with radio buttons and checkboxes
function displayForceUndress($forceUndressSettings, $documentation) {
    // Display Mode Selection (Radio Buttons)
    echo "<div>";
    echo "<p><strong>Mode Selection</strong></p>";
    $modes = $forceUndressSettings['Mode Selection'];
    foreach ($modes as $mode) {
        $docId = uniqid('doc_');
        $docText = isset($documentation[$mode]) ? $documentation[$mode] : 'Documentation not available.';
        // Set "DressUp Override" as the default selected option
        $checked = ($mode === 'DressUp Override') ? 'checked' : '';
        echo '<div>';
        echo '<input type="radio" name="force_undress_mode" value="' . htmlspecialchars($mode) . '" onclick="toggleBodyParts(this.value)" ' . $checked . '> ';
        echo '<span class="info-icon" onclick="toggleDoc(\'' . $docId . '\')">(i)</span> ';
        echo htmlspecialchars($mode);
        echo '<div id="' . $docId . '" class="doc-panel" style="display: none;">';
        echo '<p><strong>' . htmlspecialchars($mode) . '</strong></p>';
        echo '<p>' . htmlspecialchars($docText) . '</p>';
        echo '</div>';
        echo '</div>';
    }
    echo "</div>";
    // Display Body Parts (Checkboxes)
    echo "<div>";
    echo "<p><strong>Body Parts</strong></p>";
    $bodyParts = $forceUndressSettings['Body Parts'];
    foreach ($bodyParts as $part) {
        $docId = uniqid('doc_');
        $docText = isset($documentation[$part]) ? $documentation[$part] : 'Documentation not available.';
        echo '<div>';
        echo '<input type="checkbox" name="body_parts[]" value="' . htmlspecialchars($part) . '" class="body-part-checkbox" disabled> ';
        echo '<span class="info-icon" onclick="toggleDoc(\'' . $docId . '\')">(i)</span> ';
        echo htmlspecialchars($part);
        echo '<div id="' . $docId . '" class="doc-panel" style="display: none;">';
        echo '<p><strong>' . htmlspecialchars($part) . '</strong></p>';
        echo '<p>' . htmlspecialchars($docText) . '</p>';
        echo '</div>';
        echo '</div>';
    }
    echo "</div>";
}

?>
<style>
    ul { list-style-type: none; padding-left: 20px; }
    li { margin: 5px 0; position: relative; }
    .toggle-category {
        cursor: pointer;
        color: blue;
        margin-right: 5px;
    }
    .info-icon {
        cursor: pointer;
        color: green;
        margin-right: 5px;
    }
    .doc-panel {
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        padding: 10px;
        margin-top: 5px;
        margin-left: 20px;
    }
    .body-part-checkbox:disabled + .info-icon, .body-part-checkbox:disabled {
        color: gray;
    }
    input[type="submit"] {
        margin-top: 20px;
        padding: 10px 20px;
        font-size: 16px;
    }
</style>

<script>
    function toggleCategory(id) {
        var elem = document.getElementById(id);
        if (elem.style.display === 'none') {
            elem.style.display = 'block';
        } else {
            elem.style.display = 'none';
        }
    }
    function toggleDoc(id) {
        var elem = document.getElementById(id);
        if (elem.style.display === 'none') {
            elem.style.display = 'block';
        } else {
            elem.style.display = 'none';
        }
    }
    function toggleBodyParts(selectedMode) {
        var checkboxes = document.querySelectorAll('.body-part-checkbox');
        if (selectedMode === 'Ignore DressUp') {
            checkboxes.forEach(function(checkbox) {
                checkbox.disabled = false;
            });
        } else {
            checkboxes.forEach(function(checkbox) {
                checkbox.disabled = true;
                checkbox.checked = false;
            });
        }
    }
    // Initialize body parts checkboxes based on default selection
    window.onload = function() {
        toggleBodyParts('DressUp Override');
    };
</script>

<form method="post" action="process_settings.php">
    <?php
    // Display settings with folding, checkboxes, and documentation
    displaySettings($settings, $documentation);
    ?>
    <input type="submit" value="Save Settings">
</form>