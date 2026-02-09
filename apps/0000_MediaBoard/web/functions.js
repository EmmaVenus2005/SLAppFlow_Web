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

function openAddEntityOverlay(title, html) {
    const overlay = document.getElementById("add-entity-overlay");
    const titleEl = document.getElementById("add-entity-title");
    const content = document.getElementById("add-entity-content");

    titleEl.textContent = title || "Add";
    content.innerHTML = html || "";

    overlay.style.display = "block";
}

function closeAddEntityOverlay() {
    const overlay = document.getElementById("add-entity-overlay");
    const content = document.getElementById("add-entity-content");
    overlay.style.display = "none";
    content.innerHTML = "";
}

/**
 * addCreateProjectListener
 *
 * Wires the "Add Project" (+) button to open the existing overlay, prompt for a name,
 * call the backend command "ADD_PROJECT|<name>", then inject the created project
 * into the project <select> and select it.
 *
 * Requirements:
 * - openAddEntityOverlay(title, html) and closeAddEntityOverlay() must exist.
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

    addBtn.addEventListener("click", () => {
        openAddEntityOverlay("Add Project", `
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

        const nameInput = document.getElementById("new-project-name");
        const cancelBtn = document.getElementById("add-project-cancel-btn");
        const confirmBtn = document.getElementById("add-project-confirm-btn");
        const errorBox = document.getElementById("add-project-error");

        const showError = (msg) => {
            if (!errorBox) return;
            errorBox.textContent = msg;
            errorBox.style.display = "block";
        };

        const doCreate = async () => {
            const projectName = (nameInput?.value || "").trim();
            if (!projectName) {
                showError("Please enter a project name.");
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
                    showError("Project creation failed.");
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

                closeAddEntityOverlay();
            } catch (err) {
                showError("Project creation failed.");
                if (confirmBtn) confirmBtn.disabled = false;
            }
        };

        cancelBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            closeAddEntityOverlay();
        });

        confirmBtn?.addEventListener("click", (e) => {
            e.preventDefault();
            doCreate();
        });

        nameInput?.addEventListener("keydown", (e) => {
            if (e.key === "Enter") doCreate();
        });

        nameInput?.focus();
    });
}
