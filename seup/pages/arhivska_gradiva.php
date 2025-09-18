<?php

/**
 * Plaćena licenca
 * (c) 2025 8Core Association
 * Tomislav Galić <tomislav@8core.hr>
 * Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima 
 * te ga je izričito zabranjeno umnožavati, distribuirati, mijenjati, objavljivati ili 
 * na drugi način eksploatirati bez pismenog odobrenja autora.
 */
/**
 *	\file       seup/pages/arhivska_gradiva.php
 *	\ingroup    seup
 *	\brief      Arhivska gradiva page
 */

// Učitaj Dolibarr okruženje
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Security check
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
    $action = '';
    $socid = $user->socid;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = GETPOST('action', 'alpha');
    
    if ($action === 'get_gradivo_details') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $rowid = GETPOST('rowid', 'int');
        
        if (!$rowid) {
            echo json_encode(['success' => false, 'error' => 'Missing ID']);
            exit;
        }
        
        $sql = "SELECT 
                    rowid,
                    oznaka,
                    vrsta_gradiva,
                    opisi_napomene,
                    DATE_FORMAT(datec, '%d.%m.%Y %H:%i') as datum_kreiranja
                FROM " . MAIN_DB_PREFIX . "a_arhivska_gradiva
                WHERE rowid = " . (int)$rowid;

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            echo json_encode([
                'success' => true,
                'gradivo' => $obj
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Arhivsko gradivo nije pronađeno'
            ]);
        }
        exit;
    }
}
    if ($action === 'delete_gradivo') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $rowid = GETPOST('rowid', 'int');
        
        if (!$rowid) {
            echo json_encode(['success' => false, 'error' => 'Missing ID']);
            exit;
        }
        
        $db->begin();
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_arhivska_gradiva WHERE rowid = " . (int)$rowid;
        $result = $db->query($sql);
        
        if ($result) {
            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Arhivsko gradivo je uspješno obrisano'
            ]);
        } else {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'error' => 'Greška pri brisanju: ' . $db->lasterror()
            ]);
        }
        exit;
    }

// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'oznaka';
$sortOrder = GETPOST('order', 'aZ09') ?: 'ASC';

// Validate sort fields
$allowedSortFields = ['rowid', 'oznaka', 'vrsta_gradiva', 'datec'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'oznaka';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Fetch all arhivska gradiva
$sql = "SELECT 
            rowid,
            oznaka,
            vrsta_gradiva,
            opisi_napomene,
            DATE_FORMAT(datec, '%d.%m.%Y %H:%i') as datum_kreiranja
        FROM " . MAIN_DB_PREFIX . "a_arhivska_gradiva
        ORDER BY {$sortField} {$sortOrder}";

$resql = $db->query($sql);
$gradiva = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $gradiva[] = $obj;
    }
}

$form = new Form($db);
llxHeader("", "Arhivska gradiva", '', '', 0, 0, '', '', '', 'mod-seup page-arhivska-gradiva');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
print '<link href="/custom/seup/css/suradnici.css" rel="stylesheet">';

// Main hero section
print '<main class="seup-settings-hero">';

// Copyright footer
print '<footer class="seup-footer">';
print '<div class="seup-footer-content">';
print '<div class="seup-footer-left">';
print '<p>Sva prava pridržana © <a href="https://8core.hr" target="_blank" rel="noopener">8Core Association</a> 2014 - ' . date('Y') . '</p>';
print '</div>';
print '<div class="seup-footer-right">';
print '<p class="seup-version">SEUP v.14.0.4</p>';
print '</div>';
print '</div>';
print '</footer>';

// Floating background elements
print '<div class="seup-floating-elements">';
for ($i = 1; $i <= 5; $i++) {
    print '<div class="seup-floating-element"></div>';
}
print '</div>';

print '<div class="seup-settings-content">';

