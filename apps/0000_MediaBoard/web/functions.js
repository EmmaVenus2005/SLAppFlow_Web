async function displayImage() 
{

    // Reference to the preview container
    const preview = document.getElementById('preview-image');

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

}

/**
 * addCreateProjectListener
 *
 * Wires the "Add Project" (+) button to open the overlay, prompt for a name,
 * call the backend command "ADD_PROJECT|<name>", then inject the created project
 * into the project <select> and select it.
 *
 * Requirements:
 * - AFSendFlowMessage(appId, userId, message), AFGetAppID(), AFGetOwnerID() must exist.
 *
 * @param {Object} opts
 * @param {string} [opts.addButtonId="add-project-btn"]      DOM id of the + button
 * @param {string} [opts.projectSelectId="project-select"]    DOM id of the projects <select>
 */
function addCreateProjectListener(opts = {}) {
    const addButtonId = opts.addButtonId || "add-project-btn";
    const projectSelectId = opts.projectSelectId || "project-select";

    const addBtn = document.getElementById(addButtonId);
    if (!addBtn) return;

    // Prevent double-binding if called multiple times
    if (addBtn.dataset.listenerBound === "1") return;
    addBtn.dataset.listenerBound = "1";

    // -------------------------------------------------------------------------
    // Private helpers (NOT exposed outside this function)
    // -------------------------------------------------------------------------

    const openOverlay = (title, html) => {
        const overlay = document.getElementById("add-entity-overlay");
        const titleEl = document.getElementById("add-entity-title");
        const content = document.getElementById("add-entity-content");

        if (!overlay || !titleEl || !content) return;

        titleEl.textContent = title || "Add";
        content.innerHTML = html || "";
        overlay.style.display = "block";
    };

    const closeOverlay = () => {
        const overlay = document.getElementById("add-entity-overlay");
        const content = document.getElementById("add-entity-content");

        if (!overlay || !content) return;

        overlay.style.display = "none";
        content.innerHTML = "";
    };

    const showError = (errorBox, msg) => {
        if (!errorBox) return;
        errorBox.textContent = msg;
        errorBox.style.display = "block";
    };

    const doCreate = async ({ nameInput, confirmBtn, errorBox }) => {
        const projectName = (nameInput?.value || "").trim();
        if (!projectName) {
            showError(errorBox, "Please enter a project name.");
            return;
        }

        if (confirmBtn) confirmBtn.disabled = true;

        try {
            // Avoid breaking CMD|arg protocol
            const safeName = projectName.replaceAll("|", " ");

            const res = await AFSendFlowMessage(
                await AFGetAppID(),
                await AFGetOwnerID(),
                "ADD_PROJECT|" + safeName
            );

            // Expected: { project_id, name }
            if (!res || !res.project_id) {
                showError(errorBox, "Project creation failed.");
                if (confirmBtn) confirmBtn.disabled = false;
                return;
            }

            const projectSelect = document.getElementById(projectSelectId);
            if (projectSelect) {
                const opt = document.createElement("option");
                opt.value = res.project_id;
                opt.textContent = res.name || safeName;
                projectSelect.appendChild(opt);
                projectSelect.value = res.project_id;
            }

            closeOverlay();
        } catch (err) {
            showError(errorBox, "Project creation failed.");
            if (confirmBtn) confirmBtn.disabled = false;
        }
    };

    const wireOverlayInteractions = () => {
        const nameInput = document.getElementById("new-project-name");
        const cancelBtn = document.getElementById("add-project-cancel-btn");
        const confirmBtn = document.getElementById("add-project-confirm-btn");
        const errorBox = document.getElementById("add-project-error");

        cancelBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            closeOverlay();
        });

        confirmBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            doCreate({ nameInput, confirmBtn, errorBox });
        });

        nameInput?.addEventListener("keydown", (e) => {
            if (e.key === "Enter") doCreate({ nameInput, confirmBtn, errorBox });
        });

        nameInput?.focus();
    };

    // -------------------------------------------------------------------------
    // Event binding
    // -------------------------------------------------------------------------
    addBtn.addEventListener("click", () => {
        openOverlay("Add Project", `
            <div style="display:flex; flex-direction:column; gap:10px;">
                <label style="font-weight:600;">Project name</label>
                <input id="new-project-name" type="text" placeholder="e.g. Summer Events"
                       style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:6px;">
                    <button id="add-project-cancel-btn"
                            style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                        Cancel
                    </button>
                    <button id="add-project-confirm-btn"
                            style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                        Create
                    </button>
                </div>

                <div id="add-project-error" style="color:#c14a42; font-size:0.95em; display:none;"></div>
            </div>
        `);

        wireOverlayInteractions();
    });
}

