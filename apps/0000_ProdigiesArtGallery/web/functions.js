// Render the table rows based on filters
function renderPaintingsTable() 
{

    // Reference the table elements
    const thead = document.querySelector('.paintings-table thead');
    const tbody = document.getElementById('paintings-tbody');

    // Clear previous rows
    tbody.innerHTML = "";

    // Build the list of visible columns (base + checked optionals)
    const visibleCols = [
        ...baseColumns,
        ...(window.optionalColumns || []).filter(c => c.checked).map(c => ({ key: c.key, label: c.label }))
    ];

    // How many of the visible columns are optional
    const optionalCount = visibleCols.length - baseColumns.length;

    // THEAD: rebuild entirely from visible columns
    thead.innerHTML = "";
    const theadRow = document.createElement('tr');
    thead.appendChild(theadRow);

    // Add header cells from visibleCols
    visibleCols.forEach(col => {
        const th = document.createElement('th');
        th.textContent = col.label || "";
        if (col.width) th.style.width = col.width;
        
        // Store the data key for sorting
        th.dataset.key = col.key;

        // If this is the active sort column, add arrow
        if (col.key === sortKey) {
            const arrow = sortDirection === "asc" ? "▲ " : "▼ ";
            th.textContent = arrow + th.textContent;
        }

        // Make sortable except for the special "_row" column
        if (col.key !== "_row")
        {

            // Cursor for sorting
            th.style.cursor = "pointer";

            // Add click handler for sorting
            th.addEventListener("click", async () => {
                if (sortKey === col.key) 
                {
                    
                    // Same column → toggle direction
                    sortDirection = (sortDirection === "asc") ? "desc" : "asc";
                
                } else 
                {

                    // New column → default to ascending
                    sortKey = col.key;
                    sortDirection = "asc";

                }

                // Persist current sorting preferences (server merges automatically)
                try {
                    const payload = JSON.stringify({ sort_key: sortKey, sort_dir: sortDirection });
                    const appId = await AFGetAppID();
                    await AFSendFlowMessage(appId, "Global", `SETPREFERENCES|${payload}`);
                } catch (e) {
                    console.warn("Failed to persist sorting prefs", e);
                }

                // Re-render the table with new sorting
                renderPaintingsTable();

            });

        }

        // Adds the header to the row
        theadRow.appendChild(th);

    });

    // Apply filters safely (status normalized to lowercase)
    const filtered = paintings.filter(p => activeFilters.includes((p.status || "").toLowerCase()));

    // Sort by current sortKey/direction
    filtered.sort((a, b) => {

        // Normalize values to strings; null/undefined become empty strings
        let sa = (a[sortKey] ?? "").toString();
        let sb = (b[sortKey] ?? "").toString();

        // Natural (numeric-aware) compare:
        // - "100 L$" > "40 L$" (compares 100 vs 40 correctly)
        // - "2025-08-22" > "2025-07-19"
        // - "17:28" > "11:58"
        // Case-insensitive thanks to sensitivity:"base"
        const cmp = sa.localeCompare(sb, undefined, { numeric: true, sensitivity: "base" });

        // Apply direction
        return sortDirection === "asc" ? cmp : -cmp;

    });

    // Loop through each painting (filtered by active filters)
    filtered.forEach(p => {
    
        // Create a new row
        const row = document.createElement('tr');

        // Build each cell in the order of visibleCols
        visibleCols.forEach(col => {
            const td = document.createElement('td');

            // Leading checkbox placeholder for the special "_row" column
            if (col.key === "_row") 
            {
            
                // Adds the checkbox
                const checkbox = document.createElement('input');
                checkbox.type = "checkbox";
                checkbox.classList.add("row-checkbox");
                checkbox.dataset.id = p.id;
                
                // Prevent row click when toggling checkbox
                checkbox.addEventListener('click', e => e.stopPropagation());
                checkbox.addEventListener('change', updateStartAuctionButton);
                td.appendChild(checkbox);
                row.appendChild(td);
                return;

            }

            // Status column: keep the badge styling
            if (col.key === "status") 
            {
            
                const status = (p.status || "").toLowerCase();
                const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : "";
                td.innerHTML = `<span class="status-${status}">${label}</span>`;
                row.appendChild(td);
                return;
            
            }

            // Default rendering for all other columns
            td.textContent = String(p[col.key] ?? "");
            
            // Appends the cell to the row
            row.appendChild(td);

        });

        // Row click: select the painting
        row.style.cursor = 'pointer';
        row.onclick = () => selectPainting(p);

        // Highlight selected row
        if (selectedPainting && selectedPainting.id === p.id) {
            row.style.background = '#ffecf5';
        }

        // Append row
        tbody.appendChild(row);

    });

    // Add a row for new painting
    const newRow = document.createElement('tr');

    // Empty space instead of the checkbox
    const emptySelectTd = document.createElement('td');
    newRow.appendChild(emptySelectTd);

    // Number input (manual UNICATxxx)
    const numberTd = document.createElement('td');
    const numberInput = document.createElement('input');
    numberInput.type = "text";
    numberInput.placeholder = "UNICAT123";
    numberInput.style.width = "95%";
    numberTd.appendChild(numberInput);
    newRow.appendChild(numberTd);

    // Title input
    const titleInputTd = document.createElement('td');
    const titleInput = document.createElement('input');
    titleInput.type = "text";
    titleInput.placeholder = "Title";
    titleInput.style.width = "95%";
    titleInputTd.appendChild(titleInput);
    newRow.appendChild(titleInputTd);

    // Creator input
    const creatorInputTd = document.createElement('td');
    const creatorInput = document.createElement('input');
    creatorInput.type = "text";
    creatorInput.placeholder = "Creator";
    creatorInput.style.width = "95%";
    creatorInputTd.appendChild(creatorInput);
    newRow.appendChild(creatorInputTd);

    // Static status: always inactive
    const statusTd = document.createElement('td');
    statusTd.innerHTML = `<span class="status-inactive">Inactive</span>`;
    newRow.appendChild(statusTd);

    // Pad one empty cell per visible optional column
    for (let i = 0; i < optionalCount; i++) {
        const td = document.createElement('td');
        td.textContent = "";
        newRow.appendChild(td);
    }

    // Append the row
    tbody.appendChild(newRow);

    // Add submit button in a new row below
    const btnRow = document.createElement('tr');

    // Left button (Start Auction)
    const startTd = document.createElement('td');
    startTd.colSpan = 2;
    startTd.style.textAlign = "left";

    const startAuctionButton = document.createElement('button');
    startAuctionButton.textContent = "🎯 Start Auction";
    startAuctionButton.disabled = true;
    startAuctionButton.id = "start-auction-btn";
    startAuctionButton.style.padding = "6px 12px";
    startAuctionButton.style.marginRight = "12px";
    startAuctionButton.style.opacity = 0.5;
    startAuctionButton.style.cursor = "not-allowed";

    // Binds the Start Auction button
    startAuctionButton.onclick = () => {
        
        // Populate default end date: now + 48h
        const now = new Date();
        const endDate = new Date(now.getTime() + 48 * 60 * 60 * 1000); // 48h ahead
        const formattedEndDate = endDate.toISOString().replace('T', ' ').substring(0, 19);

        document.getElementById("auction-end-date").value = formattedEndDate;
        document.getElementById("auction-modal").style.display = "flex";

    };

    // Declare Sold button
    const declareSoldButton = document.createElement('button');
    declareSoldButton.textContent = "✅ Declare Sold";
    declareSoldButton.disabled = true;
    declareSoldButton.id = "declare-sold-btn";
    declareSoldButton.style.padding = "6px 12px";
    declareSoldButton.style.marginRight = "12px";
    declareSoldButton.style.opacity = 0.5;
    declareSoldButton.style.cursor = "not-allowed";

    // Binds the Declare Sold button
    declareSoldButton.onclick = async () => {
        const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
        if (selected.length === 0) {
            alert("Please select at least one painting.");
            return;
        }

        // Get the titles to show in confirmation
        const names = selected.map(id => {
            const p = paintings.find(p => p.id === id);
            return p ? `${p.number} (${p.title})` : id;
        });

        const confirmText = `Are you sure you want to declare the following items as sold?\n\n${names.join("\n")}`;

        // User cancelled
        if (!confirm(confirmText)) { return; }

        const appId = await AFGetAppID();
        await AFSendFlowMessage(appId, "Global", `DECLARESOLD|${selected.join(";")}`);

        // Update local state
        selected.forEach(id => {
            const p = paintings.find(p => p.id === id);
            if (p) {
                p.status = "sold";
            }
        });

        // Refresh the list
        renderPaintingsTable();

        // Uncheck all checkboxes
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        
        // Update the Start Auction and Declare Sold buttons
        updateStartAuctionButton();

    };

    // Cancel button: hides modal
    document.getElementById("auction-cancel-btn").onclick = () => {
        document.getElementById("auction-modal").style.display = "none";
    };

    // Confirm button: sends auction start command
    document.getElementById("auction-confirm-btn").onclick = async () => {
        const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
        if (selected.length === 0) {
            alert("Please select at least one painting.");
            return;
        }

        const endDate = document.getElementById("auction-end-date").value.trim();
        const startPriceInput = document.getElementById("auction-start-price").value.trim();
        const startPrice = parseFloat(startPriceInput);

        // Validation
        if (!/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(endDate)) {
            alert("Invalid end date format. Use: YYYY-MM-DD HH:MM:SS");
            return;
        }

        if (isNaN(startPrice) || startPrice < 0) {
            alert("Invalid starting price.");
            return;
        }

        // Compose and send the backend message
        const message = `STARTAUCTION|${endDate}|${startPrice}|${selected.join(";")}`;
        const appId = await AFGetAppID();
        await AFSendFlowMessage(appId, "Global", message);

        // Locally update the status to "active"
        const now = new Date();
        selected.forEach(id => {
            const p = paintings.find(p => p.id === id);
            if (p) {
                p.status = "active";
                p.bestBid = null;
                p.winnerName = null;
                p.endDate = endDate.slice(0, 10);   // "YYYY-MM-DD" (UTC0 string as provided)
                p.endTime = endDate.slice(11, 16);  // "HH:MM"      (UTC0 string as provided)
                p.auction = {
                    startPrice: startPrice.toString(),
                    currentBid: "?",
                    bids: "?"
                };
            }
        });

        // Close modal
        document.getElementById("auction-modal").style.display = "none";

        // Refresh the list
        renderPaintingsTable();

        // Uncheck all checkboxes
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);

        // Update the Start Auction and Declare Sold buttons
        updateStartAuctionButton();

    };

    // Addint Start Auction and Declare Sold
    startTd.appendChild(startAuctionButton);
    startTd.appendChild(declareSoldButton);

    // "Set on Hold" button
    const setOnHoldButton = document.createElement('button');
    setOnHoldButton.textContent = "⏸️ Set on Hold";
    setOnHoldButton.disabled = true;
    setOnHoldButton.id = "set-on-hold-btn";
    setOnHoldButton.style.padding = "6px 12px";
    setOnHoldButton.style.marginRight = "12px";
    setOnHoldButton.style.opacity = 0.5;
    setOnHoldButton.style.cursor = "not-allowed";

    // Bind click: send DECLAREHOLD for selected items
    setOnHoldButton.onclick = async () => {
        const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
        if (selected.length === 0) {
            alert("Please select at least one painting.");
            return;
        }

        const names = selected.map(id => {
            const p = paintings.find(p => p.id === id);
            return p ? `${p.number} (${p.title})` : id;
        });

        const confirmText = `Set the following items to Hold?\n\n${names.join("\n")}`;
        if (!confirm(confirmText)) return;

        const appId = await AFGetAppID();
        await AFSendFlowMessage(appId, "Global", `DECLAREHOLD|${selected.join(";")}`);

        // Local state update
        selected.forEach(id => {
            const p = paintings.find(p => p.id === id);
            if (p) p.status = "hold";
        });

        renderPaintingsTable();
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        updateStartAuctionButton();

    };

    // Add to left cell with the other action buttons
    startTd.appendChild(setOnHoldButton);

    // Add painting button
    const btnTd = document.createElement('td');
    btnTd.colSpan = 3 + optionalCount;
    btnTd.style.textAlign = "right";
    const addButton = document.createElement('button');
    addButton.textContent = "➕ Add Painting";
    addButton.style.padding = "6px 12px";
    addButton.style.cursor = "pointer";
    addButton.onclick = async () => {
        const number = numberInput.value.trim();
        const title = titleInput.value.trim();
        const creator = creatorInput.value.trim();
        const description = "(new entry)";

        if (!number || !title || !creator) {
            alert("Please fill in all fields: number, title, creator.");
            return;
        }

        // Send to backend
        const msg = `ADDUNICAT|${number}|${title}|${creator}|${description}`;
        const appId = await AFGetAppID();
        const resp = await AFSendFlowMessage(appId, "Global", msg);

        // If the response is false, it means the number already exists
        if (resp == false) {
            alert(`A painting with number ${number} already exists.`);
            return; // stop here, don't add locally
        }

        // Optional: Add locally
        const newPainting = {
            id: number,
            unicat: true,
            number,
            title,
            creator,
            status: "inactive",
            preview: "",
            description,
            auction: { startingPrice: "?", currentBid: "?", bids: "?" },
            tracking: { owner: "?", provenance: [], lastMoved: "?" }
        };

        paintings.push(newPainting);
        selectedPainting = newPainting;
        renderPaintingsTable();
        selectPainting(newPainting);

    };

    btnTd.appendChild(addButton);
    btnRow.appendChild(startTd);
    btnRow.appendChild(btnTd);
    tbody.appendChild(btnRow);

    // Function to enable/disable Add Painting button based on inputs
    function checkAddButtonState() {
        const allFilled = numberInput.value.trim() !== "" &&
                        titleInput.value.trim() !== "" &&
                        creatorInput.value.trim() !== "";

        addButton.disabled = !allFilled;
        addButton.style.opacity = allFilled ? "1" : "0.5";
        addButton.style.cursor = allFilled ? "pointer" : "not-allowed";
    }

    // Listen for input changes in the three fields
    [numberInput, titleInput, creatorInput].forEach(input => {
        input.addEventListener('input', checkAddButtonState);
    });

    // Initial check to disable the button by default
    checkAddButtonState();

    // Auto-select first painting if none selected
    if (!selectedPainting && filtered.length > 0) {
        selectPainting(filtered[0]);
    }
    // If filters remove selected painting, select first visible
    else if (
        selectedPainting &&
        !filtered.some(p => p.id === selectedPainting.id) &&
        filtered.length > 0
    ) {
        selectPainting(filtered[0]);
    }
    // If nothing left, clear details
    else if (filtered.length === 0) {
        clearDetails();
    }

}

