// Render the table rows based on filters
function renderPaintingsTable() 
{

    // Reference the tbody element
    const tbody = document.getElementById('paintings-tbody');
    
    // Clear existing rows
    tbody.innerHTML = '';

    // Optional columns
    const enabledCols = (window.optionalColumns || []).filter(c => c.checked);

    // Clears the optional columns from the header
    const theadRow = document.querySelector('.paintings-table thead tr');
    theadRow.querySelectorAll('th[data-optional="1"]').forEach(th => th.remove());

    // Adds the header row with checked optional columns
    enabledCols.forEach(col => {
    const th = document.createElement('th');
    th.textContent = col.label;
    th.setAttribute('data-optional', '1');
    theadRow.appendChild(th);
    });
    
    // Loop through paintings and add values to the columns
    const filtered = paintings.filter(p => activeFilters.includes(p.status));
    filtered.forEach((p, idx) => {

        // Create a new row
        const row = document.createElement('tr');

        // Leading checkbox
        const selectTd = document.createElement('td');
        const checkbox = document.createElement('input');
        checkbox.type = "checkbox";
        checkbox.classList.add("row-checkbox");
        checkbox.dataset.id = p.id;
        checkbox.addEventListener('click', e => e.stopPropagation());
        checkbox.addEventListener('change', updateStartAuctionButton);
        selectTd.appendChild(checkbox);
        row.appendChild(selectTd);
        
        // UNICAT column (show number)
        const unicatTd = document.createElement('td');
        unicatTd.textContent = p.number;
        row.appendChild(unicatTd);
        
        // Title
        const titleTd = document.createElement('td');
        titleTd.textContent = p.title;
        row.appendChild(titleTd);
        
        // Creator
        const creatorTd = document.createElement('td');
        creatorTd.textContent = p.creator;
        row.appendChild(creatorTd);
        
        // Status
        const statusTd = document.createElement('td');
        let statusClass = 'status-' + p.status;
        let statusText = p.status.charAt(0).toUpperCase() + p.status.slice(1);
        statusTd.innerHTML = `<span class="${statusClass}">${statusText}</span>`;
        row.appendChild(statusTd);

        // Adds values for optional columns
        enabledCols.forEach(col => {
            const td = document.createElement('td');
            td.setAttribute('data-optional', '1');
            td.textContent = String(p[col.key] ?? "");
            row.appendChild(td);
        });

        // Row click: select painting
        row.style.cursor = 'pointer';
        row.onclick = () => selectPainting(p);

        // Highlight selected row
        if (selectedPainting && selectedPainting.id === p.id) {
            row.style.background = '#ffecf5';
        }

        // Appens the line to the tbody
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

    // Pad optional columns for the "new painting" row
    enabledCols.forEach(() => {
        const td = document.createElement('td');
        td.textContent = "";
        td.setAttribute('data-optional', '1');
        newRow.appendChild(td);
    });

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

        renderPaintingsTable();
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        updateStartAuctionButton(); // Also updates Declare Sold

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
                p.auction = {
                    startPrice: startPrice.toString(),
                    currentBid: "?",
                    bids: "?"
                };
                // Optional: you can store end date too if needed
            }
        });

        // Refreshes the list
        renderPaintingsTable();

        // Close modal
        document.getElementById("auction-modal").style.display = "none";

        // Refresh UI
        renderPaintingsTable();
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        updateStartAuctionButton();

    };

    // Addint Start Auction and Declare Sold
    startTd.appendChild(startAuctionButton);
    startTd.appendChild(declareSoldButton);

    // Add painting button
    const btnTd = document.createElement('td');
    btnTd.colSpan = 3 + enabledCols.length;
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
        await AFSendFlowMessage(appId, "Global", msg);

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

                const isActive = auction.current_bid !== "?" && auction.end_date && new Date(auction.end_date) > new Date();

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

  const container = document.getElementById('column-options');
  const btn  = document.getElementById('column-options-btn');
  const menu = document.getElementById('column-options-menu');
  if (!container || !btn || !menu) return;

  // (Optional) Load per-user prefs from backend — keep commented for now
  // try {
  //   const appId = await AFGetAppID();
  //   const raw = await AFSendFlowMessage(appId, "Global", "GET_USER_COL_PREFS");
  //   const prefs = typeof raw === "string" ? JSON.parse(raw) : raw; // e.g., ["category","owner"]
  //   if (Array.isArray(prefs)) {
  //     optionalColumns.forEach(c => c.checked = prefs.includes(c.key));
  //   }
  // } catch(e) { console.warn("Column prefs load failed; using defaults.", e); }

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

    // (Optional) persist user choice
    // const payload = JSON.stringify(optionalColumns.filter(c=>c.checked).map(c=>c.key));
    // await AFSendFlowMessage(await AFGetAppID(), "Global", `SET_USER_COL_PREFS|${payload}`);

    if (typeof renderPaintingsTable === 'function') 
        {
            renderPaintingsTable();
            console.log("Optional columns updated and table re-rendered.");

        }
  });

}