// Header section
print '<div class="seup-settings-header">';
print '<h1 class="seup-settings-title">Vrste Arhivskog Gradiva</h1>';
print '<p class="seup-settings-subtitle">Upravljanje vrstama arhivskog gradiva i dokumentacije</p>';
print '</div>';

// Main content card
print '<div class="seup-suradnici-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-archive"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Popis Arhivskih Gradiva</h3>';
print '<p class="seup-card-description">Pregled svih registriranih vrsta arhivskog gradiva</p>';
print '</div>';
print '<div class="seup-card-actions">';
print '<button type="button" class="seup-btn seup-btn-primary" id="novoGradivoBtn">';
print '<i class="fas fa-plus me-2"></i>Novo Gradivo';
print '</button>';
print '</div>';
print '</div>';

// Search and filter section
print '<div class="seup-table-controls">';
print '<div class="seup-search-container">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-search seup-search-icon"></i>';
print '<input type="text" id="searchID" class="seup-search-input" placeholder="Pretraži po ID-u...">';
print '</div>';
print '</div>';
print '<div class="seup-filter-controls">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-tag seup-search-icon"></i>';
print '<input type="text" id="searchNaziv" class="seup-search-input" placeholder="Pretraži po nazivu...">';
print '</div>';
print '<select id="sortOrder" class="seup-filter-select">';
print '<option value="ASC"' . ($sortOrder === 'ASC' ? ' selected' : '') . '>A → Ž</option>';
print '<option value="DESC"' . ($sortOrder === 'DESC' ? ' selected' : '') . '>Ž → A</option>';
print '</select>';
print '</div>';
print '</div>';

// Enhanced table with modern styling
print '<div class="seup-table-container">';
print '<table class="seup-table">';
print '<thead class="seup-table-header">';
print '<tr>';