// Select a painting and show its details
async function selectPainting(painting) {
    
    selectedPainting = painting;

    const previouslySelectedIds = getSelectedIds();
    renderPaintingsTable();
    restoreSelection(previouslySelectedIds);
    updateStartAuctionButton();

    // Reference to the preview container
    const preview = document.getElementById('preview-image');

    // Use cache if possible
    if (imageCache[painting.number]) {
        preview.innerHTML = `
            <img src="${imageCache[painting.number]}" alt="Preview" style="max-width:100%;max-height:100%;">
            <span class="preview-upload-icon" title="Upload image">⬆️</span>
        `;

        // RE-ASSIGN THE CLICK LISTENER HERE
        const uploadIcon = preview.querySelector(".preview-upload-icon");
        uploadIcon.onclick = () => {
            const fileInput = document.getElementById("image-upload-input");
            fileInput.click();
        };

        // Restore last used tab
        setActiveTab(currentTab);

        // No need to reload the image
        return;

    }

    // Show a loading state
    preview.innerHTML = `<span style="color:#bbb;font-size:1.6em;">Loading...</span>`;

    const appId = await AFGetAppID();
    const base64 = await AFSendFlowMessage(appId, "Global", `GETIMAGE|${painting.number}`);

    // Debug: Show in console
    //console.log('Fetched Base64:', base64 ? base64.substring(0, 40) : "(empty)", "length:", base64 ? base64.length : 0);

    if (!base64 || typeof base64 !== 'string' || base64.length < 100) {
        preview.innerHTML = `
            <span style="color:#bbb;font-size:2em;">🎨</span>
            <span class="preview-upload-icon" title="Upload image">⬆️</span>
        `;
    } else {
        // Heuristic: JPEG files usually start with "/9j/"
        let mime = "image/png";
        if (base64.startsWith("/9j/")) mime = "image/jpeg";
        const dataUrl = `data:${mime};base64,${base64}`;
        imageCache[painting.number] = dataUrl;
        preview.innerHTML = `
            <img src="${dataUrl}" alt="Preview" style="max-width:100%;max-height:100%;">
            <span class="preview-upload-icon" title="Upload image">⬆️</span>
        `;
    }

    // After inserting the HTML preview
    const uploadIcon = preview.querySelector(".preview-upload-icon");
    uploadIcon.onclick = () => {
        const fileInput = document.getElementById("image-upload-input");
        fileInput.click();
    };

    // Restore last used tab
    setActiveTab(currentTab);

}

