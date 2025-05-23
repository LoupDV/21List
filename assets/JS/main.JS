// ==============================
// MAIN.JS GLOBAL POUR TOUT LE SITE
// ==============================

/**
 * Fonction pour retirer la classe 'no-transition' de la balise <html>
 * afin d'activer les transitions CSS globales après le chargement initial.
 */
function enableTransitions() {
if (document.documentElement.classList.contains("no-transition")) {
    document.documentElement.classList.remove("no-transition");
    console.log(
    "MAIN.JS: Transitions CSS enabled globally (no-transition class removed)."
    );
} else {
    console.log(
    "MAIN.JS: no-transition class was already removed or not present on html tag."
    );
}
}

// --- Gestion des Modales ---
function openModal(modalId) {
const modalContainer = document.getElementById(modalId);
if (!modalContainer) {
    console.error(`MAIN.JS: Modal "${modalId}" not found.`);
    return;
}
const modalContent = modalContainer.querySelector(".modal-content");
if (!modalContent) {
    console.error(`MAIN.JS: Modal content not found in "#${modalId}".`);
    return;
}
modalContainer.style.display = "flex";
modalContent.classList.remove("modal-hide");
console.log(`MAIN.JS: Modal "${modalId}" opened.`);
}

function closeModal(modalId) {
const modalContainer = document.getElementById(modalId);
if (!modalContainer) {
    console.error(`MAIN.JS: Modal "${modalId}" not found.`);
    return;
}
const modalContent = modalContainer.querySelector(".modal-content");
if (!modalContent) {
    console.error(`MAIN.JS: Modal content not found in "#${modalId}".`);
    return;
}
modalContent.classList.add("modal-hide");
setTimeout(() => {
    modalContainer.style.display = "none";
    console.log(`MAIN.JS: Modal "${modalId}" closed.`);
}, 300);
}

function openEditModal(cardElement) {
const id = cardElement.getAttribute("data-id");
const title = cardElement.getAttribute("data-title");
const author = cardElement.getAttribute("data-author");
const genre = cardElement.getAttribute("data-genre");
const status = cardElement.getAttribute("data-status");
const chapters = cardElement.getAttribute("data-chapters");
const note = cardElement.getAttribute("data-note");
const commentaire = cardElement.getAttribute("data-commentaire");

const modalTitle = document.getElementById("edit-modal-title");
const editIdInput = document.getElementById("edit-id");
const deleteIdInput = document.getElementById("delete-id");
const editTitleInput = document.getElementById("edit-title");
const editAuthorInput = document.getElementById("edit-author");
const editGenreInput = document.getElementById("edit-genre");
const editStatusSelect = document.getElementById("edit-status");
const editChaptersInput = document.getElementById("edit-chapters-read");
const editNoteInput = document.getElementById("edit-note");
const editCommentaireTextarea = document.getElementById("edit-commentaire");

if (modalTitle) modalTitle.innerText = "Modifier: " + (title || "Élément");
if (editIdInput) editIdInput.value = id || "";
if (deleteIdInput) deleteIdInput.value = id || "";
if (editTitleInput) editTitleInput.value = title || "";
if (editAuthorInput) editAuthorInput.value = author || "";
if (editGenreInput) editGenreInput.value = genre || "";
if (editStatusSelect) editStatusSelect.value = status || "";
if (editChaptersInput) editChaptersInput.value = chapters || "0";
if (editNoteInput) editNoteInput.value = note || "";
if (editCommentaireTextarea)
    editCommentaireTextarea.value = commentaire || "";
openModal("editModal");
}

function closeEditModal() {
closeModal("editModal");
}
function openSettings() {
openModal("settings-modal");
}
function closeSettings() {
closeModal("settings-modal");
}

// --- Gestion du Thème ---
function setTheme(theme) {
const themes = {
    default: {
    "--bg-color": "#f0f2f5",
    "--card-bg": "#ffffff",
    "--text-color": "#1f2937",
    "--accent-color": "#6366f1",
    "--accent-hover": "#4f46e5",
    "--navbar-color": "#1f2937",
    "--modal-bg-color": "#ffffff",
    },
    dark: {
    "--bg-color": "#121212",
    "--card-bg": "#1e1e1e",
    "--text-color": "#e0e0e0",
    "--accent-color": "#bb86fc",
    "--accent-hover": "#985eff",
    "--navbar-color": "#1e1e1e",
    "--modal-bg-color": "#2c2c2c",
    },
};
const selectedTheme = themes[theme] || themes.default;
for (const [key, value] of Object.entries(selectedTheme)) {
    document.documentElement.style.setProperty(key, value);
}
console.log(`MAIN.JS: Theme set to: ${theme}`);
}
function changeTheme(selectedThemeValue) {
setTheme(selectedThemeValue);
localStorage.setItem("theme", selectedThemeValue);
}
function loadTheme() {
const savedTheme = localStorage.getItem("theme") || "default";
setTheme(savedTheme);
const themeSelector = document.getElementById("theme-selector");
if (themeSelector) themeSelector.value = savedTheme;
}

