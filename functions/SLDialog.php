<?php

/**
 * Displays an interactive dialog in Second Life using FlowApp HTTP gateway.
 * Handles paging, "BACK" buttons, and a flexible set of options.
 * 
 * @param string $object      // The object ID (usually SL prim UUID)
 * @param string $recipient   // The recipient's UUID (must be 36-char SL UUID)
 * @param string $leading     // Text displayed at the top of the dialog (can include <<PAGE>> placeholder)
 * @param string $trailing    // Text displayed at the bottom of the dialog (can include <<PAGE>> placeholder)
 * @param array  $choices     // List of text choices displayed in the dialog (one per button)
 * @param array  $options     // List of values sent back when a button is pressed (mapped to $choices, same order)
 * @param bool   $needsPaging // Whether to enable pagination if there are more than 12 options
 * @param bool   $needsBack   // Whether to include a "BACK" button in the dialog
 * @return string|null        // The selected option, or 'BACK', or null if error
 */
function SLDialog($object, $recipient, $leading, $trailing, $choices, $options, $needsPaging = false, $needsBack = false) {
    
    // Context variables
    global $conn, $appid, $uuid, $name;

    // 1. Parameter validation
    if (empty($recipient) || !preg_match('/^[a-f0-9\-]{36}$/', $recipient)) {
        error_log("SLDialogTest: Invalid recipient UUID.");
        return null;
    }

    if (!is_string($leading) || !is_string($trailing)) {
        error_log("SLDialogTest: Invalid leading or trailing text.");
        return null;
    }

    if (!is_array($choices) || !is_array($options)) {
        error_log("SLDialogTest: Choices and options must be arrays.");
        return null;
    }

    // 2. Retrieve FlowURL and FlowToken
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLDialogTest: Failed to retrieve FlowURL.");
        return null;
    }

    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLDialogTest: Failed to retrieve FlowToken.");
        return null;
    }

    // 3. Basic configuration
    $command = 'open_dialog';
    set_time_limit(60); // limit execution time to 60 seconds

    // 4. Paging configuration
    $maxButtons = 12;
    $reservedButtons = 0;

    if ($needsPaging) {
        $reservedButtons += 2; // ◀ and ▶ for pagination
    }

    if ($needsBack) {
        $reservedButtons += 1; // BACK button
    }

    $optionsPerPage = $maxButtons - $reservedButtons;
    if ($optionsPerPage < 1) {
        $optionsPerPage = count($options);
    }

    $totalOptions = count($options);
    $totalPages = ($needsPaging) ? ceil($totalOptions / $optionsPerPage) : 1;

    $currentPage = 1;

    // 5. Main loop
    while (true) 
    {
    
        // Slice the options and choices according to the current page
        if ($needsPaging) {
            $offset = ($currentPage - 1) * $optionsPerPage;
            $optionsForPage = array_slice($options, $offset, $optionsPerPage);
            $choicesForPage = array_slice($choices, $offset, $optionsPerPage);
        } else {
            $optionsForPage = $options;
            $choicesForPage = $choices;
        }

        // Replace <<PAGE>> in leading and trailing
        $pageInfo = $needsPaging ? "[$currentPage / $totalPages]" : "";

        $leadingWithPage = str_replace('<<PAGE>>', $pageInfo, $leading);
        $trailingWithPage = str_replace('<<PAGE>>', $pageInfo, $trailing);

        // Throwing empty choices
        // Useful when there is a 'NONE' button, and an empty string as choice
        $choicesForPage = array_filter($choicesForPage, function ($value) {
            return $value !== '';
        });

        // Build the prompt with leading, choices, and trailing text
        $promptWithPage = $leadingWithPage . "\n" . implode("\n", $choicesForPage) . "\n" . $trailingWithPage;

        // Add previous page button if paging is enabled
        if ($needsPaging) {
            $optionsForPage[] = ($currentPage > 1) ? '◀' : ' ';
        }

        // Add BACK button if needed (along with paging, else will be added further)
        if ($needsBack) {
            $optionsForPage[] = 'BACK';
        }

        // Add next page button if paging is enabled
        if ($needsPaging) {
            $optionsForPage[] = ($currentPage < $totalPages) ? '▶' : ' ';
        }

        // Reverse the list so the first option appears at the top
        $optionsForPage = array_reverse($optionsForPage, false);

        // Build the buttons
        $buttons = [];

        // Group the options into chunks of 3 and reverse each chunk
        $chunks = array_chunk($optionsForPage, 3);
        foreach ($chunks as &$chunk) {
            $chunk = array_reverse($chunk);
            $buttons = array_merge($buttons, $chunk);
        }

        // Build the comma-separated buttons string
        $buttonsString = implode(',', $buttons);

        // 6. Construct the data string and send the HTTPS POST request
        $data = $command . '|' . $flowToken . '|' . $recipient . '|' . $promptWithPage . '|' . $buttonsString;
        error_log($data);

        // Initialize cURL
        $ch = curl_init($flowURL);

        // Preparing the headers
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/plain; charset=UTF-8'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        // Executes the request
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

        $selection = trim($response);

        // 7. Handle user selection
        if ($needsPaging && $selection === '◀') {
            $currentPage = max(1, $currentPage - 1);
            continue;

        } elseif ($needsPaging && $selection === '▶') {
            $currentPage = min($totalPages, $currentPage + 1);
            continue;

        } elseif ($needsBack && $selection === 'BACK') {
            return 'BACK';

        } elseif (in_array($selection, $options)) {
            return $selection;

        } else {
            error_log("SLDialogTest: Invalid selection received.");
            return null;
        }

    }

}