// Set active tab and render its content
function setActiveTab(tab) {

    // Memorize selected tab
    currentTab = tab;

    document.querySelectorAll('.tab').forEach(tabEl => {
        tabEl.classList.toggle('active', tabEl.getAttribute('data-tab') === tab);
    });
    if (!selectedPainting) return;
    const content = document.getElementById('tab-content');
    if (tab === "general") {

        // Get unique category values from existing paintings
        const allCategories = [...new Set(paintings.map(p => p.category).filter(Boolean))];

        content.innerHTML = `
            <div><span class="field-label">Number:</span> ${selectedPainting.number}</div>
            <div><span class="field-label">Title:</span> ${selectedPainting.title}</div>
            <div><span class="field-label">Creator:</span> ${selectedPainting.creator}</div>
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <span class="field-label" style="min-width: 80px;">Category:</span>
                <input list="category-suggestions" id="category-input" style="flex: 1;" value="${selectedPainting.category || ''}">
                <datalist id="category-suggestions">
                    ${allCategories.map(c => `<option value="${c}">`).join('')}
                </datalist>
            </div>
            <div>
                <span class="field-label">Description:</span>
                <textarea id="description-input" rows="3" style="width:90%;">${selectedPainting.description || ''}</textarea>
                <button id="update-description-btn" style="margin-top:6px; padding:4px 10px;">Update</button>
            </div>
        `;

        // Attach click handler after injection
        document.getElementById("update-description-btn").onclick = async () => {
            
            // Get new values from input fields
            const newDescription = document.getElementById("description-input").value.trim();
            const newCategory = document.getElementById("category-input").value.trim();
            const appId = await AFGetAppID();

            // Send both updates to the backend
            const respDesc = await AFSendFlowMessage(appId, "Global", `SETDESCRIPTION|${selectedPainting.number}|${newDescription}`);
            const respCat  = await AFSendFlowMessage(appId, "Global", `SETCATEGORY|${selectedPainting.number}|${newCategory}`);

            if (respDesc == true) {
                // Update selected painting locally
                selectedPainting.description = newDescription;
                selectedPainting.category = newCategory;

                // Also update in the global paintings list
                const p = paintings.find(x => x.id === selectedPainting.id);
                if (p) {
                    p.description = newDescription;
                    p.category = newCategory;
                }

                // Refresh category suggestions in case a new one was added
                const allCategories = [...new Set(paintings.map(p => p.category).filter(Boolean))];
                const datalist = document.getElementById('category-suggestions');
                datalist.innerHTML = allCategories.map(c => `<option value="${c}">`).join('');

                // The Update button is greyed out
                const btn = document.getElementById("update-description-btn");
                btn.disabled = true;

                // Optional: show success silently or with a toast/message
                // alert("Updated successfully.");
            } else {
                alert("Failed to update one or more fields.");
            }
        };

        // Link input changes to re-enable the button
        document.getElementById("description-input").addEventListener("input", enableUpdateButtonIfChanged);
        document.getElementById("category-input").addEventListener("input", enableUpdateButtonIfChanged);

        // Run once to ensure initial disabled state
        enableUpdateButtonIfChanged();

    }
    else if (tab === "auction") 
    {
    
        // Show loading placeholder while fetching auction info
        content.innerHTML = `<div style="color:#999;">Loading auction info...</div>`;

        // Async function to load and render auction history
        (async () => {
            const appId = await AFGetAppID();

            // Request auction info from the backend
            const raw = await AFSendFlowMessage(appId, "Global", `GETAUCTIONINFO|${selectedPainting.number}`);
            const result = typeof raw === 'string' ? JSON.parse(raw) : raw;

            // Handle error or missing data
            if (!result || result.error) {
                content.innerHTML = `<div style="color:red;">${result?.error || "Unable to fetch auction info."}</div>`;
                return;
            }

            // Normalize to an array of auctions (single or multiple)
            let allAuctions = [];
            if (Array.isArray(result)) {
                allAuctions = result;
            } else if (result && result.start_date) {
                allAuctions = [result]; // legacy: single object
            }

            if (allAuctions.length === 0) {
                content.innerHTML = `<div style="color:red;">No auction data found.</div>`;
                return;
            }

            // Sort auctions by descending start date (latest first)
            allAuctions.sort((a, b) => new Date(b.start_date) - new Date(a.start_date));

            // Create the selector (dropdown) for choosing an auction
            const select = document.createElement('select');
            select.style.marginBottom = "10px";
            select.style.padding = "4px";
            select.style.width = "100%";

            allAuctions.forEach((auction, index) => {
                const option = document.createElement('option');

                // Checks if auction is currently active based on end_date
                const end = new Date(auction.end_date.replace(' ', 'T') + 'Z'); // ISO UTC
                const isActive = !isNaN(end) && end.getTime() > Date.now();

                // Display label with active status if applicable
                const label = `${auction.start_date} → ${auction.end_date}` + (isActive ? " (Currently Active)" : "");
                option.textContent = label;
                option.value = index;

                select.appendChild(option);
            });

            // Clear the tab content
            content.innerHTML = "";

            // Create a wrapper for the dropdown
            const selectorWrapper = document.createElement('div');
            selectorWrapper.style.marginBottom = "10px";

            const label = document.createElement('label');
            label.innerHTML = "<strong>Select Auction:</strong><br/>";
            selectorWrapper.appendChild(label);
            selectorWrapper.appendChild(select);

            content.appendChild(selectorWrapper);

            // Container for the auction details
            const detailDiv = document.createElement('div');
            content.appendChild(detailDiv);

            // Function to display a selected auction's details
            function renderAuction(index) {
                const auction = allAuctions[index];
                const html = [];

                html.push(`<p><strong>Start Price:</strong> L$${auction.start_price}</p>`);
                html.push(`<p><strong>Start Date:</strong> ${auction.start_date}</p>`);
                html.push(`<p><strong>End Date:</strong> ${auction.end_date}</p>`);
                html.push(`<p><strong>Current Bid:</strong> L$${auction.current_bid}</p>`);
                html.push(`<p><strong>Bid Count:</strong> ${auction.bid_count}</p>`);

                if (auction.bidders && auction.bidders.length > 0) {
                    html.push(`<table style="width:100%; border-collapse: collapse; margin-top:10px; font-size:0.9em;">`);
                    html.push(`<thead>
                        <tr>
                            <th style="text-align:left; padding:4px;">Name</th>
                            <th style="text-align:left; padding:4px;">Amount</th>
                            <th style="text-align:left; padding:4px;">Time</th>
                        </tr>
                    </thead><tbody>`);
                    
                    // Sort bids descending by amount (or time as fallback)
                    const sortedBids = [...auction.bidders].sort((a, b) => {
                        const amountDiff = parseFloat(b.amount) - parseFloat(a.amount);
                        if (amountDiff !== 0) return amountDiff;
                        return new Date(b.time) - new Date(a.time);
                    });

                    for (const bid of sortedBids) {

                        html.push(`<tr>
                            <td style="padding:4px;">${bid.name}</td>
                            <td style="padding:4px;">L$${bid.amount}</td>
                            <td style="padding:4px;">${bid.time}</td>
                        </tr>`);
                    }
                    html.push(`</tbody></table>`);
                } else {
                    html.push(`<p>No bids yet.</p>`);
                }

                detailDiv.innerHTML = html.join("");
            }

            // Initial render: show latest auction by default
            renderAuction(0);

            // Update detail view on dropdown change
            select.onchange = () => {
                const idx = parseInt(select.value, 10);
                renderAuction(idx);
            };

        })();

    }
    else if (tab === "tracking") 
    {
    
        content.innerHTML = `<div style="color:#999;">Loading tracking info...</div>`;

        (async () => {
            const appId = await AFGetAppID();
            const raw = await AFSendFlowMessage(appId, "Global", `GETTRACKING|${selectedPainting.number}`);

            let entries = [];
            try {
                entries = typeof raw === 'string' ? JSON.parse(raw) : raw;
            } catch (e) {
                content.innerHTML = `<div style="color:red;">Error loading tracking info.</div>`;
                return;
            }

            if (!entries || entries.length === 0) {
                content.innerHTML = `<div style="color:#999;">No tracking information available.</div>`;
                return;
            }

            // Sort from latest to oldest
            entries.sort((a, b) => new Date(b.time) - new Date(a.time));

            // Build the HTML
            let html = `<div style="margin-bottom: 10px;"><strong>Last Owner:</strong> ${entries[0].OwnerName}</div>`;
            html += `<table style="width:100%; border-collapse:collapse; font-size:0.9em;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:4px;">Date</th>
                        <th style="text-align:left; padding:4px;">Owner</th>
                        <th style="text-align:left; padding:4px;">Position</th>
                    </tr>
                </thead>
                <tbody>`;

            for (const entry of entries) {
                const date = entry.time;
                const owner = entry.OwnerName;
                const pos = entry.FlowObjectPosition;
                const region = entry.FlowRegionName || "Unknown";
                const posText = pos ? `${region} (${pos.x.toFixed(1)}, ${pos.y.toFixed(1)}, ${pos.z.toFixed(1)})` : region;
                html += `<tr>
                            <td style="padding:4px;">${date}</td>
                            <td style="padding:4px;">${owner}</td>
                            <td style="padding:4px;">${posText}</td>
                        </tr>`;
            }

            html += `</tbody></table>`;
            content.innerHTML = html;

        })();

    }

}