// --- Gestion du Filtrage Serveur ---
function applyServerFilters() {
const searchInput = document.getElementById("searchInput");
const typeFilter = document.getElementById("typeFilter");
const statusFilter = document.getElementById("statusFilter");
const demographicFilter = document.getElementById("demographicFilter");
const statusPublicationFilter = document.getElementById(
    "statusPublicationFilter"
);
const genreSearchInput = document.getElementById("genreSearch");
const params = new URLSearchParams(window.location.search);
const updateParam = (name, element) => {
    if (element) {
    const value = element.value.trim();
    if (value) params.set(name, value);
    else params.delete(name);
    }
};
updateParam("search", searchInput);
updateParam("type", typeFilter);
updateParam("status", statusFilter);
updateParam("demographic", demographicFilter);
updateParam("status_publication", statusPublicationFilter);
updateParam("genre_search", genreSearchInput);
params.set("page", "1");
window.location.search = params.toString();
}

// --- Gestion du Loader ---
function showLoader() {
const loader = document.getElementById("loader");
if (loader) loader.style.display = "block";
}
function hideLoader() {
const loader = document.getElementById("loader");
if (loader) loader.style.display = "none";
}

// ========================================
// INITIALISATION GLOBALE AU CHARGEMENT DOM
// ========================================
document.addEventListener("DOMContentLoaded", () => {
console.log("MAIN.JS: DOMContentLoaded event fired successfully!");

// 1. Charger le thème utilisateur
loadTheme();

// 2. Logique de Fade-in pour TOUTES les cartes sur la page
const cardsToFade = document.querySelectorAll(".card.fade-in");
console.log(
    `MAIN.JS: QuerySelector found ${cardsToFade.length} elements for '.card.fade-in'.`
);
// console.log(cardsToFade); // Décommente si besoin de voir la NodeList

cardsToFade.forEach((card, index) => {
    // console.log(`MAIN.JS: Loop iteration for card ${index + 1}`, card); // Décommente si besoin
    requestAnimationFrame(() => {
    setTimeout(() => {
        // console.log("MAIN.JS: INSIDE setTimeout - Adding .visible to card:", card); // Décommente si besoin
        if (!card.classList.contains("visible")) {
        card.classList.add("visible");
        // console.log("MAIN.JS: .visible class ADDED to", card); // Décommente si besoin
        } else {
        // console.log("MAIN.JS: .visible class ALREADY PRESENT on", card); // Décommente si besoin
        }
    }, 50 + index * 75);
    });
});

// 3. Attachement des écouteurs pour le filtrage serveur
// console.log("MAIN.JS: Attempting to attach server filter listeners:", { /* ... objets ... */ }); // Décommente si besoin
const searchInput = document.getElementById("searchInput");
const typeFilter = document.getElementById("typeFilter");
const statusFilter = document.getElementById("statusFilter");
const searchButton = document.getElementById("searchButton");
const demographicFilter = document.getElementById("demographicFilter");
const statusPublicationFilter = document.getElementById(
    "statusPublicationFilter"
);
const genreSearchInput = document.getElementById("genreSearch");

if (searchInput) {
    searchInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        applyServerFilters();
    }
    });
} else {
    /* console.log("MAIN.JS: #searchInput not found on this page."); */
}
if (typeFilter) typeFilter.addEventListener("change", applyServerFilters);
else {
    /* console.log("MAIN.JS: #typeFilter not found on this page."); */
}
if (statusFilter) statusFilter.addEventListener("change", applyServerFilters);
else {
    /* console.log("MAIN.JS: #statusFilter not found on this page."); */
}
if (searchButton) searchButton.addEventListener("click", applyServerFilters);
else {
    /* console.log("MAIN.JS: #searchButton not found on this page."); */
}
if (demographicFilter)
    demographicFilter.addEventListener("change", applyServerFilters);
else {
    /* console.log("MAIN.JS: #demographicFilter not found on this page."); */
}
if (statusPublicationFilter)
    statusPublicationFilter.addEventListener("change", applyServerFilters);
else {
    /* console.log("MAIN.JS: #statusPublicationFilter not found on this page."); */
}
if (genreSearchInput) {
    genreSearchInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        applyServerFilters();
    }
    });
} else {
    /* console.log("MAIN.JS: #genreSearchInput not found on this page."); */
}

// 4. Logique pour le panneau d'information au survol des cartes
console.log("MAIN.JS: === SURVOL SECTION START ==="); // Log de début pour cette section

const cardWrappers = document.querySelectorAll(".card-wrapper");
console.log(
    `MAIN.JS: SURVOL - Found ${cardWrappers.length} .card-wrapper elements.`
);