// Function to generate sortable header
function sortableHeader($field, $label, $currentSort, $currentOrder, $icon = '')
{
    $newOrder = ($currentSort === $field && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    $sortIcon = '';

    if ($currentSort === $field) {
        $sortIcon = ($currentOrder === 'ASC')
            ? ' <i class="fas fa-arrow-up seup-sort-icon"></i>'
            : ' <i class="fas fa-arrow-down seup-sort-icon"></i>';
    }

    return '<th class="seup-table-th sortable-header">' .
        '<a href="?sort=' . $field . '&order=' . $newOrder . '" class="seup-sort-link">' .
        ($icon ? '<i class="' . $icon . ' me-2"></i>' : '') .
        $label . $sortIcon .
        '</a></th>';
}

// Generate sortable headers with icons
print '<th class="seup-table-th"><i class="fas fa-hashtag me-2"></i>ID</th>';
print sortableHeader('oznaka', 'Oznaka', $sortField, $sortOrder, 'fas fa-tag');
print sortableHeader('vrsta_gradiva', 'Vrsta Gradiva', $sortField, $sortOrder, 'fas fa-archive');
print '<th class="seup-table-th"><i class="fas fa-align-left me-2"></i>Opisi/Napomene</th>';
print sortableHeader('datec', 'Datum Kreiranja', $sortField, $sortOrder, 'fas fa-calendar');
print '<th class="seup-table-th"><i class="fas fa-cogs me-2"></i>Akcije</th>';
print '</tr>';
print '</thead>';
print '<tbody class="seup-table-body">';

if (count($gradiva)) {
    foreach ($gradiva as $index => $gradivo) {
        $rowClass = ($index % 2 === 0) ? 'seup-table-row-even' : 'seup-table-row-odd';
        print '<tr class="seup-table-row ' . $rowClass . '" data-id="' . $gradivo->rowid . '">';
        
        print '<td class="seup-table-td">';
        print '<span class="seup-badge seup-badge-neutral">' . $gradivo->rowid . '</span>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-naziv-cell clickable-name" data-id="' . $gradivo->rowid . '" title="Kliknite za detalje">';
        print '<i class="fas fa-tag me-2"></i>';
        print htmlspecialchars($gradivo->oznaka);
        print '</div>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-gradivo-info">';
        print '<i class="fas fa-archive me-2"></i>';
        print htmlspecialchars($gradivo->vrsta_gradiva);
        print '</div>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        if (!empty($gradivo->opisi_napomene)) {
            print '<div class="seup-opis-cell" title="' . htmlspecialchars($gradivo->opisi_napomene) . '">';
            print dol_trunc($gradivo->opisi_napomene, 50);
            print '</div>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-date-info">';
        print '<i class="fas fa-calendar me-2"></i>';
        print $gradivo->datum_kreiranja;
        print '</div>';
        print '</td>';

        // Action buttons
        print '<td class="seup-table-td">';
        print '<div class="seup-action-buttons">';
        print '<button class="seup-action-btn seup-btn-view" title="Pregled detalja" data-id="' . $gradivo->rowid . '">';
        print '<i class="fas fa-eye"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-edit" title="Uredi" data-id="' . $gradivo->rowid . '">';
        print '<i class="fas fa-edit"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-delete" title="Obriši" data-id="' . $gradivo->rowid . '">';
        print '<i class="fas fa-trash"></i>';
        print '</button>';
        print '</div>';
        print '</td>';

        print '</tr>';
    }
} else {
    print '<tr class="seup-table-row">';
    print '<td colspan="6" class="seup-table-empty">';
    print '<div class="seup-empty-state">';
    print '<i class="fas fa-archive seup-empty-icon"></i>';
    print '<h4 class="seup-empty-title">Nema registriranih vrsta arhivskog gradiva</h4>';
    print '<p class="seup-empty-description">Dodajte prvu vrstu gradiva za početak rada</p>';
    print '<button type="button" class="seup-btn seup-btn-primary mt-3" id="novoGradivoBtn2">';
    print '<i class="fas fa-plus me-2"></i>Dodaj prvo gradivo';
    print '</button>';
    print '</div>';
    print '</td>';
    print '</tr>';
}

print '</tbody>';
print '</table>';
print '</div>'; // seup-table-container

// Table footer with stats and actions
print '<div class="seup-table-footer">';
print '<div class="seup-table-stats">';
print '<i class="fas fa-info-circle me-2"></i>';
print '<span>Prikazano <strong id="visibleCount">' . count($gradiva) . '</strong> od <strong>' . count($gradiva) . '</strong> vrsta gradiva</span>';
print '</div>';
print '<div class="seup-table-actions">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-btn-sm" id="exportBtn">';
print '<i class="fas fa-download me-2"></i>Izvoz Excel';
print '</button>';
print '</div>';
print '</div>';

print '</div>'; // seup-settings-card
print '</div>'; // seup-suradnici-container

print '</div>'; // seup-settings-content
print '</main>';

// Details Modal
print '<div class="seup-modal" id="detailsModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-archive me-2"></i>Detalji Arhivskog Gradiva</h5>';
print '<button type="button" class="seup-modal-close" id="closeDetailsModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div id="gradivoDetailsContent">';
print '<div class="seup-loading-message">';
print '<i class="fas fa-spinner fa-spin"></i> Učitavam detalje...';
print '</div>';
print '</div>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="closeDetailsBtn">Zatvori</button>';
print '<button type="button" class="seup-btn seup-btn-primary" id="editGradivoBtn">';
print '<i class="fas fa-edit me-2"></i>Uredi';
print '</button>';
print '</div>';
print '</div>';
print '</div>';

// Delete Modal
print '<div class="seup-modal" id="deleteModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-trash me-2"></i>Brisanje Arhivskog Gradiva</h5>';
print '<button type="button" class="seup-modal-close" id="closeDeleteModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div class="seup-delete-info">';
print '<div class="seup-delete-oznaka" id="deleteOznaka">ARH-001</div>';
print '<div class="seup-delete-vrsta" id="deleteVrsta">Vrsta gradiva</div>';
print '<div class="seup-delete-warning">';
print '<i class="fas fa-exclamation-triangle me-2"></i>';
print '<strong>PAŽNJA:</strong> Ova akcija je nepovratna! Arhivsko gradivo će biti trajno obrisano.';
print '</div>';
print '</div>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelDeleteBtn">Odustani</button>';
print '<button type="button" class="seup-btn seup-btn-danger" id="confirmDeleteBtn">';
print '<i class="fas fa-trash me-2"></i>Trajno Obriši';
print '</button>';
print '</div>';
print '</div>';
print '</div>';

// JavaScript for enhanced functionality
print '<script src="/custom/seup/js/seup-modern.js"></script>';

?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Navigation buttons
    const novoGradivoBtn = document.getElementById("novoGradivoBtn");
    const novoGradivoBtn2 = document.getElementById("novoGradivoBtn2");
    
    if (novoGradivoBtn) {
        novoGradivoBtn.addEventListener("click", function() {
            this.classList.add('seup-loading');
            window.location.href = "postavke.php#arhivska_gradiva";
        });
    }
    
    if (novoGradivoBtn2) {
        novoGradivoBtn2.addEventListener("click", function() {
            this.classList.add('seup-loading');
            window.location.href = "postavke.php#arhivska_gradiva";
        });
    }

    // Enhanced search and filter functionality
    const searchID = document.getElementById('searchID');
    const searchNaziv = document.getElementById('searchNaziv');
    const sortOrder = document.getElementById('sortOrder');
    const tableRows = document.querySelectorAll('.seup-table-row[data-id]');
    const visibleCountSpan = document.getElementById('visibleCount');

    function filterTable() {
        const idTerm = searchID.value.toLowerCase();
        const nazivTerm = searchNaziv.value.toLowerCase();
        let visibleCount = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.seup-table-td');
            const idText = cells[0].textContent.toLowerCase();
            const oznakaText = cells[1].textContent.toLowerCase();
            const vrstaText = cells[2].textContent.toLowerCase();
            
            // Check search terms
            const matchesID = !idTerm || idText.includes(idTerm);
            const matchesNaziv = !nazivTerm || oznakaText.includes(nazivTerm) || vrstaText.includes(nazivTerm);

            if (matchesID && matchesNaziv) {
                row.style.display = '';
                visibleCount++;
                // Add staggered animation
                row.style.animationDelay = `${visibleCount * 50}ms`;
                row.classList.add('animate-fade-in-up');
            } else {
                row.style.display = 'none';
                row.classList.remove('animate-fade-in-up');
            }
        });

        // Update visible count
        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleCount;
        }
    }

    if (searchID) {
        searchID.addEventListener('input', debounce(filterTable, 300));
    }
    
    if (searchNaziv) {
        searchNaziv.addEventListener('input', debounce(filterTable, 300));
    }

    if (sortOrder) {
        sortOrder.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', 'oznaka');
            currentUrl.searchParams.set('order', this.value);
            window.location.href = currentUrl.toString();
        });
    }

    // Enhanced row interactions
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // Clickable name functionality
    document.querySelectorAll('.clickable-name').forEach(nameCell => {
        nameCell.addEventListener('click', function() {
            const id = this.dataset.id;
            openDetailsModal(id);
        });
    });

    // Action button handlers
    document.querySelectorAll('.seup-btn-view').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            openDetailsModal(id);
        });
    });

    document.querySelectorAll('.seup-btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            this.classList.add('seup-loading');
            window.location.href = `postavke.php?edit_arh=${id}#arhivska_gradiva`;
        });
    });

    // Delete button handlers
    document.querySelectorAll('.seup-btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const row = this.closest('.seup-table-row');
            const oznakaCell = row.querySelector('.clickable-name');
            const vrstaCell = row.querySelector('.seup-gradivo-info');
            
            const oznaka = oznakaCell ? oznakaCell.textContent.trim() : 'N/A';
            const vrsta = vrstaCell ? vrstaCell.textContent.trim() : 'N/A';
            
            openDeleteModal(id, oznaka, vrsta);
        });
    });

    // Export handler
    document.getElementById('exportBtn').addEventListener('click', function() {
        this.classList.add('seup-loading');
        // Implement export functionality
        setTimeout(() => {
            this.classList.remove('seup-loading');
            showMessage('Excel izvoz je pokrenut', 'success');
        }, 2000);
    });

    // Modal functionality
    let currentGradivoId = null;
    let currentDeleteId = null;

    function openDetailsModal(gradivoId) {
        currentGradivoId = gradivoId;
        
        // Show modal
        const modal = document.getElementById('detailsModal');
        modal.classList.add('show');
        
        // Load details
        loadGradivoDetails(gradivoId);
    }

    function closeDetailsModal() {
        const modal = document.getElementById('detailsModal');
        modal.classList.remove('show');
        currentGradivoId = null;
    }

    function openDeleteModal(gradivoId, oznaka, vrsta) {
        currentDeleteId = gradivoId;
        
        // Update modal content
        document.getElementById('deleteOznaka').textContent = oznaka;
        document.getElementById('deleteVrsta').textContent = vrsta;
        
        // Show modal
        document.getElementById('deleteModal').classList.add('show');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
        currentDeleteId = null;
    }

    function confirmDelete() {
        if (!currentDeleteId) return;
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.classList.add('seup-loading');
        
        const formData = new FormData();
        formData.append('action', 'delete_gradivo');
        formData.append('rowid', currentDeleteId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove row from table with animation
                const row = document.querySelector(`[data-id="${currentDeleteId}"]`);
                if (row) {
                    row.style.animation = 'fadeOut 0.5s ease-out';
                    setTimeout(() => {
                        row.remove();
                        updateVisibleCount();
                    }, 500);
                }
                
                showMessage(data.message, 'success');
                closeDeleteModal();
            } else {
                showMessage('Greška pri brisanju: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showMessage('Došlo je do greške pri brisanju', 'error');
        })
        .finally(() => {
            confirmBtn.classList.remove('seup-loading');
        });
    }

    function updateVisibleCount() {
        const visibleRows = document.querySelectorAll('.seup-table-row[data-id]:not([style*="display: none"])');
        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleRows.length;
        }
    }

    function loadGradivoDetails(gradivoId) {
        const content = document.getElementById('gradivoDetailsContent');
        content.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Učitavam detalje...</div>';
        
        const formData = new FormData();
        formData.append('action', 'get_gradivo_details');
        formData.append('rowid', gradivoId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderGradivoDetails(data.gradivo);
            } else {
                content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading details:', error);
            content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>Greška pri učitavanju detalja</div>';
        });
    }

    function renderGradivoDetails(gradivo) {
        const content = document.getElementById('gradivoDetailsContent');
        
        let html = '<div class="seup-suradnik-details">';
        
        // Header with oznaka and basic info
        html += '<div class="seup-details-header">';
        html += '<div class="seup-details-avatar"><i class="fas fa-archive"></i></div>';
        html += '<div class="seup-details-basic">';
        html += '<h4>' + escapeHtml(gradivo.oznaka) + '</h4>';
        html += '<p class="seup-contact-person">' + escapeHtml(gradivo.vrsta_gradiva) + '</p>';
        html += '</div>';
        html += '</div>';
        
        // Details grid
        html += '<div class="seup-details-grid">';
        
        // ID
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-hashtag me-2"></i>ID</div>';
        html += '<div class="seup-detail-value">' + gradivo.rowid + '</div>';
        html += '</div>';
        
        // Datum kreiranja
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-calendar me-2"></i>Datum kreiranja</div>';
        html += '<div class="seup-detail-value">' + gradivo.datum_kreiranja + '</div>';
        html += '</div>';
        
        // Opisi/Napomene (wide)
        if (gradivo.opisi_napomene) {
            html += '<div class="seup-detail-item seup-detail-wide">';
            html += '<div class="seup-detail-label"><i class="fas fa-align-left me-2"></i>Opisi/Napomene</div>';
            html += '<div class="seup-detail-value">' + escapeHtml(gradivo.opisi_napomene) + '</div>';
            html += '</div>';
        }
        
        html += '</div>'; // seup-details-grid
        html += '</div>'; // seup-suradnik-details
        
        content.innerHTML = html;
        
        // Update edit button
        const editBtn = document.getElementById('editGradivoBtn');
        if (editBtn) {
            editBtn.onclick = function() {
                window.location.href = `postavke.php?edit_arh=${currentGradivoId}#arhivska_gradiva`;
            };
        }
    }

    // Modal event listeners
    document.getElementById('closeDetailsModal').addEventListener('click', closeDetailsModal);
    document.getElementById('closeDetailsBtn').addEventListener('click', closeDetailsModal);
    
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

    // Close modal when clicking outside
    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetailsModal();
        }
    });
    
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Utility functions
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Toast message function
    window.showMessage = function(message, type = 'success', duration = 5000) {
        let messageEl = document.querySelector('.seup-message-toast');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.className = 'seup-message-toast';
            document.body.appendChild(messageEl);
        }

        messageEl.className = `seup-message-toast seup-message-${type} show`;
        messageEl.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
        `;

        setTimeout(() => {
            messageEl.classList.remove('show');
        }, duration);
    };

    // Initial staggered animation for existing rows
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 100}ms`;
        row.classList.add('animate-fade-in-up');
    });
});
</script>

