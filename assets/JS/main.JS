// ==============================
// MAIN.JS GLOBAL POUR TOUT LE SITE
// ==============================

// ----------------------
// Modales
// ----------------------

// Dès que la page est complètement chargée
window.addEventListener('load', () => {
    document.documentElement.classList.remove('no-transition');
});

// Ouvre la modale d'édition (si présente)
function openEditModal(card) {
    const modalContainer = document.getElementById('editModal');
    if (!modalContainer) return;

    const modalContent = modalContainer.querySelector('.modal-content');
    modalContainer.style.display = 'flex';
    
    const title = card.getAttribute('data-title');
    document.getElementById('edit-modal-title').innerText = "Modifier " + title;

    modalContent.classList.remove('modal-hide');
    modalContent.classList.add('modal-content');

    document.getElementById('edit-id').value = card.getAttribute('data-id');
    document.getElementById('delete-id').value = card.getAttribute('data-id');
    document.getElementById('edit-title').value = card.getAttribute('data-title');
    document.getElementById('edit-author').value = card.getAttribute('data-author');
    document.getElementById('edit-genre').value = card.getAttribute('data-genre');
    document.getElementById('edit-status').value = card.getAttribute('data-status');
    document.getElementById('edit-chapters-read').value = card.getAttribute('data-chapters');
    document.getElementById('edit-note').value = card.getAttribute('data-note') ?? '';
    document.getElementById('edit-commentaire').value = card.getAttribute('data-commentaire') ?? '';
}

function closeEditModal() {
    const modalContainer = document.getElementById('editModal');
    if (!modalContainer) return;

    const modalContent = modalContainer.querySelector('.modal-content');
    modalContent.classList.remove('modal-content');
    modalContent.classList.add('modal-hide');

    setTimeout(() => {
        modalContainer.style.display = 'none';
        modalContent.classList.remove('modal-hide');
        modalContent.classList.add('modal-content');
    }, 300);
}

// Ouvre la modale d'ajout (si présente)
function openAddModal(card) {
    document.getElementById('addModal').style.display = 'flex';

    const title = card.getAttribute('data-title');

    document.getElementById('add-title').value = title;
    document.getElementById('add-author').value = card.getAttribute('data-author');
    document.getElementById('add-genre').value = card.getAttribute('data-genre');
    document.getElementById('add-cover').value = card.getAttribute('data-cover');

    document.getElementById('add-modal-title').innerText = "Ajouter " + title;
}


function closeAddModal() {
    const modalContainer = document.getElementById('addModal');
    if (!modalContainer) return;

    const modalContent = modalContainer.querySelector('.modal-content');
    modalContent.classList.remove('modal-content');
    modalContent.classList.add('modal-hide');

    setTimeout(() => {
        modalContainer.style.display = 'none';
        modalContent.classList.remove('modal-hide');
        modalContent.classList.add('modal-content');
    }, 300);
}

// Ouvre la modale de paramètres
function openSettings() {
    const modalContainer = document.getElementById('settings-modal');
    if (!modalContainer) return;

    const modalContent = modalContainer.querySelector('.modal-content');
    modalContainer.style.display = 'flex';
    modalContent.classList.remove('modal-hide');
    modalContent.classList.add('modal-content');
}

function closeSettings() {
    const modalContainer = document.getElementById('settings-modal');
    if (!modalContainer) return;

    const modalContent = modalContainer.querySelector('.modal-content');
    modalContent.classList.remove('modal-content');
    modalContent.classList.add('modal-hide');

    setTimeout(() => {
        modalContainer.style.display = 'none';
        modalContent.classList.remove('modal-hide');
        modalContent.classList.add('modal-content');
    }, 300);
}