/**
 * Initialize all interactions related to the "Add Entity" overlay.
 *
 * This function is safe to call multiple times; listeners will only be
 * attached once.
 */
function addAddEntityListener() {
    // -------------------------------------------------------------------------
    // Idempotency guard
    // -------------------------------------------------------------------------
    if (addAddEntityListener._isBound) return;
    addAddEntityListener._isBound = true;

    // -------------------------------------------------------------------------
    // Private helpers (NOT exposed outside this function)
    // -------------------------------------------------------------------------

    /**
     * Open the overlay with a given title and HTML content.
     *
     * @param {string} title - Overlay title text.
     * @param {string} html  - HTML content injected into the overlay body.
     */
    function openOverlay(title, html) {
        const overlay = document.getElementById("add-entity-overlay");
        const titleEl = document.getElementById("add-entity-title");
        const content = document.getElementById("add-entity-content");

        if (!overlay || !titleEl || !content) return;

        titleEl.textContent = title || "Add";
        content.innerHTML = html || "";

        overlay.style.display = "block";
    }

    /**
     * Close the overlay and reset its content.
     */
    function closeOverlay() {
        const overlay = document.getElementById("add-entity-overlay");
        const content = document.getElementById("add-entity-content");

        if (!overlay || !content) return;

        overlay.style.display = "none";
        content.innerHTML = "";
    }

    // -------------------------------------------------------------------------
    // Event bindings
    // -------------------------------------------------------------------------

    // 1) Click on overlay background or any element explicitly marked to close it
    document.addEventListener("click", (e) => {
        const target = e.target;

        if (target?.getAttribute?.("data-overlay-close") === "1") {
            closeOverlay();
        }
    });

    // 2) Close button
    document
        .getElementById("add-entity-close-btn")
        ?.addEventListener("click", closeOverlay);

    // 3) Escape key
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeOverlay();
    });

    // 4) "Add Entity" button → open overlay based on current view
    document
        .getElementById("add-entity-btn")
        ?.addEventListener("click", () => {
            const view =
                document.getElementById("main-view-select")?.value ||
                "Events List";

            if (view === "Events List") {
                openOverlay("Add Event", `<div>…event form…</div>`);
            } else if (view === "Artists List") {
                openOverlay("Add Artist", `<div>…artist form…</div>`);
            }
        });
}

// ----------------------------------------------------------------------------
// renderTable()  (Events only for now)
// ----------------------------------------------------------------------------
// Assumes globals:
// - window.events = [ { id, name, status, ... }, ... ]
// - activeFilters = ["inactive","upcoming","active","ended","hold"] (lowercase)
// - sortKey, sortDirection ("asc"|"desc")
// - selectedEvent (or null)
// - selectEvent(eventObj) exists (you can rename later)
// ----------------------------------------------------------------------------

