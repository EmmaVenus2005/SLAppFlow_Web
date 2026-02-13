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

function addAddEntityListener()
{

    /**
     * addAddEntityListener
     *
     * Purpose:
     * - Wires ALL interactions related to the "Add Entity" overlay.
     * - The overlay skeleton is assumed to ALWAYS exist in index.html:
     *     #add-entity-overlay, #add-entity-title, #add-entity-content,
     *     .overlay-backdrop[data-overlay-close="1"], #add-entity-close-btn
     *
     * Design:
     * - Single function only (no external utilities).
     * - Top: listeners (bound once).
     * - Then: openOverlay / closeOverlay helpers.
     * - Then: form templates by entity type (Events / Artists / Venues / Boards).
     * - Then: per-form wiring (validation, photo load in RAM, submit behavior).
     *
     * Project routing (IMPORTANT):
     * - The project is selected from <select id="project-list">.
     * - window.projects is expected to contain objects like:
     *     { project_id, name, owner, owner_name }
     * - For ADD_* commands, the message is sent to the PROJECT OWNER (proj.owner),
     *   not to the currently logged-in user.
     * - The selected project_id is included in the stride as the 1st argument:
     *     ADD_*|<project_uuid>|...
     *
     * Backend contract:
     * - Event:
     *     ADD_EVENT|<project_uuid>|<infos_json>|<base64_img>
     *     - <infos_json> is a JSON string (no base64 inside).
     *     - <base64_img> is raw base64 (no "data:image/...;base64," prefix). Can be empty.
     * - Artist:
     *     ADD_ARTIST|<project_uuid>|<infos_json>
     *     - <infos_json> includes: name, description
     * - Venue:
     *     ADD_VENUE|<project_uuid>|<infos_json>
     *     - <infos_json> includes: name, description
     *
     * Boards:
     * - Not handled here (no overlay form).
     * - The ➕ button is disabled when "Boards List" is selected.
     */

    // -------------------------------------------------------------------------
    // 0) Idempotency guard (prevents double-binding)
    // -------------------------------------------------------------------------
    if (addAddEntityListener._isBound) return;
    addAddEntityListener._isBound = true;

    // -------------------------------------------------------------------------
    // 1) DOM references (overlay skeleton must exist in index.html)
    // -------------------------------------------------------------------------
    const overlay  = document.getElementById("add-entity-overlay");
    const titleEl  = document.getElementById("add-entity-title");
    const contentEl = document.getElementById("add-entity-content");
    const addBtn   = document.getElementById("add-entity-btn");
    const closeBtn = document.getElementById("add-entity-close-btn");
    const viewSelect = document.getElementById("main-view-select");

    // If overlay is missing, do nothing (safe no-op)
    if (!overlay || !titleEl || !contentEl || !addBtn || !viewSelect) return;

    // -------------------------------------------------------------------------
    // 2) LISTENERS (global overlay interactions + view-based enabling)
    // -------------------------------------------------------------------------

    // 2.1) Close overlay when clicking backdrop (or any element marked data-overlay-close="1")
    document.addEventListener("click", (e) => {
        const t = e.target;
        if (t?.getAttribute?.("data-overlay-close") === "1") {
            closeOverlay();
        }
    });

    // 2.2) Close overlay with X button
    closeBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        closeOverlay();
    });

    // 2.3) Close overlay with Escape key
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeOverlay();
    });

    // 2.4) Disable ➕ button when Boards List is selected
    // - This improves UX: boards are not created here.
    // - Also closes the overlay if user switches to Boards while overlay is open.
    const syncAddButtonState = () => {
        const viewLabel = viewSelect.value || "Events List";
        const isBoards = (viewLabel === "Boards List");

        addBtn.disabled = isBoards;
        addBtn.style.opacity = isBoards ? "0.45" : "1";
        addBtn.style.cursor = isBoards ? "not-allowed" : "pointer";

        if (isBoards) closeOverlay();
    };

    viewSelect.addEventListener("change", () => {
        syncAddButtonState();
    });

    // Ensure correct state on init
    syncAddButtonState();

    // 2.5) Open overlay with the ➕ button (form depends on current main view)
    addBtn.addEventListener("click", async (e) => {
        e.preventDefault();

        // If disabled, do nothing (extra safety)
        if (addBtn.disabled) return;

        const viewLabel = viewSelect.value || "Events List";

        // Decide entity type based on the active table/view
        let entityType = "event";
        if (viewLabel === "Artists List") entityType = "artist";
        if (viewLabel === "Venues List")  entityType = "venue";
        if (viewLabel === "Boards List")  entityType = "board"; // should not happen due to disabled button

        // Render + wire the form for this entity type
        await renderAndWireForm(entityType);
    });

    // -------------------------------------------------------------------------
    // 3) Overlay helpers
    // -------------------------------------------------------------------------

    /**
     * openOverlay
     *
     * @param {string} title - Overlay title
     * @param {string} html  - Overlay body HTML
     */
    function openOverlay(title, html) {
        titleEl.textContent = title || "Add";
        contentEl.innerHTML = html || "";
        overlay.style.display = "block";
    }

    /**
     * closeOverlay
     *
     * - Hides overlay
     * - Clears content (so the next open is clean)
     */
    function closeOverlay() {
        overlay.style.display = "none";
        contentEl.innerHTML = "";
    }

    // -------------------------------------------------------------------------
    // 3.B) Project context (selected project_id + project owner uuid)
    // -------------------------------------------------------------------------
    function getSelectedProjectContext() {

        const projectSelect = document.getElementById("project-list");
        const projectId = (projectSelect?.value || "").trim();

        const list = Array.isArray(window.projects) ? window.projects : [];
        const proj = list.find(p => String(p?.project_id || "") === projectId) || null;

        const projectOwnerId = (proj && typeof proj === "object")
            ? String(proj.owner || "")
            : "";

        return { projectId, projectOwnerId };
    }

    // -------------------------------------------------------------------------
    // 4) Form HTML templates (one section per entity type)
    // -------------------------------------------------------------------------

    /**
     * formHtmlByType
     *
     * - Each function returns HTML string for the overlay content.
     * - Keep the HTML close to its wiring logic for readability.
     */
    const formHtmlByType = {

        // ---------------------------------------------------------------------
        // 4.A) EVENT form
        // ---------------------------------------------------------------------
        event: () => {
            const artists = Array.isArray(window.artists) ? window.artists : [];
            const venues  = Array.isArray(window.venues)  ? window.venues  : [];

            const artistOptions = artists.length
                ? [`<option value=""></option>`, ...artists.map(a => {
                    const id = String(a.id ?? a.artist_id ?? "");
                    const nm = String(a.name ?? "");
                    return `<option value="${escapeHtml(id)}">${escapeHtml(nm)}</option>`;
                })].join("")
                : `<option value="">(no artists yet)</option>`;

            const venueOptions = venues.length
                ? [`<option value=""></option>`, ...venues.map(v => {
                    const id = String(v.id ?? v.venue_id ?? "");
                    const nm = String(v.name ?? "");
                    return `<option value="${escapeHtml(id)}">${escapeHtml(nm)}</option>`;
                })].join("")
                : `<option value="">(no venues yet)</option>`;

            return `
                <div style="display:flex; flex-direction:column; gap:10px;">

                    <div>
                        <label style="font-weight:600;">Event name</label>
                        <input id="ae_event_name" type="text" placeholder="e.g. DJ Madison Live"
                            style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                    </div>

                    <div>
                        <label style="font-weight:600;">Artist</label>
                        <select id="ae_event_artist"
                            style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                            ${artistOptions}
                        </select>
                    </div>

                    <div>
                        <label style="font-weight:600;">Venue</label>
                        <select id="ae_event_venue"
                            style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                            ${venueOptions}
                        </select>
                    </div>

                    <div style="display:flex; gap:10px;">
                        <div style="flex:1;">
                            <label style="font-weight:600;">Date</label>
                            <input id="ae_event_date" type="date"
                                style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                        </div>
                        <div style="flex:1;">
                            <label style="font-weight:600;">Start time</label>
                            <input id="ae_event_start" type="time"
                                style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                        </div>
                        <div style="flex:1;">
                            <label style="font-weight:600;">End time</label>
                            <input id="ae_event_end" type="time"
                                style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                        </div>
                    </div>

                    <div>
                        <label style="font-weight:600;">Photo</label>
                        <input id="ae_event_photo" type="file" accept="image/*"
                            style="width:100%; padding:6px 0;">
                        <div id="ae_event_photo_hint" style="color:#666; font-size:0.95em;">
                            No photo loaded.
                        </div>
                    </div>

                    <div id="ae_event_error" style="color:#c14a42; font-size:0.95em; display:none;"></div>

                    <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:6px;">
                        <button id="ae_event_cancel"
                            style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                            Cancel
                        </button>
                        <button id="ae_event_add"
                            style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                            Add
                        </button>
                    </div>
                </div>
            `;
        },

        // ---------------------------------------------------------------------
        // 4.B) ARTIST form
        // Fields:
        // - name
        // - description
        // Submit:
        // - ADD_ARTIST|<infos_json>
        // ---------------------------------------------------------------------
        artist: () => `
            <div style="display:flex; flex-direction:column; gap:10px;">

                <div>
                    <label style="font-weight:600;">Artist name</label>
                    <input id="ae_artist_name" type="text" placeholder="e.g. Madison Star"
                        style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                </div>

                <div>
                    <label style="font-weight:600;">Description</label>
                    <textarea id="ae_artist_desc" rows="4" placeholder="Short bio / notes"
                        style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px; resize:vertical;"></textarea>
                </div>

                <div id="ae_artist_error" style="color:#c14a42; font-size:0.95em; display:none;"></div>

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:6px;">
                    <button id="ae_artist_cancel"
                        style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                        Cancel
                    </button>
                    <button id="ae_artist_add"
                        style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                        Add
                    </button>
                </div>

            </div>
        `,

        // ---------------------------------------------------------------------
        // 4.C) VENUE form
        // Fields:
        // - name
        // - description
        // Submit:
        // - ADD_VENUE|<infos_json>
        // ---------------------------------------------------------------------
        venue: () => `
            <div style="display:flex; flex-direction:column; gap:10px;">

                <div>
                    <label style="font-weight:600;">Venue name</label>
                    <input id="ae_venue_name" type="text" placeholder="e.g. Starlit Night Club"
                        style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px;">
                </div>

                <div>
                    <label style="font-weight:600;">Description</label>
                    <textarea id="ae_venue_desc" rows="4" placeholder="Short description / location notes"
                        style="width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:8px; resize:vertical;"></textarea>
                </div>

                <div id="ae_venue_error" style="color:#c14a42; font-size:0.95em; display:none;"></div>

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:6px;">
                    <button id="ae_venue_cancel"
                        style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                        Cancel
                    </button>
                    <button id="ae_venue_add"
                        style="padding:6px 10px; border:1px solid #ddd; background:#fff; border-radius:8px; cursor:pointer;">
                        Add
                    </button>
                </div>

            </div>
        `,

        // ---------------------------------------------------------------------
        // 4.D) BOARD form (not handled here)
        // ---------------------------------------------------------------------
        board: () => `
            <div style="color:#666;">
                Boards are not created here.
            </div>
        `,
    };

    // -------------------------------------------------------------------------
    // 5) Render + wire logic (one block that routes by entity type)
    // -------------------------------------------------------------------------

    /**
     * renderAndWireForm
     *
     * - Injects the correct HTML for the entity type
     * - Attaches per-form listeners (submit, cancel, photo load, etc.)
     *
     * @param {"event"|"artist"|"venue"|"board"} entityType
     */
    async function renderAndWireForm(entityType) {

        // 5.1) Pick HTML builder
        const htmlBuilder = formHtmlByType[entityType];
        const html = (typeof htmlBuilder === "function")
            ? htmlBuilder()
            : `<div style="color:#666;">Unknown form.</div>`;

        // 5.2) Open overlay with title
        const titles = {
            event: "Add Event",
            artist: "Add Artist",
            venue: "Add Venue",
            board: "Add",
        };
        openOverlay(titles[entityType] || "Add", html);

        // 5.3) Wire the form according to entity type
        if (entityType === "event")  wireEventForm();
        if (entityType === "artist") wireArtistForm();
        if (entityType === "venue")  wireVenueForm();
        // Boards: no wiring
    }

    // -------------------------------------------------------------------------
    // 6) EVENT form wiring (validation + photo RAM + backend call)
    // -------------------------------------------------------------------------

    function wireEventForm() {

        const nameEl   = document.getElementById("ae_event_name");
        const artistEl = document.getElementById("ae_event_artist");
        const venueEl  = document.getElementById("ae_event_venue");
        const dateEl   = document.getElementById("ae_event_date");
        const startEl  = document.getElementById("ae_event_start");
        const endEl    = document.getElementById("ae_event_end");

        const photoEl  = document.getElementById("ae_event_photo");
        const hintEl   = document.getElementById("ae_event_photo_hint");

        const errEl    = document.getElementById("ae_event_error");
        const cancelBtn = document.getElementById("ae_event_cancel");
        const addBtn    = document.getElementById("ae_event_add");

        let photoBase64 = "";

        const showError = (msg) => {
            if (!errEl) return;
            errEl.textContent = msg || "Error";
            errEl.style.display = "block";
        };
        const hideError = () => {
            if (!errEl) return;
            errEl.textContent = "";
            errEl.style.display = "none";
        };

        cancelBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            closeOverlay();
        });

        photoEl?.addEventListener("change", async () => {
            hideError();

            const file = photoEl.files?.[0];
            if (!file) {
                photoBase64 = "";
                if (hintEl) hintEl.textContent = "No photo loaded.";
                return;
            }

            try {
                const dataUrl = await readFileAsDataURL(file);
                const idx = dataUrl.indexOf(",");
                photoBase64 = (idx >= 0) ? dataUrl.slice(idx + 1) : "";

                if (hintEl) {
                    hintEl.textContent = `Loaded: ${file.name} (${Math.round(file.size / 1024)} KB)`;
                }
            } catch (ex) {
                console.warn("Photo read failed", ex);
                photoBase64 = "";
                if (hintEl) hintEl.textContent = "No photo loaded.";
                showError("Failed to load photo.");
            }
        });

        addBtn?.addEventListener("click", async (e) => {
            e.preventDefault();
            hideError();

            const payload = {
                name: (nameEl?.value || "").trim(),
                artist_id: (artistEl?.value || "").trim(),
                venue_id: (venueEl?.value || "").trim(),
                date: (dateEl?.value || "").trim(),
                start_time: (startEl?.value || "").trim(),
                end_time: (endEl?.value || "").trim(),
            };

            if (!payload.name) { showError("Please enter an event name."); return; }
            if (!payload.date) { showError("Please select a date."); return; }

            // Resolve project_id + destination owner (project owner)
            const { projectId, projectOwnerId } = getSelectedProjectContext();
            if (!projectId) { showError("Please select a project first."); return; }
            if (!projectOwnerId) { showError("Project owner is missing for this project."); return; }

            if (addBtn) addBtn.disabled = true;

            try {
                const appId = await AFGetAppID();

                const safePayload = {
                    ...payload,
                    name: payload.name.replaceAll("|", " "),
                };

                const infosJson = JSON.stringify(safePayload);

                // UPDATED stride:
                // ADD_EVENT|<project_uuid>|<infos_json>|<base64_img>
                const msg = `ADD_EVENT|${projectId}|${infosJson}|${photoBase64 || ""}`;

                const res = await AFSendFlowMessage(appId, projectOwnerId, msg);

                if (res === false) {
                    showError("Failed to add event.");
                    if (addBtn) addBtn.disabled = false;
                    return;
                }

                closeOverlay();
                if (typeof renderTable === "function") renderTable();

            } catch (ex) {
                console.warn("ADD_EVENT failed", ex);
                showError("Failed to add event.");
                if (addBtn) addBtn.disabled = false;
            }
        });

        nameEl?.focus();

        function readFileAsDataURL(file) {
            return new Promise((resolve, reject) => {
                const r = new FileReader();
                r.onload = () => resolve(String(r.result || ""));
                r.onerror = reject;
                r.readAsDataURL(file);
            });
        }
    }

    // -------------------------------------------------------------------------
    // 7) ARTIST form wiring (validation + backend call)
    // -------------------------------------------------------------------------

    function wireArtistForm() {

        const nameEl = document.getElementById("ae_artist_name");
        const descEl = document.getElementById("ae_artist_desc");

        const errEl = document.getElementById("ae_artist_error");
        const cancelBtn = document.getElementById("ae_artist_cancel");
        const addBtn = document.getElementById("ae_artist_add");

        const showError = (msg) => {
            if (!errEl) return;
            errEl.textContent = msg || "Error";
            errEl.style.display = "block";
        };
        const hideError = () => {
            if (!errEl) return;
            errEl.textContent = "";
            errEl.style.display = "none";
        };

        cancelBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            closeOverlay();
        });

        addBtn?.addEventListener("click", async (e) => {
            e.preventDefault();
            hideError();

            const payload = {
                name: (nameEl?.value || "").trim(),
                description: (descEl?.value || "").trim(),
            };

            if (!payload.name) { showError("Please enter an artist name."); return; }

            // Resolve project_id + destination owner (project owner)
            const { projectId, projectOwnerId } = getSelectedProjectContext();
            if (!projectId) { showError("Please select a project first."); return; }
            if (!projectOwnerId) { showError("Project owner is missing for this project."); return; }

            if (addBtn) addBtn.disabled = true;

            try {
                const appId = await AFGetAppID();

                const safePayload = {
                    ...payload,
                    name: payload.name.replaceAll("|", " "),
                    description: payload.description.replaceAll("|", " "),
                };

                const infosJson = JSON.stringify(safePayload);

                // UPDATED stride:
                // ADD_ARTIST|<project_uuid>|<infos_json>
                const msg = `ADD_ARTIST|${projectId}|${infosJson}`;

                const res = await AFSendFlowMessage(appId, projectOwnerId, msg);

                if (res === false) {
                    showError("Failed to add artist.");
                    if (addBtn) addBtn.disabled = false;
                    return;
                }

                closeOverlay();
                if (typeof renderTable === "function") renderTable();

            } catch (ex) {
                console.warn("ADD_ARTIST failed", ex);
                showError("Failed to add artist.");
                if (addBtn) addBtn.disabled = false;
            }
        });

        nameEl?.focus();
    }

    // -------------------------------------------------------------------------
    // 8) VENUE form wiring (validation + backend call)
    // -------------------------------------------------------------------------

    function wireVenueForm() {

        const nameEl = document.getElementById("ae_venue_name");
        const descEl = document.getElementById("ae_venue_desc");

        const errEl = document.getElementById("ae_venue_error");
        const cancelBtn = document.getElementById("ae_venue_cancel");
        const addBtn = document.getElementById("ae_venue_add");

        const showError = (msg) => {
            if (!errEl) return;
            errEl.textContent = msg || "Error";
            errEl.style.display = "block";
        };
        const hideError = () => {
            if (!errEl) return;
            errEl.textContent = "";
            errEl.style.display = "none";
        };

        cancelBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            closeOverlay();
        });

        addBtn?.addEventListener("click", async (e) => {
            e.preventDefault();
            hideError();

            const payload = {
                name: (nameEl?.value || "").trim(),
                description: (descEl?.value || "").trim(),
            };

            if (!payload.name) { showError("Please enter a venue name."); return; }

            // Resolve project_id + destination owner (project owner)
            const { projectId, projectOwnerId } = getSelectedProjectContext();
            if (!projectId) { showError("Please select a project first."); return; }
            if (!projectOwnerId) { showError("Project owner is missing for this project."); return; }

            if (addBtn) addBtn.disabled = true;

            try {
                const appId = await AFGetAppID();

                const safePayload = {
                    ...payload,
                    name: payload.name.replaceAll("|", " "),
                    description: payload.description.replaceAll("|", " "),
                };

                const infosJson = JSON.stringify(safePayload);

                // UPDATED stride:
                // ADD_VENUE|<project_uuid>|<infos_json>
                const msg = `ADD_VENUE|${projectId}|${infosJson}`;

                const res = await AFSendFlowMessage(appId, projectOwnerId, msg);

                if (res === false) {
                    showError("Failed to add venue.");
                    if (addBtn) addBtn.disabled = false;
                    return;
                }

                closeOverlay();
                if (typeof renderTable === "function") renderTable();

            } catch (ex) {
                console.warn("ADD_VENUE failed", ex);
                showError("Failed to add venue.");
                if (addBtn) addBtn.disabled = false;
            }
        });

        nameEl?.focus();
    }

    // -------------------------------------------------------------------------
    // 9) Tiny HTML escape helper (local to this single function)
    // -------------------------------------------------------------------------
    function escapeHtml(s) {
        return String(s ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }
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

async function loadEventsFromFilters() 
{

    // -------------------------------------------------------------------------
    // loadEventsFromFilters()
    // -------------------------------------------------------------------------
    // - Reads current month/year from the date filter UI
    // - Resolves selected project + PROJECT OWNER from window.projects
    // - Calls backend to retrieve events for that project + period
    // - Stores result into window.events
    //
    // Expected DOM:
    // - #filter-month (value: "01".."12")
    // - #filter-year  (value: "YYYY")
    // - #project-list (selected project_id)
    //
    // NOTE:
    // - Adjust the backend command format to your real contract.
    // -------------------------------------------------------------------------

    try {

        const month = (document.getElementById("filter-month")?.value || "").trim(); // "02"
        const year  = (document.getElementById("filter-year")?.value || "").trim();  // "2026"

        // Resolve selected project + destination owner
        const projectSelect = document.getElementById("project-list");
        const projectId = (projectSelect?.value || "").trim();

        const projects = Array.isArray(window.projects) ? window.projects : [];
        const proj = projects.find(p => String(p?.project_id || "") === projectId) || null;

        const projectOwnerId = (proj && typeof proj === "object")
            ? String(proj.owner || "")
            : "";

        if (!projectId || !projectOwnerId) {
            window.events = [];
            if (typeof renderTable === "function") renderTable();
            return [];
        }

        // Basic validation
        if (!/^\d{4}$/.test(year) || !/^\d{2}$/.test(month)) {
            window.events = [];
            if (typeof renderTable === "function") renderTable();
            return [];
        }

        const appId = await AFGetAppID();

        // TODO: Adapt to your backend protocol
        // Example: LIST_EVENTS|<project_uuid>|<YYYY>|<MM>
        const res = await AFSendFlowMessage(appId, projectOwnerId, `LIST_EVENTS|${projectId}|${year}|${month}`);

        // Normalize
        let list = [];
        if (Array.isArray(res)) {
            list = res;
        } else if (typeof res === "string") {
            try {
                const parsed = JSON.parse(res);
                list = Array.isArray(parsed) ? parsed : [];
            } catch {
                list = [];
            }
        } else {
            list = [];
        }

        window.events = list;

        return list;

    } catch (err) {

        console.error("Failed to load events:", err);
        window.events = [];
        if (typeof renderTable === "function") renderTable();
        return [];

    }
}

function initializeDateFilters() 
{

    // -------------------------------------------------------------------------
    // initializeDateFilters()
    // -------------------------------------------------------------------------
    // Purpose:
    // - Populates the Month dropdown (once)
    // - Sets default Month/Year to the current local date
    // - Binds UI listeners (once) so that any change triggers:
    //     loadEventsFromFilters()  -> fetch from backend
    // - Triggers an initial events load at the end (first screen)
    //
    // Expected DOM:
    // - #filter-month (select)
    // - #filter-year  (input)
    // - #year-dec, #year-inc (buttons)
    //
    // Expected function:
    // - loadEventsFromFilters() (async) must exist and will call renderTable()
    // -------------------------------------------------------------------------

    const monthSelect = document.getElementById("filter-month");
    const yearInput   = document.getElementById("filter-year");
    const yearDec     = document.getElementById("year-dec");
    const yearInc     = document.getElementById("year-inc");

    if (!monthSelect || !yearInput || !yearDec || !yearInc) return;

    // -------------------------------------------------------------------------
    // 1) Populate month options (idempotent)
    // -------------------------------------------------------------------------
    if (monthSelect.dataset.populated !== "1") {

        const months = [
            "January","February","March","April","May","June",
            "July","August","September","October","November","December"
        ];

        monthSelect.innerHTML = "";

        for (let i = 0; i < 12; i++) {
            const opt = document.createElement("option");
            opt.value = String(i + 1).padStart(2, "0"); // "01".."12"
            opt.textContent = months[i];
            monthSelect.appendChild(opt);
        }

        monthSelect.dataset.populated = "1";
    }

    // -------------------------------------------------------------------------
    // 2) Defaults (current local month/year)
    // -------------------------------------------------------------------------
    const now = new Date();
    const currentYear  = now.getFullYear();
    const currentMonth = String(now.getMonth() + 1).padStart(2, "0");

    monthSelect.value = currentMonth;
    yearInput.value = String(currentYear);

    // -------------------------------------------------------------------------
    // 3) Helpers
    // -------------------------------------------------------------------------
    const clampYear = (y) => {
        if (!Number.isFinite(y)) return currentYear;
        if (y < 1970) return 1970;
        if (y > 2100) return 2100;
        return y;
    };

    const sanitizeYearInput = () => {
        yearInput.value = (yearInput.value || "").replace(/[^\d]/g, "").slice(0, 4);
    };

    const normalizeYearOrReset = () => {
        const v = yearInput.value || "";
        if (!/^\d{4}$/.test(v)) {
            yearInput.value = String(currentYear);
        } else {
            yearInput.value = String(clampYear(parseInt(v, 10)));
        }
    };

    const reload = async () => {
        if (typeof loadEventsFromFilters === "function") {
            await loadEventsFromFilters();
        }
        if (typeof renderTable === "function") {
            renderTable();
        }
    };

    // -------------------------------------------------------------------------
    // 4) Bind listeners once
    // -------------------------------------------------------------------------
    if (monthSelect.dataset.bound !== "1") {
        monthSelect.dataset.bound = "1";

        // Month change -> reload events from backend
        monthSelect.addEventListener("change", async () => {
            await reload();
        });

        // Stepper buttons -> adjust year then reload
        yearDec.addEventListener("click", async () => {
            const y = clampYear(parseInt(yearInput.value || String(currentYear), 10));
            yearInput.value = String(y - 1);
            await reload();
        });

        yearInc.addEventListener("click", async () => {
            const y = clampYear(parseInt(yearInput.value || String(currentYear), 10));
            yearInput.value = String(y + 1);
            await reload();
        });

        // Manual typing (digits only)
        yearInput.addEventListener("input", () => {
            sanitizeYearInput();
        });

        // Commit year change on blur -> normalize then reload
        yearInput.addEventListener("blur", async () => {
            sanitizeYearInput();
            normalizeYearOrReset();
            await reload();
        });

        // Keyboard UX
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

    // -------------------------------------------------------------------------
    // 5) Initial events load (first screen)
    // -------------------------------------------------------------------------
    reload();

}

async function loadProjects() 
{

    // -----------------------------------------------------------------------------
    // loadProjects()
    // -----------------------------------------------------------------------------
    // Purpose:
    // - Calls backend command "LIST_PROJECTS" to retrieve the projects list.
    // - Stores the normalized result into window.projects.
    // - Populates <select id="project-list"> with the projects.
    // - Loads project-scoped data (artists + venues) for the currently selected project.
    // - Binds UI listeners once:
    //     - Project selection change -> reload artists + venues -> re-render table
    //     - Main view selection change -> re-render table (switch which table is visible)
    //
    // Expected backend response:
    // - Array of objects, or JSON string that parses to an array:
    //     [
    //       { project_id: "...", name: "...", owner: "...", owner_name: "...", ... },
    //       ...
    //     ]
    //
    // Requirements:
    // - AFGetAppID()
    // - AFGetOwnerID()
    // - AFSendFlowMessage(appId, userId, message)
    // - loadArtists(), loadVenues() (project-scoped lists)
    // - renderTable() (safe to call repeatedly)
    // -----------------------------------------------------------------------------

    try {

        // -------------------------------------------------------------------------
        // 1) Fetch projects from backend
        // -------------------------------------------------------------------------
        const appId   = await AFGetAppID();
        const ownerId = await AFGetOwnerID();

        const res = await AFSendFlowMessage(
            appId,
            ownerId,
            "LIST_PROJECTS"
        );

        // -------------------------------------------------------------------------
        // 2) Normalize result into window.projects
        // -------------------------------------------------------------------------
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

        // Debug output (keep if useful during dev)
        console.log("Projects loaded:", window.projects);

        // -------------------------------------------------------------------------
        // 3) Populate <select id="project-list">
        // -------------------------------------------------------------------------
        const projectSelect = document.getElementById("project-list");
        if (projectSelect) {

            // Reset options
            projectSelect.innerHTML = "";

            // Populate options
            window.projects.forEach(proj => {
                const opt = document.createElement("option");
                opt.value = proj.project_id || "";
                opt.textContent = (proj.owner_name ? (proj.owner_name + " - ") : "") + (proj.name || "");
                projectSelect.appendChild(opt);
            });

            // ---------------------------------------------------------------------
            // 4) Initial load of project-scoped lists (artists + venues)
            // ---------------------------------------------------------------------
            await Promise.all([
                loadArtists(),
                loadVenues(),
            ]);

            // ---------------------------------------------------------------------
            // 5) Bind main view change -> re-render which table is displayed
            // ---------------------------------------------------------------------
            const viewSelect = document.getElementById("main-view-select");
            if (viewSelect && viewSelect.dataset.boundMainViewChange !== "1") {
                viewSelect.dataset.boundMainViewChange = "1";

                viewSelect.addEventListener("change", () => {
                    if (typeof renderTable === "function") renderTable();
                });
            }
        }

        // -------------------------------------------------------------------------
        // 6) Bind project change -> reload artists + venues for the selected project
        //    and re-render (so UI stays consistent with the new context)
        // -------------------------------------------------------------------------
        if (projectSelect && projectSelect.dataset.boundProjectChange !== "1") {
            projectSelect.dataset.boundProjectChange = "1";

            projectSelect.addEventListener("change", async () => {

                await Promise.all([
                    loadArtists(),
                    loadVenues(),
                ]);

                if (typeof renderTable === "function") renderTable();
            });
        }

    } catch (err) {

        console.error("Failed to load projects:", err);
        window.projects = [];

        // Optional: render an empty table state if you want predictable UI behavior
        if (typeof renderTable === "function") renderTable();

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

async function loadArtists() 
{

    // -----------------------------------------------------------------------------
    // loadArtists()
    // -----------------------------------------------------------------------------
    // - Reads selected project from <select id="project-list">
    // - Resolves the PROJECT OWNER from window.projects ({ project_id, owner, ... })
    // - Calls: LIST_ARTISTS|<project_uuid>  (sent to the project owner)
    // - Stores result into:
    //     window.artists   (array)
    //     window.artistsById (map: { [artist_id]: artistObj })
    //
    // Expected backend response (array items):
    //   { name, description, created_at, created_by, artist_id, ... }
    // -----------------------------------------------------------------------------

    try {

        // 1) Resolve selected project + destination owner
        const projectSelect = document.getElementById("project-list");
        const projectId = (projectSelect?.value || "").trim();

        const projects = Array.isArray(window.projects) ? window.projects : [];
        const proj = projects.find(p => String(p?.project_id || "") === projectId) || null;

        const projectOwnerId = (proj && typeof proj === "object")
            ? String(proj.owner || "")
            : "";

        if (!projectId || !projectOwnerId) {
            window.artists = [];
            window.artistsById = {};
            return [];
        }

        // 2) Call backend (to project owner)
        const appId = await AFGetAppID();
        const res = await AFSendFlowMessage(appId, projectOwnerId, `LIST_ARTISTS|${projectId}`);

        // 3) Normalize
        let list = [];
        if (Array.isArray(res)) {
            list = res;
        } else if (typeof res === "string") {
            try {
                const parsed = JSON.parse(res);
                list = Array.isArray(parsed) ? parsed : [];
            } catch {
                list = [];
            }
        }

        // 4) Store globals
        window.artists = list;

        const byId = {};
        for (const a of list) {
            if (!a || typeof a !== "object") continue;
            const id = String(a.artist_id ?? a.id ?? a.artistId ?? "").trim();
            if (!id) continue;
            byId[id] = a;
        }
        window.artistsById = byId;

        return list;

    } catch (err) {

        console.error("Failed to load artists:", err);
        window.artists = [];
        window.artistsById = {};
        return [];

    }

}

async function loadVenues() 
{

    // -----------------------------------------------------------------------------
    // loadVenues()
    // -----------------------------------------------------------------------------
    // - Reads selected project from <select id="project-list">
    // - Resolves the PROJECT OWNER from window.projects ({ project_id, owner, ... })
    // - Calls: LIST_VENUES|<project_uuid>  (sent to the project owner)
    // - Stores result into:
    //     window.venues   (array)
    //     window.venuesById (map: { [venue_id]: venueObj })
    //
    // Expected backend response (array items):
    //   { name, description, created_at, created_by, venue_id, ... }
    // -----------------------------------------------------------------------------

    try {

        // 1) Resolve selected project + destination owner
        const projectSelect = document.getElementById("project-list");
        const projectId = (projectSelect?.value || "").trim();

        const projects = Array.isArray(window.projects) ? window.projects : [];
        const proj = projects.find(p => String(p?.project_id || "") === projectId) || null;

        const projectOwnerId = (proj && typeof proj === "object")
            ? String(proj.owner || "")
            : "";

        if (!projectId || !projectOwnerId) {
            window.venues = [];
            window.venuesById = {};
            return [];
        }

        // 2) Call backend (to project owner)
        const appId = await AFGetAppID();
        const res = await AFSendFlowMessage(appId, projectOwnerId, `LIST_VENUES|${projectId}`);

        // 3) Normalize
        let list = [];
        if (Array.isArray(res)) {
            list = res;
        } else if (typeof res === "string") {
            try {
                const parsed = JSON.parse(res);
                list = Array.isArray(parsed) ? parsed : [];
            } catch {
                list = [];
            }
        }

        // 4) Store globals
        window.venues = list;

        const byId = {};
        for (const v of list) {
            if (!v || typeof v !== "object") continue;
            const id = String(v.venue_id ?? v.id ?? v.venueId ?? "").trim();
            if (!id) continue;
            byId[id] = v;
        }
        window.venuesById = byId;

        return list;

    } catch (err) {

        console.error("Failed to load venues:", err);
        window.venues = [];
        window.venuesById = {};
        return [];

    }

}

