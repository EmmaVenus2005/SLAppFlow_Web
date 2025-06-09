<style>
    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }
    .tab-button {
        padding: 10px;
        cursor: pointer;
        border: none;
        background: #ccc;
    }
    .tab-button:hover {
        background: #bbb;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
</style>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        document.getElementById(tabId).style.display = 'block';
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        switchTab('complete');
    });
</script>

<div id="dressup-main">
    <select>
        <option value="myself">Myself (<?php echo htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8'); ?>)</option>
    </select>
    
    <div class="tabs">
        <button class="tab-button" onclick="switchTab('complete')">Complete Outfits</button>
        <button class="tab-button" onclick="switchTab('individual')">Individual Clothing</button>
    </div>
    
    <div id="individual" class="tab-content">
        <p>Individual clothing items will be listed here.</p>
    </div>
    
    <div id="complete" class="tab-content active">
        <div class="outfit-list">
            <?php 
            $outfits = NVGetLists('Outfit');
            if (!empty($outfits)) {
                foreach ($outfits as $outfit) {
                    echo '<a href="apps/DressUp Control/switch_outfit.php?outfit=' . $outfit . '">' . htmlspecialchars($outfit, ENT_QUOTES, 'UTF-8') . '</a><br>';
                }
            } else {
                echo '<p>No outfits available.</p>';
            }
            ?>
        </div>
    </div>
</div>