// ----------------------
// Theme Management
// ----------------------
function setTheme(theme) {
    if (theme === 'dark') {
        document.documentElement.style.setProperty('--bg-color', '#121212');
        document.documentElement.style.setProperty('--card-color', '#1e1e1e');
        document.documentElement.style.setProperty('--text-color', '#e0e0e0');
        document.documentElement.style.setProperty('--accent-color', '#bb86fc');
        document.documentElement.style.setProperty('--accent-hover', '#985eff');
        document.documentElement.style.setProperty('--navbar-color', '#1e1e1e');
        document.documentElement.style.setProperty('--modal-bg-color', '#2c2c2c');
    } else {
        document.documentElement.style.setProperty('--bg-color', '#f0f2f5');
        document.documentElement.style.setProperty('--card-color', '#ffffff');
        document.documentElement.style.setProperty('--text-color', '#1f2937');
        document.documentElement.style.setProperty('--accent-color', '#6366f1');
        document.documentElement.style.setProperty('--accent-hover', '#4f46e5');
        document.documentElement.style.setProperty('--navbar-color', '#1f2937');
        document.documentElement.style.setProperty('--modal-bg-color', '#ffffff');
    }
}

function changeTheme(theme) {
    setTheme(theme);
    localStorage.setItem('theme', theme);
}

function loadTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        setTheme(savedTheme);
        const selector = document.getElementById('theme-selector');
        if (selector) {
            selector.value = savedTheme;
        }
    }
}
document.addEventListener('DOMContentLoaded', loadTheme);

// ----------------------
// Recherche et Filtres Dynamiques (si search exist)
// ----------------------
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');

    if (searchInput) {
        searchInput.addEventListener('input', filterCards);
    }
    if (statusFilter || typeFilter) {
        (statusFilter || typeFilter).addEventListener('change', filterCards);
    }
});

// ============
// Filtres (Search + Status + Type)
// ============

// =========================
// Système de filtrage des cartes
// =========================
function filterCards() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter'); // Seulement sur index
    const typeFilter = document.getElementById('typeFilter');     // Seulement sur catalogue

    const searchText = searchInput ? searchInput.value.toLowerCase() : '';
    const selectedStatus = statusFilter ? statusFilter.value.toLowerCase() : '';
    const selectedType = typeFilter ? typeFilter.value.toLowerCase() : '';

    const cards = document.querySelectorAll('.card');
    let visibleCount = 0;

    cards.forEach(card => {
        const title = card.getAttribute('data-title')?.toLowerCase() || '';
        const author = card.getAttribute('data-author')?.toLowerCase() || '';
        const genre = card.getAttribute('data-genre')?.toLowerCase() || '';
        const status = card.getAttribute('data-status')?.toLowerCase() || '';
        const type = card.getAttribute('data-type')?.toLowerCase() || '';

        const matchesText = title.includes(searchText) || author.includes(searchText) || genre.includes(searchText);

        let matchesPageSpecific = true;

        if (statusFilter) {
            matchesPageSpecific = selectedStatus === "" || status === selectedStatus;
        }
        else if (typeFilter) {
            matchesPageSpecific = selectedType === "" || type.includes(selectedType);
        }

        if (matchesText && matchesPageSpecific) {
            card.style.display = "block";
            visibleCount++;
        } else {
            card.style.display = "none";
        }
    });

    const noResults = document.getElementById('noResults');
    if (noResults) {
        noResults.style.display = visibleCount === 0 ? "block" : "none";
    }
}



// =========================
// Lancer le filtrage quand l'utilisateur tape ou change un filtre
// =========================
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');

    if (searchInput) searchInput.addEventListener('input', filterCards);
    if (statusFilter) statusFilter.addEventListener('change', filterCards);
    if (typeFilter) typeFilter.addEventListener('change', filterCards);
});


// ========================
// Loader Show/Hide
// ========================
function showLoader() {
    const loader = document.getElementById('loader');
    if (loader) loader.style.display = 'block';
}

function hideLoader() {
    const loader = document.getElementById('loader');
    if (loader) loader.style.display = 'none';
}

// Fonction pour rendre les cards visibles progressivement
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');

    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('visible');
        }, 30 *index); // ⏳ délai léger entre chaque card pour un effet cascade
    });
});
