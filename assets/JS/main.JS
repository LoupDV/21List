// ==============================
// MAIN.JS GLOBAL POUR TOUT LE SITE
// ==============================

/**
 * Fonction pour retirer la classe 'no-transition' une fois la page chargée.
 */
function enableTransitions() {
    document.documentElement.classList.remove('no-transition');
    console.log("Transitions enabled."); // Debug
}

// --- Gestion des Modales ---
// ... (tes fonctions openModal, closeModal, openEditModal, closeEditModal, openSettings, closeSettings restent les mêmes ici) ...
function openModal(modalId) {
    const modalContainer = document.getElementById(modalId);
    if (!modalContainer) {
        console.error(`Modal "${modalId}" not found.`);
        return;
    }
    const modalContent = modalContainer.querySelector('.modal-content');
    if (!modalContent) {
        console.error(`Modal content not found in "#${modalId}".`);
        return;
    }
    modalContainer.style.display = 'flex';
    modalContent.classList.remove('modal-hide');
    console.log(`Modal "${modalId}" opened.`);
}
function closeModal(modalId) {
    const modalContainer = document.getElementById(modalId);
    if (!modalContainer) {
        console.error(`Modal "${modalId}" not found.`);
        return;
    }
    const modalContent = modalContainer.querySelector('.modal-content');
    if (!modalContent) {
        console.error(`Modal content not found in "#${modalId}".`);
        return;
    }
    modalContent.classList.add('modal-hide');
    setTimeout(() => {
        modalContainer.style.display = 'none';
        modalContent.classList.remove('modal-hide');
        console.log(`Modal "${modalId}" closed.`);
    }, 300);
}
function openEditModal(cardElement) {
    const id = cardElement.getAttribute('data-id');
    const title = cardElement.getAttribute('data-title');
    const author = cardElement.getAttribute('data-author');
    const genre = cardElement.getAttribute('data-genre');
    const status = cardElement.getAttribute('data-status');
    const chapters = cardElement.getAttribute('data-chapters');
    const note = cardElement.getAttribute('data-note');
    const commentaire = cardElement.getAttribute('data-commentaire');
    const modalTitle = document.getElementById('edit-modal-title');
    const editIdInput = document.getElementById('edit-id');
    const deleteIdInput = document.getElementById('delete-id');
    const editTitleInput = document.getElementById('edit-title');
    const editAuthorInput = document.getElementById('edit-author');
    const editGenreInput = document.getElementById('edit-genre');
    const editStatusSelect = document.getElementById('edit-status');
    const editChaptersInput = document.getElementById('edit-chapters-read');
    const editNoteInput = document.getElementById('edit-note');
    const editCommentaireTextarea = document.getElementById('edit-commentaire');
    if (modalTitle) modalTitle.innerText = "Modifier " + title;
    if (editIdInput) editIdInput.value = id;
    if (deleteIdInput) deleteIdInput.value = id;
    if (editTitleInput) editTitleInput.value = title || '';
    if (editAuthorInput) editAuthorInput.value = author || '';
    if (editGenreInput) editGenreInput.value = genre || '';
    if (editStatusSelect) editStatusSelect.value = status || '';
    if (editChaptersInput) editChaptersInput.value = chapters || '0';
    if (editNoteInput) editNoteInput.value = note || '';
    if (editCommentaireTextarea) editCommentaireTextarea.value = commentaire || '';
    openModal('editModal');
}
function closeEditModal() {
    closeModal('editModal');
}
function openSettings() {
    openModal('settings-modal');
}
function closeSettings() {
    closeModal('settings-modal');
}

// --- Gestion du Thème ---
// ... (tes fonctions setTheme, changeTheme, loadTheme restent les mêmes ici) ...
function setTheme(theme) {
    const themes = {
        default: {
            '--bg-color': '#f0f2f5', '--card-bg': '#ffffff', '--text-color': '#1f2937',
            '--accent-color': '#6366f1', '--accent-hover': '#4f46e5', '--navbar-color': '#1f2937',
            '--modal-bg-color': '#ffffff'
        },
        dark: {
            '--bg-color': '#121212', '--card-bg': '#1e1e1e', '--text-color': '#e0e0e0',
            '--accent-color': '#bb86fc', '--accent-hover': '#985eff', '--navbar-color': '#1e1e1e',
            '--modal-bg-color': '#2c2c2c'
        }
    };
    const selectedTheme = themes[theme] || themes.default;
    for (const [key, value] of Object.entries(selectedTheme)) {
        document.documentElement.style.setProperty(key, value);
    }
    console.log(`Theme set to: ${theme}`);
}
function changeTheme(selectedThemeValue) {
    setTheme(selectedThemeValue);
    localStorage.setItem('theme', selectedThemeValue);
}
function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'default';
    setTheme(savedTheme);
    const themeSelector = document.getElementById('theme-selector');
    if (themeSelector) {
        themeSelector.value = savedTheme;
    }
}