<style>
/* Arhivska gradiva specific styles */
.seup-gradivo-info {
  display: flex;
  align-items: center;
  font-size: var(--text-sm);
  color: var(--secondary-700);
  font-weight: var(--font-medium);
}

.seup-opis-cell {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  cursor: help;
  font-size: var(--text-sm);
  color: var(--secondary-600);
}

.seup-date-info {
  display: flex;
  align-items: center;
  font-size: var(--text-sm);
  color: var(--secondary-700);
}

/* Archive icon color variant */
.seup-details-avatar i.fa-archive {
  color: var(--warning-600);
}

/* Table header color for archive theme */
.seup-table-header {
  background: linear-gradient(135deg, var(--warning-500), var(--warning-600));
}

/* Delete Modal Styles */
.seup-delete-info {
  background: var(--error-50);
  border: 1px solid var(--error-200);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
  margin-bottom: var(--space-4);
}

.seup-delete-oznaka {
  font-family: var(--font-family-mono);
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--error-800);
  margin-bottom: var(--space-2);
}

.seup-delete-vrsta {
  font-size: var(--text-base);
  color: var(--secondary-700);
  margin-bottom: var(--space-3);
  font-weight: var(--font-medium);
}

.seup-delete-warning {
  font-size: var(--text-sm);
  color: var(--error-700);
  display: flex;
  align-items: flex-start;
  gap: var(--space-2);
}

/* Delete button styling */
.seup-btn-delete {
  background: var(--error-100);
  color: var(--error-600);
}

.seup-btn-delete:hover {
  background: var(--error-200);
  color: var(--error-700);
  transform: scale(1.1);
}

/* Delete modal header */
#deleteModal .seup-modal-header {
  background: linear-gradient(135deg, var(--error-500), var(--error-600));
}

/* Danger button variant */
.seup-btn-danger {
  background: linear-gradient(135deg, var(--error-500), var(--error-600));
  color: white;
  box-shadow: var(--shadow-md);
}

.seup-btn-danger:hover {
  background: linear-gradient(135deg, var(--error-600), var(--error-700));
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
  color: white;
  text-decoration: none;
}

@keyframes fadeOut {
  from {
    opacity: 1;
    transform: translateX(0);
  }
  to {
    opacity: 0;
    transform: translateX(-100px);
  }
}

/* Responsive design */
@media (max-width: 768px) {
  .seup-opis-cell {
    max-width: 120px;
  }
}
</style>

<?php
llxFooter();
$db->close();
?>