function renderTable() {

    // ---------------------------
    // 1) View + data source
    // ---------------------------
    // For now: only Events.
    const view = "events";
    const data = Array.isArray(window.events) ? window.events : [];

    // ---------------------------
    // 2) Columns configuration
    // ---------------------------
    // Each column can define:
    // - key: unique key (used for sorting)
    // - label: header text
    // - width: optional CSS width
    // - sortable: boolean (default true)
    // - optional: boolean (default false)
    // - render: (row) => string (or set td.innerHTML if you prefer)
    // - sortValue: (row) => string (optional; defaults to row[key])
    const columnsByView = {
        events: [
            {
                key: "_row",
                label: "",
                width: "36px",
                sortable: false,
                render: (ev) => "", // handled as special case (checkbox)
            },
            {
                key: "name",
                label: "Event",
                render: (ev) => String(ev.name ?? ""),
                sortValue: (ev) => String(ev.name ?? ""),
            },
            {
                key: "status",
                label: "Status",
                optional: true,
                render: (ev) => String(ev.status ?? ""),
                sortValue: (ev) => String(ev.status ?? ""),
            },
            // Add more later:
            // { key:"start_date", label:"Start", render:(ev)=>formatDate(ev.start_date), sortValue:(ev)=>ev.start_date||"" },
        ],
    };

    const baseColumns = columnsByView[view] || [];
    const visibleCols = baseColumns; // optional columns later

    // ---------------------------
    // 3) Table targets (Events table)
    // ---------------------------
    const table = document.querySelector(".events-table");
    if (!table) return;

    const thead = table.querySelector("thead");
    const tbody = document.getElementById("events-tbody");
    if (!thead || !tbody) return;

    // Clear previous
    thead.innerHTML = "";
    tbody.innerHTML = "";

    // ---------------------------
    // 4) Build THEAD (sortable)
    // ---------------------------
    const theadRow = document.createElement("tr");
    thead.appendChild(theadRow);

    visibleCols.forEach((col) => {
        const th = document.createElement("th");
        th.dataset.key = col.key;
        if (col.width) th.style.width = col.width;

        let label = col.label || "";

        if (col.key === sortKey) {
            const arrow = sortDirection === "asc" ? "▲ " : "▼ ";
            label = arrow + label;
        }

        th.textContent = label;

        const sortable = (col.sortable !== false) && col.key !== "_row";
        if (sortable) {
            th.style.cursor = "pointer";
            th.addEventListener("click", async () => {
                if (sortKey === col.key) {
                    sortDirection = (sortDirection === "asc") ? "desc" : "asc";
                } else {
                    sortKey = col.key;
                    sortDirection = "asc";
                }

                // Persist sorting preferences (optional, keep your current logic)
                try {
                    const payload = JSON.stringify({ sort_key: sortKey, sort_dir: sortDirection });
                    const appId = await AFGetAppID();
                    await AFSendFlowMessage(appId, "Global", `SETPREFERENCES|${payload}`);
                } catch (e) {
                    console.warn("Failed to persist sorting prefs", e);
                }

                renderTable();
            });
        }

        theadRow.appendChild(th);
    });

    // ---------------------------
    // 5) Filter + sort
    // ---------------------------
    const filtered = data.filter(ev => activeFilters.includes((ev.status || "").toLowerCase()));

    const colMap = new Map(visibleCols.map(c => [c.key, c]));
    const getSortValue = (row) => {
        const col = colMap.get(sortKey);
        if (!col) return "";
        if (typeof col.sortValue === "function") return String(col.sortValue(row) ?? "");
        return String(row[sortKey] ?? "");
    };

    filtered.sort((a, b) => {
        const sa = getSortValue(a);
        const sb = getSortValue(b);
        const cmp = sa.localeCompare(sb, undefined, { numeric: true, sensitivity: "base" });
        return sortDirection === "asc" ? cmp : -cmp;
    });

    // ---------------------------
    // 6) Build TBODY
    // ---------------------------
    filtered.forEach((ev) => {
        const row = document.createElement("tr");

        visibleCols.forEach((col) => {
            const td = document.createElement("td");

            // Special checkbox column
            if (col.key === "_row") {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.classList.add("row-checkbox");
                checkbox.dataset.id = ev.id;

                checkbox.addEventListener("click", (e) => e.stopPropagation());
                // checkbox.addEventListener("change", updateButtonsIfAny);

                td.appendChild(checkbox);
                row.appendChild(td);
                return;
            }

            // Special status rendering
            if (col.key === "status") {
                const status = (ev.status || "").toLowerCase();
                const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : "";
                td.innerHTML = `<span class="status-${status}">${label}</span>`;
                row.appendChild(td);
                return;
            }

            // Default cell rendering via column.render
            const text = (typeof col.render === "function") ? col.render(ev) : String(ev[col.key] ?? "");
            td.textContent = String(text ?? "");
            row.appendChild(td);
        });

        // Row click
        row.style.cursor = "pointer";
        row.onclick = () => selectEvent(ev);

        // Highlight selected
        if (window.selectedEvent && window.selectedEvent.id === ev.id) {
            row.style.background = "#ffecf5";
        }

        tbody.appendChild(row);
    });

    // No "new row / add painting" part for Events for now.
}

// Load preferences from backend and apply to UI state
async function loadPreferences() 
{
  
    try {
        
        // Request preferences from backend
        const appId = await AFGetAppID();
        const ownerId = await AFGetOwnerID();
        const raw = await AFSendFlowMessage(appId, ownerId, "GETPREFERENCES");
        const prefs = raw ? (typeof raw === "string" ? JSON.parse(raw) : raw) : {};
        window.preferences = (prefs && typeof prefs === "object") ? prefs : {};
        
    } catch (e) {
        
        console.log("GETPREFERENCES failed; using empty preferences.", e);
  
    }

}

// -----------------------------------------------------------------------------
// Preferences Save (batched + debounced)
// -----------------------------------------------------------------------------
//
// Usage examples:
//   savePreferences("sort_key", "name");
//   savePreferences("sort_key", "name", "sort_dir", "asc");
//   savePreferences("active_columns", ["name", "status"]);
//
// - Updates window.preferences immediately
// - Batches multiple calls within 1 second
// - Sends a single SETPREFERENCES with merged patch
// -----------------------------------------------------------------------------

let _prefsTimer = null;
let _prefsPatch = {};