// Updates the state of the Start Auction button (greyed out or not)
function updateStartAuctionButton() 
{

    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const selectedIds = Array.from(checkboxes).map(cb => cb.dataset.id);
    const selectedPaintings = paintings.filter(p => selectedIds.includes(p.id));

    const startAuctionBtn = document.getElementById("start-auction-btn");
    const declareSoldBtn = document.getElementById("declare-sold-btn");
    const setOnHoldBtn    = document.getElementById("set-on-hold-btn");

    // Start Auction: only if all selected are NOT active
    const allNonActive = selectedPaintings.every(p => p.status !== "active");
    const enableStartAuction = selectedPaintings.length > 0 && allNonActive;

    if (startAuctionBtn) {
        startAuctionBtn.disabled = !enableStartAuction;
        startAuctionBtn.style.opacity = enableStartAuction ? "1" : "0.5";
        startAuctionBtn.style.cursor = enableStartAuction ? "pointer" : "not-allowed";
    }

    // Declare Sold: only if all selected are ended
    const allEnded = selectedPaintings.length > 0 && selectedPaintings.every(p => p.status === "ended");

    if (declareSoldBtn) {
        declareSoldBtn.disabled = !allEnded;
        declareSoldBtn.style.opacity = allEnded ? "1" : "0.5";
        declareSoldBtn.style.cursor = allEnded ? "pointer" : "not-allowed";
    }

    // Set on Hold: allowed if ALL selected are NOT active with bids and NOT sold and NOT already on hold
    // (i.e., allowed for inactive, ended)
    
    // Base rule: allowed if all selected are NOT sold and NOT already on hold
    let canHold = selectedPaintings.length > 0 &&
                selectedPaintings.every(p => p.status !== "sold" && p.status !== "hold");

    // Extra rule: block if any selected item is ACTIVE and has a non-empty bestBid
    if (canHold) {
        const hasActiveWithBids = selectedPaintings.some(p => {
            const status = (p.status || "").toLowerCase();
            // Treat "", null, undefined as "no bids"
            const hasBid = p.bestBid != null && String(p.bestBid).trim() !== "";
            return status === "active" && hasBid;
        });
        if (hasActiveWithBids) canHold = false;
    }

    if (setOnHoldBtn) {
        setOnHoldBtn.disabled = !canHold;
        setOnHoldBtn.style.opacity = canHold ? "1" : "0.5";
        setOnHoldBtn.style.cursor  = canHold ? "pointer" : "not-allowed";
    }

}