// Définition des fonctions showPanel et hidePanel DANS le scope du DOMContentLoaded
// et AVANT leur utilisation dans la boucle forEach.
function showPanel(wrapper, panel) {
    // console.log("MAIN.JS: SURVOL - showPanel called for", panel); // Décommente si besoin
    panel.classList.add("visible-for-measure");
    const cardRect = wrapper.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();
    panel.classList.remove("visible-for-measure");

    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const panelMargin = 15;
    let panelTop = cardRect.top + cardRect.height / 2 - panelRect.height / 2;
    let panelLeft = cardRect.right + panelMargin;

    if (panel.querySelector(".panel-arrow"))
    panel.querySelector(".panel-arrow").className = "panel-arrow arrow-left";

    if (panelLeft + panelRect.width > viewportWidth - panelMargin) {
    panelLeft = cardRect.left - panelRect.width - panelMargin;
    if (panel.querySelector(".panel-arrow"))
        panel.querySelector(".panel-arrow").className =
        "panel-arrow arrow-right";
    }
    if (panelTop < panelMargin) panelTop = panelMargin;
    else if (panelTop + panelRect.height > viewportHeight - panelMargin) {
    panelTop = Math.max(
        panelMargin,
        viewportHeight - panelRect.height - panelMargin
    );
    }
    panel.style.top = `${panelTop}px`;
    panel.style.left = `${panelLeft}px`;
    panel.classList.add("is-active");
}

function hidePanel(panel) {
    // console.log("MAIN.JS: SURVOL - hidePanel called for", panel); // Décommente si besoin
    panel.classList.remove("is-active");
}

if (cardWrappers.length > 0) {
    console.log("MAIN.JS: SURVOL - Attaching hover listeners to cardWrappers.");
    cardWrappers.forEach((wrapper, wrapperIndex) => {
    // console.log(`MAIN.JS: SURVOL - Processing wrapper ${wrapperIndex + 1}`, wrapper); // Décommente si besoin
    const panel = wrapper.querySelector(".card-info-panel");

    if (!panel) {
        console.log(
        `MAIN.JS: SURVOL - No .card-info-panel found for wrapper ${
            wrapperIndex + 1
        }:`,
        wrapper
        );
        return;
    }
    // console.log(`MAIN.JS: SURVOL - Found .card-info-panel for wrapper ${wrapperIndex + 1}`, panel); // Décommente si besoin

    let hoverTimeoutEnter, hoverTimeoutLeave;
    const enterDelay = 200,
        leaveDelay = 100;

    wrapper.addEventListener("mouseenter", () => {
        console.log(
        `MAIN.JS: SURVOL - MOUSE ENTER on wrapper (index ${
            wrapperIndex + 1
        }):`,
        wrapper
        );
        clearTimeout(hoverTimeoutLeave);
        hoverTimeoutEnter = setTimeout(() => {
        console.log(
            `MAIN.JS: SURVOL - Calling showPanel for wrapper (index ${
            wrapperIndex + 1
            }):`,
            wrapper
        );
        showPanel(wrapper, panel);
        }, enterDelay);
    });

    wrapper.addEventListener("mouseleave", () => {
        console.log(
        `MAIN.JS: SURVOL - MOUSE LEAVE from wrapper (index ${
            wrapperIndex + 1
        }):`,
        wrapper
        );
        clearTimeout(hoverTimeoutEnter);
        hoverTimeoutLeave = setTimeout(() => {
        console.log(
            `MAIN.JS: SURVOL - Calling hidePanel for panel (from wrapper index ${
            wrapperIndex + 1
            }):`,
            panel
        );
        hidePanel(panel);
        }, leaveDelay);
    });

    panel.addEventListener("mouseenter", () => {
        console.log(
        `MAIN.JS: SURVOL - MOUSE ENTER on panel itself (from wrapper index ${
            wrapperIndex + 1
        }):`,
        panel
        );
        clearTimeout(hoverTimeoutLeave);
    });

    panel.addEventListener("mouseleave", () => {
        console.log(
        `MAIN.JS: SURVOL - MOUSE LEAVE from panel itself (from wrapper index ${
            wrapperIndex + 1
        }):`,
        panel
        );
        hoverTimeoutLeave = setTimeout(() => {
        hidePanel(panel);
        }, leaveDelay);
    });
    });
    console.log("MAIN.JS: SURVOL - Hover listeners attachment finished.");
} else {
    console.log(
    "MAIN.JS: SURVOL - No .card-wrapper found, hover logic skipped."
    );
}
console.log("MAIN.JS: === SURVOL SECTION END ===");

// 5. Activer les transitions CSS après un court délai.
setTimeout(enableTransitions, 50);

console.log("MAIN.JS: DOMContentLoaded - All initializations attempted.");
});