async function savePreferences(...args) {

    // Must be key/value pairs
    if (args.length % 2 !== 0) {
        console.warn("savePreferences requires key/value pairs.");
        return;
    }

    if (!window.preferences) {
        window.preferences = {};
    }

    // Build patch from arguments
    const patch = {};
    for (let i = 0; i < args.length; i += 2) {
        const key = args[i];
        const value = args[i + 1];

        if (typeof key !== "string") continue;

        // Update local state immediately
        window.preferences[key] = value;

        patch[key] = value;
    }

    // Merge into global pending patch
    _prefsPatch = { ..._prefsPatch, ...patch };

    // Reset debounce timer
    if (_prefsTimer) clearTimeout(_prefsTimer);

    _prefsTimer = setTimeout(async () => {

        const toSend = { ..._prefsPatch };
        _prefsPatch = {};
        _prefsTimer = null;

        if (Object.keys(toSend).length === 0) return;

        try {
            const appId = await AFGetAppID();
            const ownerId = await AFGetOwnerID();
            await AFSendFlowMessage(
                appId,
                ownerId,
                `SETPREFERENCES|${JSON.stringify(toSend)}`
            );
        } catch (e) {
            console.warn("Failed to save preferences", e);
        }

    }, 1000); // 1 second debounce
}

// -----------------------------------------------------------------------------
// initializeDateFilters (Month dropdown + Year stepper)
// -----------------------------------------------------------------------------
// Defaults:
//   - Month = current month
//   - Year  = current year
//
// Still calls savePreferences(...) on change (as you already wired it),
// but does NOT read window.preferences for defaults.
// -----------------------------------------------------------------------------

function initializeDateFilters() {

    const monthSelect = document.getElementById("filter-month");
    const yearInput   = document.getElementById("filter-year");
    const yearDec     = document.getElementById("year-dec");
    const yearInc     = document.getElementById("year-inc");

    if (!monthSelect || !yearInput || !yearDec || !yearInc) return;

    // English month names
    const months = [
        "January","February","March","April","May","June",
        "July","August","September","October","November","December"
    ];

    // Avoid duplicating options if called twice
    if (!monthSelect.dataset.populated) {

        // Optional: keep "All Months" if you kept it in HTML
        // If you don't want "All Months", remove that option in HTML.
        // Here we ensure it exists only once.
        // if (monthSelect.options.length === 0) {
        //     const allOpt = document.createElement("option");
        //     allOpt.value = "";
        //     allOpt.textContent = "All Months";
        //     monthSelect.appendChild(allOpt);
        // }

        for (let i = 0; i < 12; i++) {
            const opt = document.createElement("option");
            opt.value = String(i + 1).padStart(2, "0"); // "01".."12"
            opt.textContent = months[i];
            monthSelect.appendChild(opt);
        }

        monthSelect.dataset.populated = "1";
    }

    // Current date defaults (local)
    const now = new Date();
    const currentYear  = now.getFullYear();
    const currentMonth = String(now.getMonth() + 1).padStart(2, "0");

    // Set defaults (month + year current)
    monthSelect.value = currentMonth;
    yearInput.value = String(currentYear);

    // Optionally persist defaults once (harmless)
    // if (typeof savePreferences === "function") {
    //     savePreferences("filter_month", currentMonth, "filter_year", String(currentYear));
    // }

    // Helpers
    const clampYear = (y) => {
        if (!Number.isFinite(y)) return currentYear;
        if (y < 1970) return 1970;
        if (y > 2100) return 2100;
        return y;
    };

    const setYear = (y) => {
        const yy = String(clampYear(parseInt(y, 10)));
        yearInput.value = yy;

        // if (typeof savePreferences === "function") {
        //     savePreferences("filter_year", yy);
        // }
        if (typeof renderTable === "function") {
            renderTable();
        }
    };

    // Month change
    monthSelect.addEventListener("change", () => {
        // if (typeof savePreferences === "function") {
        //     savePreferences("filter_month", monthSelect.value);
        // }
        if (typeof renderTable === "function") {
            renderTable();
        }
    });

    // Stepper buttons
    yearDec.addEventListener("click", () => {
        const y = clampYear(parseInt(yearInput.value || String(currentYear), 10));
        setYear(y - 1);
    });

    yearInc.addEventListener("click", () => {
        const y = clampYear(parseInt(yearInput.value || String(currentYear), 10));
        setYear(y + 1);
    });

    // Manual typing
    yearInput.addEventListener("input", () => {
        yearInput.value = (yearInput.value || "").replace(/[^\d]/g, "").slice(0, 4);
    });

    yearInput.addEventListener("blur", () => {
        const v = yearInput.value;
        if (!/^\d{4}$/.test(v)) {
            setYear(currentYear);
        } else {
            setYear(v);
        }
    });

    yearInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") yearInput.blur();

        if (e.key === "ArrowUp") {
            e.preventDefault();
            yearInc.click();
        }
        if (e.key === "ArrowDown") {
            e.preventDefault();
            yearDec.click();
        }
    });
}