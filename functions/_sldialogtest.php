<?php

function SLDialogTest($recipient, $prompt, $options, $needsPaging = false, $needsBack = false) {
    global $conn, $appid, $uuid, $name, $session;

    // 1. Parameter validation
    if (empty($recipient) || !preg_match('/^[a-f0-9\-]{36}$/', $recipient)) {
        error_log("SLDialogTest: Invalid recipient UUID.");
        return null;
    }

    if (empty($prompt) || !is_string($prompt)) {
        error_log("SLDialogTest: Invalid prompt.");
        return null;
    }

    if (empty($options) || !is_array($options)) {
        error_log("SLDialogTest: Options must be a non-empty array.");
        return null;
    }

    // 2. Retrieve FlowURL and FlowToken
    $flowURL = NVGetValue('FlowURL');
    if (empty($flowURL)) {
        error_log("SLDialogTest: Failed to retrieve FlowURL.");
        return null;
    }

    $flowToken = NVGetValue('FlowToken');
    if (empty($flowToken)) {
        error_log("SLDialogTest: Failed to retrieve FlowToken.");
        return null;
    }

    // 3. Basic configuration
    $command = 'open_dialog';
    set_time_limit(60); // limit execution time to 60 seconds

    // 4. Paging configuration
    $maxButtons = 12;        // llDialog limit
    $reservedButtons = 0;    // count of reserved navigation/function buttons

    // If paging is needed, reserve 2 buttons (◀ and ▶)
    if ($needsPaging) {
        $reservedButtons += 2;
    }

    // If "back" is needed, reserve 1 button
    if ($needsBack) {
        $reservedButtons += 1;
    }

    // Number of user options per page
    $optionsPerPage = $maxButtons - $reservedButtons;

    // Edge case: if the number of options per page is below 1, show all
    if ($optionsPerPage < 1) {
        $optionsPerPage = count($options);
    }

    // If paging is disabled, we force only 1 page
    $totalOptions = count($options);
    $totalPages = ($needsPaging) ? ceil($totalOptions / $optionsPerPage) : 1;

    // Current page index
    $currentPage = 1;

    // 5. Main loop
    while (true) {
        // If paging is enabled, update prompt with page info
        $pageInfo = $needsPaging ? "[$currentPage / $totalPages]" : "";
        $promptWithPage = str_replace('<<PAGE>>', $pageInfo, $prompt);

        // If paging is on, slice the options; otherwise show all
        if ($needsPaging) {
            $offset = ($currentPage - 1) * $optionsPerPage;
            $optionsForPage = array_slice($options, $offset, $optionsPerPage);
        } else {
            $optionsForPage = $options;
        }

        // 6. Build the buttons
        $buttons = [];

        // If paging is needed, add previous-page button (◀) or space
        if ($needsPaging) {
            $buttons[] = ($currentPage > 1) ? '◀' : ' ';
        }

        // If back is needed, add BACK button
        if ($needsBack) {
            $buttons[] = 'BACK';
        }

        // If paging is needed, add next-page button (▶) or space
        if ($needsPaging) {
            $buttons[] = ($currentPage < $totalPages) ? '▶' : ' ';
        }

        // Reverse the list so the first appear at the top (comment from original code)
        $optionsForPage = array_reverse($optionsForPage, false);

        // Group the options in chunks of 3 and reverse each chunk
        $chunks = array_chunk($optionsForPage, 3);
        foreach ($chunks as &$chunk) {
            $chunk = array_reverse($chunk);
            $buttons = array_merge($buttons, $chunk);
        }

        // Build the comma-separated buttons string
        $buttonsString = implode(',', $buttons);

        // 7. Construct the data string and send the HTTPS POST request
        $data = $command . '|' . $flowToken . '|' . $recipient . '|' . $promptWithPage . '|' . $buttonsString;
        error_log($data);

        $ch = curl_init($flowURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/plain; charset=UTF-8'
        ]);
        // SSL checks disabled (not recommended for production)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 60-second timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        // Execute the request
        $response = curl_exec($ch);
        if ($response === false) {
            error_log("SLDialogTest: cURL error: " . curl_error($ch));
            curl_close($ch);
            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("SLDialogTest: HTTP error code: $httpCode");
            return null;
        }

        // Retrieve user selection
        $selection = trim($response);

        // 8. Handle user selection
        if ($needsPaging && $selection === '◀') {
            // Go to previous page
            $currentPage = max(1, $currentPage - 1);
            continue;

        } elseif ($needsPaging && $selection === '▶') {
            // Go to next page
            $currentPage = min($totalPages, $currentPage + 1);
            continue;

        } elseif ($needsBack && $selection === 'BACK') {
            // "BACK" action
            return 'BACK';

        } elseif (in_array($selection, $options)) {
            // Valid selection
            return $selection;

        } else {
            error_log("SLDialogTest: Invalid selection received.");
            return null;
        }
    }
}

?>