// Clear details panel
function clearDetails() {
    selectedPainting = null;
    document.getElementById('preview-image').innerHTML = `<span style="color:#bbb;">No preview</span>`;
    document.getElementById('tab-content').innerHTML = `<div style="color:#aaa;">No painting selected.</div>`;
}

// Return the IDs of all selected (checked) rows in the table
function getSelectedIds() {
    return Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.dataset.id);
}

// Restore the selection state of checkboxes based on a given list of IDs
function restoreSelection(ids) {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = ids.includes(cb.dataset.id);
    });
}

// Function to manage to grey out the update when not needed
function enableUpdateButtonIfChanged() {
    const descInput = document.getElementById("description-input");
    const catInput = document.getElementById("category-input");
    const btn = document.getElementById("update-description-btn");

    const descChanged = descInput.value.trim() !== (selectedPainting.description || "").trim();
    const catChanged = catInput.value.trim() !== (selectedPainting.category || "").trim();

    btn.disabled = !(descChanged || catChanged);
}

// Build menu, wire events, and provide rendering helpers (no initial render here)
async function initializeOptionalColumns() 
{

    // References to key elements
    const container = document.getElementById('column-options');
    const btn  = document.getElementById('column-options-btn');
    const menu = document.getElementById('column-options-menu');
    if (!container || !btn || !menu) return;

    // Build the menu from config
    menu.innerHTML = optionalColumns.map(c => `
        <label class="column-option-item">
        <input type="checkbox" value="${c.key}" ${c.checked ? "checked" : ""}>
        <span>${c.label}</span>
        </label>
    `).join("");

    // Toggle open/close
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        container.classList.toggle('open');
    });
    menu.addEventListener('click', (e) => e.stopPropagation());
    document.addEventListener('click', () => container.classList.remove('open'));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') container.classList.remove('open'); });

    // On checkbox change: sync flags + (optional) persist + re-render
    menu.addEventListener('change', async () => {
        menu.querySelectorAll('input[type="checkbox"]').forEach(input => {
            const col = optionalColumns.find(c => c.key === input.value);
            if (col) col.checked = input.checked;
        });

        // Build minimal payload (only selected keys)
        const activeCols = (window.optionalColumns || [])
        .filter(c => c.checked)
        .map(c => c.key);

        // Keep JS cache in sync
        window.preferences.active_columns = activeCols;

        // Persist to backend (server merges automatically)
        const payload = JSON.stringify({ active_columns: activeCols });
        const appId = await AFGetAppID();
        AFSendFlowMessage(appId, "Global", `SETPREFERENCES|${payload}`);

        // Re-render the table with new columns
        renderPaintingsTable();

    });

}