// --- Gestion du Filtrage Serveur ---
// ... (ta fonction applyServerFilters reste la même ici) ...
function applyServerFilters() {
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const demographicFilter = document.getElementById('demographicFilter'); // Nouveau
    const statusPublicationFilter = document.getElementById('statusPublicationFilter'); // Nouveau
    const genreSearchInput = document.getElementById('genreSearch'); // Nouveau

    const searchValue = searchInput ? searchInput.value.trim() : '';
    const typeValue = typeFilter ? typeFilter.value : '';
    const demographicValue = demographicFilter ? demographicFilter.value : ''; // Nouveau
    const statusPublicationValue = statusPublicationFilter ? statusPublicationFilter.value : ''; // Nouveau
    const genreSearchValue = genreSearchInput ? genreSearchInput.value.trim() : ''; // Nouveau

    const params = new URLSearchParams();
    if (searchValue) params.set('search', searchValue);
    if (typeValue) params.set('type', typeValue);
    if (demographicValue) params.set('demographic', demographicValue); // Nouveau
    if (statusPublicationValue) params.set('status_publication', statusPublicationValue); // Nouveau
    if (genreSearchValue) params.set('genre_search', genreSearchValue); // Nouveau

    params.set('page', '1');
    window.location.search = params.toString();
}
// N'oublie pas d'ajouter les event listeners pour ces nouveaux filtres
document.getElementById('demographicFilter')?.addEventListener('change', applyServerFilters);
document.getElementById('statusPublicationFilter')?.addEventListener('change', applyServerFilters);
document.getElementById('genreSearch')?.addEventListener('keypress', function(e){ if(e.key === 'Enter') applyServerFilters(); });

// --- Gestion du Loader ---
// ... (tes fonctions showLoader, hideLoader restent les mêmes ici) ...
function showLoader() {
    const loader = document.getElementById('loader');
    if (loader) loader.style.display = 'block';
}
function hideLoader() {
    const loader = document.getElementById('loader');
    if (loader) loader.style.display = 'none';
}

