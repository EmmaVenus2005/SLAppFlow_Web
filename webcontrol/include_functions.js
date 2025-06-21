// Self-executing async function to avoid global scope pollution
window.WebControlReady = (async function () {

    // API that returns list of available PHP functions
    const endpoint = '/webcontrol/wcfunctions.php';

    try {
        
        // Fetch the list of available PHP functions
        const response = await fetch(endpoint);
        const functionList = await response.json();

        // Iterate over each function definition
        functionList.forEach(entry => {
            const functionName = entry.name;
            const paramCount = entry.params;

            // Dynamically declare the function in global scope
            window[functionName] = async function (...args) {
                if (args.length !== paramCount) {
                    console.warn(`[WebControl] Function ${functionName} expects ${paramCount} parameter(s), but got ${args.length}.`);
                }

                // Call the backend API
                const res = await fetch('/webcontrol/wccall.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        Function: functionName,
                        Params: args
                    })
                });
                const json = await res.json();

                // Handle API response
                if (json.Success !== 'True') {
                    throw new Error(`[WebControl] ${functionName} failed: ${json.Message || 'Unknown error'}`);
                }

                // Try to parse Return if it's JSON
                try {
                    return typeof json.Return === 'string'
                        ? JSON.parse(json.Return)
                        : json.Return;
                } catch {
                    return json.Return;
                }
            };

            // Log to console when the function is registered
            console.log(`[WebControl] Loaded function: ${functionName} (${paramCount} parameter${paramCount !== 1 ? 's' : ''})`);

        });

    } catch (error) {
        console.error('[WebControl] Failed to initialize API functions:', error);
    }

})();