// Load preferences from backend and apply to UI state
async function loadPreferences() 
{
  
    try {
        
        // Request preferences from backend
        const appId = await AFGetAppID();
        const raw = await AFSendFlowMessage(appId, "Global", "GETPREFERENCES");
        const prefs = raw ? (typeof raw === "string" ? JSON.parse(raw) : raw) : {};
        window.preferences = (prefs && typeof prefs === "object") ? prefs : {};
        
    } catch (e) {
        
        console.log("GETPREFERENCES failed; using empty preferences.", e);
  
    }

    // Apply active_columns to UI flags
    const activeKeys = Array.isArray(window.preferences.active_columns)
        ? window.preferences.active_columns
        : [];

    (window.optionalColumns || []).forEach(c => {
        c.checked = activeKeys.includes(c.key);
    });

    // Apply active_filters
    if (Array.isArray(window.preferences.active_filters)) {
        
        // Set activeFilters from preferences
        activeFilters = window.preferences.active_filters.slice();

        // Reflect into the UI checkboxes (no 'change' event fired)
        document.querySelectorAll('.filter-checkbox').forEach(cb => {
            cb.checked = activeFilters.includes(cb.value);
        });

    }

    // Apply sort prefs if provided (use values as-is)
    if (typeof window.preferences.sort_key === "string") 
    {
        sortKey = window.preferences.sort_key;
    }
    if (window.preferences.sort_dir === "asc" || window.preferences.sort_dir === "desc") 
    {
        sortDirection = window.preferences.sort_dir;
    }

}