<?php

function FetchFolders($p_obj_id) 
{

    // 1. Get the current dataset value from the server
    $current = NVGetValue("DirFetchCurrentDataset");
    $dataset = ($current == "0") ? 1 : 0;

    // 2. Fetch the list of main folders (~wearings)
    $cats_str = SLRLVRequest($p_obj_id, ["@getinv:~wearings/=#"]);
    if ($cats_str[0] === "" || $cats_str[0] === false) {
        // Handle error: No folders found
        return false;
    }
    $categories = explode(',', $cats_str[0]);

    // 3. Delete old lists for this dataset
    NVDelLists("ClothingPieces{$dataset}");

    // 4. For each main folder, fetch and update its contents
    foreach ($categories as $category) 
    {
        
        // Clean up category name (just in case)
        $category = trim($category);
        if ($category === "") continue;

        // Fetch folder content (worn items)
        $items_str = SLRLVRequest($p_obj_id, ["@getinvworn:~wearings/{$category}=#"]);
        // if ($items_str[0] === "" || $items_str[0] === false) {
        //     // Handle error: No items found for this category
        //     continue;
        // }
        $items = explode(',', $items_str[0]);

        // Remove the first element (usually "|01" - status marker)
        if (count($items) > 0) {
            array_shift($items);
        }

        // Sort items alphabetically
        sort($items, SORT_STRING);

        // Send the list to the server
        NVSetList("ClothingPieces{$dataset}", $category, implode(',', $items));

    }

    // 5. Mark the dataset as updated
    NVSetValue("DirFetchCurrentDataset", $dataset);

    // Optional: return success
    return true;

}
