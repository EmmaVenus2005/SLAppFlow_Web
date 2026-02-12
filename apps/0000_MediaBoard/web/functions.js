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
// renderTable()
// ----------------------------------------------------------------------------
// - Renders the active view table (Events for now, but supports other views).
// - Builds the columns menu (☰) from columns marked as optional:true.
// - Shows/hides optional columns based on preferences + menu checkboxes.
// - Persists visible optional columns in preferences via savePreferences().
//
// Expected globals / functions:
// - window.events (and later window.artists, window.venues, window.boards)
// - activeFilters (array of status strings) OR you can ignore filters per view
// - sortKey, sortDirection ("asc"|"desc")
// - window.selectedEvent (optional) + selectEvent(ev) (optional)
// - savePreferences(...pairs)  -> sends SETPREFERENCES with partial payload
// - renderTable() can be called again safely
//
// Stored preference keys (per view):
// - visible_columns_events: ["status", ...]
// - visible_columns_artists: [...]
// - visible_columns_venues: [...]
// - visible_columns_boards: [...]
// ----------------------------------------------------------------------------

function renderTable() {

    // ------------------------------------------------------------------------
    // 1) Resolve current view
    // ------------------------------------------------------------------------
    const viewLabel = document.getElementById("main-view-select")?.value || "Events List";
    const view = (viewLabel.split(" ")[0] || "Events").toLowerCase(); // "events","artists","venues","boards"

    // ------------------------------------------------------------------------
    // 2) Data sources (extend later)
    // ------------------------------------------------------------------------
    const dataByView = {
        events:  Array.isArray(window.events)  ? window.events  : [],
        artists: Array.isArray(window.artists) ? window.artists : [],
        venues:  Array.isArray(window.venues)  ? window.venues  : [],
        boards:  Array.isArray(window.boards)  ? window.boards  : [],
    };

    const data = dataByView[view] || [];

    // ------------------------------------------------------------------------
    // 3) Columns configuration (base + optional)
    // ------------------------------------------------------------------------
    // - optional columns are toggled from the ☰ menu.
    // - base columns are always visible (optional:false or missing).
    const columnsByView = {
        events: [
            { key: "_row", label: "", width: "36px", sortable: false, render: () => "" },

            // Event name
            {
                key: "name",
                label: "Event",
                render: (ev) => String(ev.name ?? ""),
                sortValue: (ev) => String(ev.name ?? "")
            },

            // Artist name (ev.artist = uuid)
            {
                key: "artist",
                label: "Artist",
                optional: true,
                render: (ev) => {
                    const id = ev.artist;
                    const name = window.artistsById?.[id]?.name;
                    return name ? String(name) : (id ? "(unknown artist)" : "");
                },
                sortValue: (ev) => String(window.artistsById?.[ev.artist]?.name ?? "")
            },

            // Venue name (ev.artist = uuid)
            {
                key: "venue",
                label: "Venue",
                optional: true,
                render: (ev) => {
                    const id = ev.artist;
                    const name = window.venuesById?.[id]?.name;
                    return name ? String(name) : (id ? "(unknown venue)" : "");
                },
                sortValue: (ev) => String(window.venuesById?.[ev.venues]?.name ?? "")
            },

            // Date (expects ev.date = "YYYY-MM-DD")
            {
                key: "date",
                label: "Date",
                optional: true,
                render: (ev) => String(ev.date ?? ""),
                sortValue: (ev) => String(ev.date ?? "")
            },

            // Start time (expects ev.start_time = "HH:MM" or "HH:MM:SS")
            {
                key: "start_time",
                label: "Start Time",
                optional: true,
                render: (ev) => String(ev.start_time ?? ""),
                sortValue: (ev) => String(ev.start_time ?? "")
            },

            // End time (expects ev.end_time = "HH:MM" or "HH:MM:SS")
            {
                key: "end_time",
                label: "End Time",
                optional: true,
                render: (ev) => String(ev.end_time ?? ""),
                sortValue: (ev) => String(ev.end_time ?? "")
            },

            // Status badge (optional toggle via ☰ menu)
            {
                key: "status",
                label: "Status",
                optional: true,
                render: (ev) => String(ev.status ?? ""),
                sortValue: (ev) => String(ev.status ?? "")
            }
        ],
        artists: [
            { key: "_row", label: "", width: "36px", sortable: false, render: () => "" },
            { key: "name", label: "Artist", render: (a) => String(a.name ?? ""), sortValue: (a) => String(a.name ?? "") },
            // add optional columns later
        ],
        venues: [
            { key: "_row", label: "", width: "36px", sortable: false, render: () => "" },
            { key: "name", label: "Venue", render: (v) => String(v.name ?? ""), sortValue: (v) => String(v.name ?? "") },
        ],
        boards: [
            { key: "_row", label: "", width: "36px", sortable: false, render: () => "" },
            { key: "name", label: "Board", render: (b) => String(b.name ?? ""), sortValue: (b) => String(b.name ?? "") },
        ],
    };

    const allCols = columnsByView[view] || [];
    const baseCols = allCols.filter(c => !c.optional);
    const optCols  = allCols.filter(c => c.optional);

    // ------------------------------------------------------------------------
    // 4) Read visible optional columns from preferences (per view)
    // ------------------------------------------------------------------------
    const prefKey = `visible_columns_${view}`;
    const prefList = (window.preferences && Array.isArray(window.preferences[prefKey]))
        ? window.preferences[prefKey]
        : [];

    // Visible columns = base + (optional keys present in prefs)
    const visibleCols = [
        ...baseCols,
        ...optCols.filter(c => prefList.includes(c.key)),
    ];

    // ------------------------------------------------------------------------
    // 5) Show only the active table (hide others)
    // ------------------------------------------------------------------------
    const tableByView = {
        events:  { tableSel: ".events-table",  tbodyId: "events-tbody"  },
        artists: { tableSel: ".artists-table", tbodyId: "artists-tbody" },
        venues:  { tableSel: ".venues-table",  tbodyId: "venues-tbody"  },
        boards:  { tableSel: ".boards-table",  tbodyId: "boards-tbody"  },
    };

    // Hide all tables, then show the active one
    Object.values(tableByView).forEach(({ tableSel }) => {
        const t = document.querySelector(tableSel);
        if (t) t.style.display = "none";
    });

    const activeMeta = tableByView[view];
    const table = activeMeta ? document.querySelector(activeMeta.tableSel) : null;
    const tbody = activeMeta ? document.getElementById(activeMeta.tbodyId) : null;
    const thead = table ? table.querySelector("thead") : null;

    if (!table || !thead || !tbody) return;
    table.style.display = "";

    // ------------------------------------------------------------------------
    // 6) Build / wire the optional columns menu (☰)
    // ------------------------------------------------------------------------
    // One handler that re-renders + saves prefs when checkboxes change.
    (function buildOptionalColumnsMenu() {

        const container = document.getElementById("column-options");
        const btn  = document.getElementById("column-options-btn");
        const menu = document.getElementById("column-options-menu");

        if (!container || !btn || !menu) return;

        // If no optional columns for this view, hide the menu button.
        if (optCols.length === 0) {
            container.style.display = "none";
            return;
        }

        container.style.display = "";

        // Build menu HTML
        menu.innerHTML = optCols.map(c => {
            const checked = prefList.includes(c.key);
            return `
                <label class="column-option-item">
                    <input type="checkbox" value="${c.key}" ${checked ? "checked" : ""}>
                    <span>${c.label || c.key}</span>
                </label>
            `;
        }).join("");

        // Bind open/close once
        if (!container.dataset.bound) {
            container.dataset.bound = "1";

            btn.addEventListener("click", (e) => {
                e.stopPropagation();
                container.classList.toggle("open");
            });

            menu.addEventListener("click", (e) => e.stopPropagation());
            document.addEventListener("click", () => container.classList.remove("open"));
            document.addEventListener("keydown", (e) => { if (e.key === "Escape") container.classList.remove("open"); });
        }

        // Bind change (rebound each render because menu HTML is rebuilt)
        menu.onchange = () => {
            const selectedKeys = Array.from(menu.querySelectorAll('input[type="checkbox"]:checked'))
                .map(i => i.value);

            // Keep local prefs cache in sync
            if (!window.preferences || typeof window.preferences !== "object") window.preferences = {};
            window.preferences[prefKey] = selectedKeys;

            // Persist only the key for this view
            if (typeof savePreferences === "function") {
                savePreferences(prefKey, selectedKeys);
            }

            // Re-render table with new columns
            renderTable();
        };

    })();

    // ------------------------------------------------------------------------
    // 7) Build THEAD (sortable)
    // ------------------------------------------------------------------------
    thead.innerHTML = "";
    const theadRow = document.createElement("tr");
    thead.appendChild(theadRow);

    // Map for sorting
    const colMap = new Map(visibleCols.map(c => [c.key, c]));

    visibleCols.forEach((col) => {
        const th = document.createElement("th");
        th.dataset.key = col.key;
        if (col.width) th.style.width = col.width;

        let label = col.label || "";

        if (col.key === sortKey) {
            label = (sortDirection === "asc" ? "▲ " : "▼ ") + label;
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

                // Persist sorting preferences (optional)
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

    // ------------------------------------------------------------------------
    // 8) Filter + sort (keep it minimal; status filtering mainly for events)
    // ------------------------------------------------------------------------
    let filtered = data;

    // If you keep "activeFilters", apply it for rows that have a status
    if (Array.isArray(window.activeFilters) || Array.isArray(activeFilters)) {
        const filters = Array.isArray(activeFilters) ? activeFilters : window.activeFilters;
        filtered = data.filter(r => {
            const s = (r.status || "").toLowerCase();
            return !s || filters.includes(s);
        });
    }

    const getSortValue = (row) => {
        const col = colMap.get(sortKey);
        if (!col) return "";
        if (typeof col.sortValue === "function") return String(col.sortValue(row) ?? "");
        return String(row[sortKey] ?? "");
    };

    filtered = [...filtered].sort((a, b) => {
        const sa = getSortValue(a);
        const sb = getSortValue(b);
        const cmp = sa.localeCompare(sb, undefined, { numeric: true, sensitivity: "base" });
        return sortDirection === "asc" ? cmp : -cmp;
    });

    // ------------------------------------------------------------------------
    // 9) Build TBODY
    // ------------------------------------------------------------------------
    tbody.innerHTML = "";

    filtered.forEach((rowObj) => {
        const tr = document.createElement("tr");

        visibleCols.forEach((col) => {
            const td = document.createElement("td");

            // Checkbox column
            if (col.key === "_row") {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.classList.add("row-checkbox");
                checkbox.dataset.id = rowObj.id || "";

                checkbox.addEventListener("click", (e) => e.stopPropagation());
                td.appendChild(checkbox);
                tr.appendChild(td);
                return;
            }

            // Status badge rendering (if status exists)
            if (col.key === "status") {
                const status = (rowObj.status || "").toLowerCase();
                const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : "";
                td.innerHTML = `<span class="status-${status}">${label}</span>`;
                tr.appendChild(td);
                return;
            }

            // Default rendering (via column.render if provided)
            const text = (typeof col.render === "function") ? col.render(rowObj) : String(rowObj[col.key] ?? "");
            td.textContent = String(text ?? "");
            tr.appendChild(td);
        });

        // Row click (events only for now; keep safe if handler missing)
        tr.style.cursor = "pointer";
        tr.onclick = () => {
            if (view === "events" && typeof selectEvent === "function") {
                selectEvent(rowObj);
            }
        };

        // Highlight selection (events only)
        if (view === "events" && window.selectedEvent && window.selectedEvent.id === rowObj.id) {
            tr.style.background = "#ffecf5";
        }

        tbody.appendChild(tr);
    });
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

// -----------------------------------------------------------------------------
// loadProjects()
// -----------------------------------------------------------------------------
// - Calls backend command "LIST_PROJECTS"
// - Stores result into window.projects
// - Logs content to console (debug only)
//
// Expected backend response:
//   [
//     { project_id: "...", name: "..." },
//     ...
//   ]
//
// Requires:
//   - AFGetAppID()
//   - AFGetOwnerID()
//   - AFSendFlowMessage()
// -----------------------------------------------------------------------------

async function loadProjects() {

    try {

        const appId   = await AFGetAppID();
        const ownerId = await AFGetOwnerID();

        const res = await AFSendFlowMessage(
            appId,
            ownerId,
            "LIST_PROJECTS"
        );

        // Normalize result
        if (Array.isArray(res)) {
            window.projects = res;
        } else if (typeof res === "string") {
            try {
                const parsed = JSON.parse(res);
                window.projects = Array.isArray(parsed) ? parsed : [];
            } catch {
                window.projects = [];
            }
        } else {
            window.projects = [];
        }

        // Debug output
        console.log("Projects loaded:", window.projects);

        // Adds each entry as an option to <select id="project-list">
        const projectSelect = document.getElementById("project-list");
        if (projectSelect) {
            projectSelect.innerHTML = ""; // Clear existing options

            window.projects.forEach(proj => {
                const opt = document.createElement("option");
                opt.value = proj.project_id || "";
                opt.textContent = proj.owner_name + " - " + proj.name;
                projectSelect.appendChild(opt);
            });
        }

    } catch (err) {

        console.error("Failed to load projects:", err);
        window.projects = [];

    }

}

/**
 * addTrusted
 *
 * Adds a "Trusted user" UI next to the Projects "+" button:
 * - Opens the existing overlay
 * - Lets you pick ONE project you own (from window.projects)
 * - Lets you paste a trusted user's UUID
 * - Sends: ADD_TRUSTED|<trusted_uuid>|<project_id>
 *
 * Requirements:
 * - Overlay DOM exists: #add-entity-overlay, #add-entity-title, #add-entity-content
 * - Backend command exists: ADD_TRUSTED|<trusted_user_id>|<project_id>
 * - SLAppFlow funcs exist: AFGetAppID(), AFGetOwnerID(), AFSendFlowMessage()
 * - window.projects is already loaded (LIST_PROJECTS); we filter by p.is_owner === true if present,
 *   otherwise we assume all projects in window.projects are owned by you.
 *
 * @param {Object} opts
 * @param {string} [opts.buttonId="add-trusted-btn"] DOM id for the button
 */
function addTrusted(opts = {}) {

    const buttonId = opts.buttonId || "add-trusted-btn";
    const btn = document.getElementById(buttonId);
    if (!btn) return;

    // Prevent double-binding if called multiple times
    if (btn.dataset.listenerBound === "1") return;
    btn.dataset.listenerBound = "1";

    // -----------------------------
    // Overlay helpers (local only)
    // -----------------------------
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

    const showError = (el, msg) => {
        if (!el) return;
        el.textContent = msg;
        el.style.display = "block";
    };

    // -----------------------------
    // Projects helpers
    // -----------------------------
    const getOwnedProjects = () => {
        const list = Array.isArray(window.projects) ? window.projects : [];

        // If your objects later include an ownership flag, we honor it.
        // Otherwise, assume the list is yours (owner-only list).
        const owned = list.filter(p => {
            if (p && typeof p === "object" && "is_owner" in p) return !!p.is_owner;
            return true;
        });

        // Normalize to { project_id, name }
        return owned
            .map(p => ({
                project_id: (p.project_id || p.id || p.projectId || "").toString(),
                name: (p.name || p.project_name || p.projectName || p.project_id || p.id || "").toString()
            }))
            .filter(p => p.project_id);
    };

    // -----------------------------
    // Main create flow
    // -----------------------------
    const doAddTrusted = async ({ projectSelect, uuidInput, confirmBtn, errorBox }) => {

        const projectId = (projectSelect?.value || "").trim();
        const trustedId = (uuidInput?.value || "").trim();

        if (!projectId) {
            showError(errorBox, "Please select one of your projects.");
            return;
        }
        if (!trustedId) {
            showError(errorBox, "Please enter the trusted user's UUID.");
            return;
        }

        // Very light UUID sanity check (accepts SL UUID format)
        if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(trustedId)) {
            showError(errorBox, "Invalid UUID format.");
            return;
        }

        if (confirmBtn) confirmBtn.disabled = true;

        try {
            const appId = await AFGetAppID();
            const ownerId = await AFGetOwnerID();

            const msg = `ADD_TRUSTED|${trustedId}|${projectId}`;
            const res = await AFSendFlowMessage(appId, ownerId, msg);

            // Your backend returns "return;" (null) on success currently,
            // but could also return true. Treat non-false as success.
            if (res === false) {
                showError(errorBox, "Failed to add trusted user.");
                if (confirmBtn) confirmBtn.disabled = false;
                return;
            }

            closeOverlay();
        } catch (e) {
            showError(errorBox, "Failed to add trusted user.");
            if (confirmBtn) confirmBtn.disabled = false;
        }
    };

    // -----------------------------
    // Wire button
    // -----------------------------
    btn.addEventListener("click", () => {

        const owned = getOwnedProjects();

        openOverlay("Add Trusted User", `
            <div style="display:flex; flex-direction:column; gap:10px;">

                <label style="font-weight:600;">Project</label>
                <select id="trusted-project-select"
                        style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                    ${owned.length > 0
                        ? owned.map(p => `<option value="${p.project_id}">${escapeHtml(p.name)}</option>`).join("")
                        : `<option value="">(No owned projects found)</option>`
                    }
                </select>

                <label style="font-weight:600;">Trusted user UUID</label>
                <input id="trusted-user-uuid" type="text" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                       style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:6px;">
                    <button id="add-trusted-cancel-btn"
                            style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                        Cancel
                    </button>
                    <button id="add-trusted-confirm-btn"
                            style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;"
                            ${owned.length === 0 ? "disabled" : ""}>
                        Add
                    </button>
                </div>

                <div id="add-trusted-error" style="color:#c14a42; font-size:0.95em; display:none;"></div>
            </div>
        `);

        const projectSelect = document.getElementById("trusted-project-select");
        const uuidInput = document.getElementById("trusted-user-uuid");
        const cancelBtn = document.getElementById("add-trusted-cancel-btn");
        const confirmBtn = document.getElementById("add-trusted-confirm-btn");
        const errorBox = document.getElementById("add-trusted-error");

        cancelBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            closeOverlay();
        });

        confirmBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            doAddTrusted({ projectSelect, uuidInput, confirmBtn, errorBox });
        });

        uuidInput?.addEventListener("keydown", (e) => {
            if (e.key === "Enter") doAddTrusted({ projectSelect, uuidInput, confirmBtn, errorBox });
        });

        uuidInput?.focus();
    });

    // -----------------------------
    // Tiny HTML escape for safety
    // -----------------------------
    function escapeHtml(s) {
        return String(s ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }
}