// ========================================
// INITIALISATION GLOBALE AU CHARGEMENT DOM
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded and parsed. SINGLE LISTENER."); // Debug

    // 1. Gestion du thème initial
    loadTheme();

    // 2. Effet Fade-in pour les cartes
    const cards = document.querySelectorAll('.grid-container .card.fade-in');
    console.log(`Found ${cards.length} cards for fade-in effect.`); // Debug
    cards.forEach((card, index) => {
        if (!card.classList.contains('visible')) {
             setTimeout(() => {
                card.classList.add('visible');
            }, 50 * index);
        }
    });

    // 3. Attachement des écouteurs pour le filtrage serveur
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchButton = document.getElementById('searchButton');

    console.log("Attempting to attach server filter listeners:", { searchInput, typeFilter, statusFilter, searchButton });

    if (searchInput) {
        console.log("MAIN.JS: Adding server filter listener (keypress Enter) to #searchInput");
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                console.log("--- Enter detected on searchInput, calling applyServerFilters ---");
                e.preventDefault();
                applyServerFilters();
            }
        });
    } else { console.log("MAIN.JS: #searchInput not found on this page."); }

    if (typeFilter) {
        console.log("MAIN.JS: Adding server filter listener (change) to #typeFilter");
        typeFilter.addEventListener('change', applyServerFilters);
    } else { console.log("MAIN.JS: #typeFilter not found on this page."); }

    if (statusFilter) {
        console.log("MAIN.JS: Adding server filter listener (change) to #statusFilter");
        statusFilter.addEventListener('change', applyServerFilters);
    } else { console.log("MAIN.JS: #statusFilter not found on this page."); }

    if (searchButton) {
        console.log("MAIN.JS: Adding server filter listener (click) to #searchButton");
        searchButton.addEventListener('click', function(e) {
            console.log("--- Click detected on searchButton, calling applyServerFilters ---");
            applyServerFilters();
        });
    } else { console.log("MAIN.JS: #searchButton not found on this page."); }


    // 4. Logique pour le panneau d'information au survol des cartes
    const cardWrappers = document.querySelectorAll('.card-wrapper');
    console.log(`Found ${cardWrappers.length} card wrappers for hover panel.`); // Debug

    // Définition des fonctions showPanel et hidePanel DANS le scope du DOMContentLoaded
    // ou à l'extérieur si elles étaient réutilisées ailleurs, mais ici c'est bien.
    function showPanel(wrapper, panel) {
        console.log("Showing panel for wrapper:", wrapper); // Debug
        panel.style.visibility = 'hidden';
        panel.style.opacity = '0';
        panel.classList.add('visible-for-measure');

        const cardRect = wrapper.getBoundingClientRect();
        const panelRect = panel.getBoundingClientRect(); // Mesurer APRÈS avoir ajouté visible-for-measure
        panel.classList.remove('visible-for-measure'); // Retirer avant de calculer les positions finales

        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        let panelTop = cardRect.top + (cardRect.height / 2) - (panelRect.height / 2);
        let panelLeft;
        const panelMargin = 15;
        const arrow = panel.querySelector('.panel-arrow');

        panelLeft = cardRect.right + panelMargin;
        if (arrow) arrow.className = 'panel-arrow arrow-left';

        if (panelLeft + panelRect.width > viewportWidth - panelMargin) {
            panelLeft = cardRect.left - panelRect.width - panelMargin;
            if (arrow) arrow.className = 'panel-arrow arrow-right';
        }

        if (panelTop < panelMargin) {
            panelTop = panelMargin;
        } else if (panelTop + panelRect.height > viewportHeight - panelMargin) {
            panelTop = Math.max(panelMargin, viewportHeight - panelRect.height - panelMargin); // Assurer de ne pas aller sous panelMargin
        }
        
        // Pour position:fixed, pas besoin de scrollX/scrollY
        panel.style.top = `${panelTop}px`;
        panel.style.left = `${panelLeft}px`;

        panel.style.visibility = 'visible';
        panel.style.opacity = '1';
        // Assurez-vous que votre CSS pour .card-wrapper:hover .card-info-panel
        // ou une classe .is-active gère bien la transformation finale (translateX(0) scale(1))
    }

    function hidePanel(panel) {
        console.log("Hiding panel:", panel); // Debug
        panel.style.opacity = '0';
        panel.style.pointerEvents = 'none';
        // La transition CSS s'occupe de la visibilité et de la transformation
    }

    cardWrappers.forEach(wrapper => {
        const panel = wrapper.querySelector('.card-info-panel');
        if (!panel) {
            // console.log("No panel found for wrapper:", wrapper); // Peut être verbeux si beaucoup de cartes n'ont pas de panel
            return;
        }

        let hoverTimeoutEnter;
        let hoverTimeoutLeave;

        wrapper.addEventListener('mouseenter', () => {
            console.log("Mouse enter on wrapper:", wrapper); // Debug
            clearTimeout(hoverTimeoutLeave); // Important: Annule un timeout de disparition si la souris revient vite
            hoverTimeoutEnter = setTimeout(() => {
                showPanel(wrapper, panel);
            }, 200);
        });

        wrapper.addEventListener('mouseleave', () => {
            console.log("Mouse leave from wrapper:", wrapper); // Debug
            clearTimeout(hoverTimeoutEnter); // Annule le timeout d'apparition si la souris sort avant
            hoverTimeoutLeave = setTimeout(() => {
                hidePanel(panel);
            }, 100); // Délai plus court pour la disparition
        });

        // Il n'est généralement pas nécessaire d'avoir un mouseleave sur le panel lui-même
        // si le panel est un enfant direct de wrapper et que le mouseleave du wrapper suffit.
        // Si le panel peut déborder du wrapper et que la souris peut être DESSUS alors qu'elle
        // a quitté le wrapper, alors cet écouteur peut être utile.
        /*
        panel.addEventListener('mouseleave', () => {
             console.log("Mouse leave from panel itself:", panel); // Debug
             clearTimeout(hoverTimeoutEnter); // S'assurer qu'un éventuel affichage est annulé
             hidePanel(panel); // Cacher immédiatement ou avec un petit délai
        });
        */
    });

    // 5. Activer les transitions CSS
    setTimeout(enableTransitions, 50); // Placé à la fin pour que tout le reste soit configuré
});


// Note: Les fonctions appelées par onclick() ou onchange() dans le HTML
// (comme openEditModal, closeSettings, changeTheme)
// doivent rester accessibles globalement (elles le sont car définies en dehors
// de tout bloc spécifique comme DOMContentLoaded ici).