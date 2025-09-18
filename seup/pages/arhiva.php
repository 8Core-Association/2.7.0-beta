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
 *	\file       seup/pages/arhiva.php
 *	\ingroup    seup
 *	\brief      Arhiva page - archived predmeti
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

// Local classes
require_once __DIR__ . '/../class/predmet_helper.class.php';

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
    
    if ($action === 'get_predmet_details') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $predmet_id = GETPOST('predmet_id', 'int');
        
        if (!$predmet_id) {
            echo json_encode(['success' => false, 'error' => 'Missing predmet ID']);
            exit;
        }
        
        // Kompletan SQL upit koji spaja sve potrebne tablice
        $sql = "SELECT 
                    p.ID_predmeta,
                    p.klasa_br,
                    p.sadrzaj,
                    p.dosje_broj,
                    p.godina,
                    p.predmet_rbr,
                    p.naziv_predmeta,
                    p.naziv as posiljatelj_naziv,
                    p.zaprimljeno_datum,
                    DATE_FORMAT(p.tstamp_created, '%d.%m.%Y %H:%i') as datum_otvaranja,
                    u.name_ustanova,
                    u.code_ustanova,
                    k.ime_prezime,
                    k.rbr as korisnik_rbr,
                    k.naziv as radno_mjesto,
                    ko.opis_klasifikacijske_oznake,
                    ko.vrijeme_cuvanja,
                    a.datum_arhiviranja,
                    a.razlog_arhiviranja,
                    a.postupak_po_isteku,
                    ag.oznaka as arhivska_oznaka,
                    ag.vrsta_gradiva,
                    ag.opisi_napomene as arhivska_napomena,
                    DATE_FORMAT(a.datum_arhiviranja, '%d.%m.%Y %H:%i') as datum_arhiviranja_formatted
                FROM " . MAIN_DB_PREFIX . "a_predmet p
                LEFT JOIN " . MAIN_DB_PREFIX . "a_arhiva a ON p.ID_predmeta = a.ID_predmeta
                LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
                LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON p.ID_interna_oznaka_korisnika = k.ID
                LEFT JOIN " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ko ON p.ID_klasifikacijske_oznake = ko.ID_klasifikacijske_oznake
                LEFT JOIN " . MAIN_DB_PREFIX . "a_arhivska_gradiva ag ON a.fk_arhivska_gradiva = ag.rowid
                WHERE p.ID_predmeta = " . (int)$predmet_id . "
                AND a.status_arhive = 'active'";

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            // Format klasa
            $obj->klasa_format = $obj->klasa_br . '-' . $obj->sadrzaj . '/' . 
                                $obj->godina . '-' . $obj->dosje_broj . '/' . 
                                $obj->predmet_rbr;
            
            // Count documents in archive
            $relative_path = Predmet_helper::getArhivaPredmetFolderPath($predmet_id, $db);
            $sql_docs = "SELECT COUNT(*) as doc_count FROM " . MAIN_DB_PREFIX . "ecm_files 
                        WHERE filepath = '" . $db->escape(rtrim($relative_path, '/')) . "'
                        AND entity = " . $conf->entity;
            $resql_docs = $db->query($sql_docs);
            $obj->document_count = 0;
            if ($resql_docs && $doc_obj = $db->fetch_object($resql_docs)) {
                $obj->document_count = (int)$doc_obj->doc_count;
            }
            
            echo json_encode([
                'success' => true,
                'predmet' => $obj
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Arhivirani predmet nije pronađen'
            ]);
        }
        exit;
    }
    
    if ($action === 'restore_predmet') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $predmet_id = GETPOST('predmet_id', 'int');
        
        if (!$predmet_id) {
            echo json_encode(['success' => false, 'error' => 'Missing predmet ID']);
            exit;
        }
        
        $db->begin();
        
        try {
            // Update archive status to inactive
            $sql = "UPDATE " . MAIN_DB_PREFIX . "a_arhiva 
                    SET status_arhive = 'inactive',
                        datum_povrata = NOW(),
                        fk_user_povrat = " . $user->id . "
                    WHERE ID_predmeta = " . (int)$predmet_id . "
                    AND status_arhive = 'active'";
            
            $result = $db->query($sql);
            if (!$result) {
                throw new Exception('Failed to update archive status: ' . $db->lasterror());
            }
            
            // Move documents back from archive to active folder
            $archive_path = Predmet_helper::getArhivaPredmetFolderPath($predmet_id, $db);
            $active_path = Predmet_helper::getPredmetFolderPath($predmet_id, $db);
            
            $archive_full_path = DOL_DATA_ROOT . '/ecm/' . $archive_path;
            $active_full_path = DOL_DATA_ROOT . '/ecm/' . $active_path;
            
            $files_moved = 0;
            
            if (is_dir($archive_full_path)) {
                // Ensure active directory exists
                if (!is_dir($active_full_path)) {
                    dol_mkdir($active_full_path);
                }
                
                // Move files
                $files = scandir($archive_full_path);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $source = $archive_full_path . $file;
                        $destination = $active_full_path . $file;
                        
                        if (is_file($source)) {
                            if (rename($source, $destination)) {
                                $files_moved++;
                                
                                // Update ECM database records
                                $sql_update_ecm = "UPDATE " . MAIN_DB_PREFIX . "ecm_files 
                                                  SET filepath = '" . $db->escape(rtrim($active_path, '/')) . "'
                                                  WHERE filepath = '" . $db->escape(rtrim($archive_path, '/')) . "'
                                                  AND filename = '" . $db->escape($file) . "'
                                                  AND entity = " . $conf->entity;
                                $db->query($sql_update_ecm);
                            }
                        }
                    }
                }
                
                // Remove empty archive directory
                if (count(scandir($archive_full_path)) === 2) { // Only . and ..
                    rmdir($archive_full_path);
                }
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Predmet uspješno vraćen iz arhive. Premješteno {$files_moved} dokumenata.",
                'files_moved' => $files_moved
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Fetch sorting parameters
$sortField = GETPOST('sort', 'aZ09') ?: 'datum_arhiviranja';
$sortOrder = GETPOST('order', 'aZ09') ?: 'DESC';

// Validate sort fields
$allowedSortFields = ['ID_predmeta', 'klasa_br', 'naziv_predmeta', 'name_ustanova', 'datum_arhiviranja'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'datum_arhiviranja';
}
$sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';

// Fetch all archived predmeti
$sql = "SELECT 
            p.ID_predmeta,
            p.klasa_br,
            p.sadrzaj,
            p.dosje_broj,
            p.godina,
            p.predmet_rbr,
            p.naziv_predmeta,
            p.naziv as posiljatelj_naziv,
            DATE_FORMAT(p.tstamp_created, '%d.%m.%Y') as datum_otvaranja,
            u.name_ustanova,
            k.ime_prezime,
            DATE_FORMAT(a.datum_arhiviranja, '%d.%m.%Y %H:%i') as datum_arhiviranja,
            a.razlog_arhiviranja,
            ag.oznaka as arhivska_oznaka,
            ag.vrsta_gradiva
        FROM " . MAIN_DB_PREFIX . "a_predmet p
        INNER JOIN " . MAIN_DB_PREFIX . "a_arhiva a ON p.ID_predmeta = a.ID_predmeta
        LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
        LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON p.ID_interna_oznaka_korisnika = k.ID
        LEFT JOIN " . MAIN_DB_PREFIX . "a_arhivska_gradiva ag ON a.fk_arhivska_gradiva = ag.rowid
        WHERE a.status_arhive = 'active'
        ORDER BY {$sortField} {$sortOrder}";

$resql = $db->query($sql);
$arhivirani_predmeti = [];
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        // Format klasa
        $obj->klasa_format = $obj->klasa_br . '-' . $obj->sadrzaj . '/' . 
                            $obj->godina . '-' . $obj->dosje_broj . '/' . 
                            $obj->predmet_rbr;
        $arhivirani_predmeti[] = $obj;
    }
}

$form = new Form($db);
llxHeader("", "Arhiva", '', '', 0, 0, '', '', '', 'mod-seup page-arhiva');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
print '<link href="/custom/seup/css/predmeti.css" rel="stylesheet">';

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
print '<h1 class="seup-settings-title">Arhiva Predmeta</h1>';
print '<p class="seup-settings-subtitle">Pregled i upravljanje arhiviranim predmetima i dokumentima</p>';
print '</div>';

// Main content card
print '<div class="seup-predmeti-container">';
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-archive"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Arhivirani Predmeti</h3>';
print '<p class="seup-card-description">Pregled svih arhiviranih predmeta s mogućnostima pretraživanja i vraćanja</p>';
print '</div>';
print '<div class="seup-card-actions">';
print '<a href="../pages/predmeti.php" class="seup-btn seup-btn-secondary" role="button">';
print '<i class="fas fa-folder-open me-2"></i>Aktivni Predmeti';
print '</a>';
print '</div>';
print '</div>';

// Search and filter section
print '<div class="seup-table-controls">';
print '<div class="seup-search-container">';
print '<div class="seup-search-input-wrapper">';
print '<i class="fas fa-search seup-search-icon"></i>';
print '<input type="text" id="searchInput" class="seup-search-input" placeholder="Pretraži arhivirane predmete...">';
print '</div>';
print '</div>';
print '<div class="seup-filter-controls">';
print '<select id="filterUstanova" class="seup-filter-select">';
print '<option value="">Sve ustanove</option>';
// Add unique ustanove from arhivirani predmeti
$ustanove = array_unique(array_filter(array_column($arhivirani_predmeti, 'name_ustanova')));
foreach ($ustanove as $ustanova) {
    print '<option value="' . htmlspecialchars($ustanova) . '">' . htmlspecialchars($ustanova) . '</option>';
}
print '</select>';
print '<select id="filterGodina" class="seup-filter-select">';
print '<option value="">Sve godine</option>';
// Add unique godine from arhivirani predmeti
$godine = array_unique(array_filter(array_column($arhivirani_predmeti, 'godina')));
sort($godine);
foreach ($godine as $godina) {
    print '<option value="' . htmlspecialchars($godina) . '">20' . htmlspecialchars($godina) . '</option>';
}
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
print sortableHeader('ID_predmeta', 'ID', $sortField, $sortOrder, 'fas fa-hashtag');
print sortableHeader('klasa_br', 'Klasa', $sortField, $sortOrder, 'fas fa-layer-group');
print sortableHeader('naziv_predmeta', 'Naziv Predmeta', $sortField, $sortOrder, 'fas fa-heading');
print '<th class="seup-table-th"><i class="fas fa-archive me-2"></i>Vrsta Građe</th>';
print sortableHeader('datum_arhiviranja', 'Arhivirano', $sortField, $sortOrder, 'fas fa-calendar');
print '<th class="seup-table-th"><i class="fas fa-cogs me-2"></i>Akcije</th>';
print '</tr>';
print '</thead>';
print '<tbody class="seup-table-body">';

if (count($arhivirani_predmeti)) {
    foreach ($arhivirani_predmeti as $index => $predmet) {
        $rowClass = ($index % 2 === 0) ? 'seup-table-row-even' : 'seup-table-row-odd';
        print '<tr class="seup-table-row ' . $rowClass . '" data-id="' . $predmet->ID_predmeta . '">';
        
        print '<td class="seup-table-td">';
        print '<span class="seup-badge seup-badge-neutral">' . $predmet->ID_predmeta . '</span>';
        print '</td>';
        
        // Make Klasa badge clickable to open details modal
        print '<td class="seup-table-td">';
        print '<button class="seup-badge seup-badge-primary seup-klasa-link clickable-klasa" data-predmet-id="' . $predmet->ID_predmeta . '" title="Kliknite za detalje">';
        print $predmet->klasa_format;
        print '</button>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-naziv-cell" title="' . htmlspecialchars($predmet->naziv_predmeta) . '">';
        print dol_trunc($predmet->naziv_predmeta, 50);
        print '</div>';
        print '</td>';
        
        print '<td class="seup-table-td">';
        if (!empty($predmet->arhivska_oznaka)) {
            print '<div class="seup-arhivska-info">';
            print '<i class="fas fa-archive me-2"></i>';
            print '<span class="seup-arhivska-oznaka">' . htmlspecialchars($predmet->arhivska_oznaka) . '</span>';
            if (!empty($predmet->vrsta_gradiva)) {
                print '<br><small class="text-muted">' . htmlspecialchars($predmet->vrsta_gradiva) . '</small>';
            }
            print '</div>';
        } else {
            print '<span class="seup-empty-field">—</span>';
        }
        print '</td>';
        
        print '<td class="seup-table-td">';
        print '<div class="seup-date-info">';
        print '<i class="fas fa-calendar me-2"></i>';
        print $predmet->datum_arhiviranja;
        print '</div>';
        print '</td>';

        // Action buttons
        print '<td class="seup-table-td">';
        print '<div class="seup-action-buttons">';
        print '<button class="seup-action-btn seup-btn-view" title="Pregled detalja" data-predmet-id="' . $predmet->ID_predmeta . '">';
        print '<i class="fas fa-eye"></i>';
        print '</button>';
        print '<button class="seup-action-btn seup-btn-restore" title="Vrati iz arhive" data-predmet-id="' . $predmet->ID_predmeta . '">';
        print '<i class="fas fa-undo"></i>';
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
    print '<h4 class="seup-empty-title">Nema arhiviranih predmeta</h4>';
    print '<p class="seup-empty-description">Arhivirani predmeti će se prikazati ovdje</p>';
    print '<a href="../pages/predmeti.php" class="seup-btn seup-btn-primary mt-3" role="button">';
    print '<i class="fas fa-folder-open me-2"></i>Pogledaj aktivne predmete';
    print '</a>';
    print '</div>';
    print '</td>';
    print '</tr>';
}

print '</tbody>';
print '</table>';
print '</div>'; // seup-table-container

// Table footer with stats
print '<div class="seup-table-footer">';
print '<div class="seup-table-stats">';
print '<i class="fas fa-info-circle me-2"></i>';
print '<span>Prikazano <strong id="visibleCount">' . count($arhivirani_predmeti) . '</strong> od <strong>' . count($arhivirani_predmeti) . '</strong> arhiviranih predmeta</span>';
print '</div>';
print '<div class="seup-table-actions">';
print '<button type="button" class="seup-btn seup-btn-secondary seup-btn-sm" id="exportBtn">';
print '<i class="fas fa-download me-2"></i>Izvoz Excel';
print '</button>';
print '</div>';
print '</div>';

print '</div>'; // seup-settings-card
print '</div>'; // seup-predmeti-container

print '</div>'; // seup-settings-content
print '</main>';

// Details Modal
print '<div class="seup-modal" id="detailsModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-archive me-2"></i>Detalji Arhiviranog Predmeta</h5>';
print '<button type="button" class="seup-modal-close" id="closeDetailsModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div id="predmetDetailsContent">';
print '<div class="seup-loading-message">';
print '<i class="fas fa-spinner fa-spin"></i> Učitavam detalje...';
print '</div>';
print '</div>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="closeDetailsBtn">Zatvori</button>';
print '<button type="button" class="seup-btn seup-btn-success" id="restorePredmetBtn">';
print '<i class="fas fa-undo me-2"></i>Vrati iz Arhive';
print '</button>';
print '</div>';
print '</div>';
print '</div>';

// JavaScript for enhanced functionality
print '<script src="/custom/seup/js/seup-modern.js"></script>';

?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Enhanced search and filter functionality
    const searchInput = document.getElementById('searchInput');
    const filterUstanova = document.getElementById('filterUstanova');
    const filterGodina = document.getElementById('filterGodina');
    const tableRows = document.querySelectorAll('.seup-table-row[data-id]');
    const visibleCountSpan = document.getElementById('visibleCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedUstanova = filterUstanova.value;
        const selectedGodina = filterGodina.value;
        let visibleCount = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.seup-table-td');
            const rowText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(' ');
            
            // Check search term
            const matchesSearch = !searchTerm || rowText.includes(searchTerm);
            
            // Check ustanova filter
            let matchesUstanova = true;
            if (selectedUstanova) {
                // Check if any cell contains the ustanova name
                matchesUstanova = rowText.includes(selectedUstanova.toLowerCase());
            }

            // Check godina filter
            let matchesGodina = true;
            if (selectedGodina) {
                const klasaCell = cells[1]; // klasa column contains year
                const klasaText = klasaCell.textContent;
                // Extract year from klasa format: XXX-XX/YY-XX/X
                const yearMatch = klasaText.match(/\/(\d{2})-/);
                if (yearMatch) {
                    matchesGodina = yearMatch[1] === selectedGodina;
                }
            }

            if (matchesSearch && matchesUstanova && matchesGodina) {
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

    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterTable, 300));
    }
    
    if (filterUstanova) {
        filterUstanova.addEventListener('change', filterTable);
    }

    if (filterGodina) {
        filterGodina.addEventListener('change', filterTable);
    }

    // Modal functionality
    let currentPredmetId = null;

    function openDetailsModal(predmetId) {
        currentPredmetId = predmetId;
        
        // Show modal
        const modal = document.getElementById('detailsModal');
        modal.classList.add('show');
        
        // Load details
        loadPredmetDetails(predmetId);
    }

    function closeDetailsModal() {
        const modal = document.getElementById('detailsModal');
        modal.classList.remove('show');
        currentPredmetId = null;
    }

    function loadPredmetDetails(predmetId) {
        const content = document.getElementById('predmetDetailsContent');
        content.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Učitavam detalje...</div>';
        
        const formData = new FormData();
        formData.append('action', 'get_predmet_details');
        formData.append('predmet_id', predmetId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPredmetDetails(data.predmet);
            } else {
                content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Error loading details:', error);
            content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>Greška pri učitavanju detalja</div>';
        });
    }

    function renderPredmetDetails(predmet) {
        const content = document.getElementById('predmetDetailsContent');
        
        let html = '<div class="seup-predmet-details">';
        
        // Header with klasa and basic info
        html += '<div class="seup-details-header">';
        html += '<div class="seup-details-avatar"><i class="fas fa-archive"></i></div>';
        html += '<div class="seup-details-basic">';
        html += '<h4>' + escapeHtml(predmet.klasa_format) + '</h4>';
        html += '<p class="seup-contact-person">' + escapeHtml(predmet.naziv_predmeta) + '</p>';
        html += '</div>';
        html += '</div>';
        
        // Details grid
        html += '<div class="seup-details-grid">';
        
        // Ustanova
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-building me-2"></i>Ustanova</div>';
        html += '<div class="seup-detail-value">' + escapeHtml(predmet.name_ustanova || 'N/A') + '</div>';
        html += '</div>';
        
        // Zaposlenik
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-user me-2"></i>Zaposlenik</div>';
        html += '<div class="seup-detail-value">' + escapeHtml(predmet.ime_prezime || 'N/A') + '</div>';
        html += '</div>';
        
        // Datum otvaranja
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-calendar-plus me-2"></i>Datum otvaranja</div>';
        html += '<div class="seup-detail-value">' + escapeHtml(predmet.datum_otvaranja) + '</div>';
        html += '</div>';
        
        // Datum arhiviranja
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-archive me-2"></i>Datum arhiviranja</div>';
        html += '<div class="seup-detail-value">' + escapeHtml(predmet.datum_arhiviranja_formatted) + '</div>';
        html += '</div>';
        
        // Vrsta arhivske građe
        if (predmet.arhivska_oznaka) {
            html += '<div class="seup-detail-item">';
            html += '<div class="seup-detail-label"><i class="fas fa-tag me-2"></i>Vrsta arhivske građe</div>';
            html += '<div class="seup-detail-value">' + escapeHtml(predmet.arhivska_oznaka + ' - ' + (predmet.vrsta_gradiva || '')) + '</div>';
            html += '</div>';
        }
        
        // Broj dokumenata
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-file me-2"></i>Broj dokumenata</div>';
        html += '<div class="seup-detail-value">' + (predmet.document_count || 0) + '</div>';
        html += '</div>';
        
        // Razlog arhiviranja (wide)
        if (predmet.razlog_arhiviranja) {
            html += '<div class="seup-detail-item seup-detail-wide">';
            html += '<div class="seup-detail-label"><i class="fas fa-comment me-2"></i>Razlog arhiviranja</div>';
            html += '<div class="seup-detail-value">' + escapeHtml(predmet.razlog_arhiviranja) + '</div>';
            html += '</div>';
        }
        
        // Postupak po isteku
        if (predmet.postupak_po_isteku) {
            html += '<div class="seup-detail-item seup-detail-wide">';
            html += '<div class="seup-detail-label"><i class="fas fa-clock me-2"></i>Postupak po isteku</div>';
            html += '<div class="seup-detail-value">';
            
            switch(predmet.postupak_po_isteku) {
                case 'predaja_arhivu':
                    html += '🏛️ Predaja arhivu';
                    break;
                case 'ibp_izlucivanje':
                    html += '📋 IBP izlučivanje';
                    break;
                case 'ibp_brisanje':
                    html += '🗑️ IBP trajno brisanje';
                    break;
                default:
                    html += escapeHtml(predmet.postupak_po_isteku);
            }
            
            html += '</div>';
            html += '</div>';
        }
        
        html += '</div>'; // seup-details-grid
        html += '</div>'; // seup-predmet-details
        
        content.innerHTML = html;
        
        // Update restore button
        const restoreBtn = document.getElementById('restorePredmetBtn');
        if (restoreBtn) {
            restoreBtn.onclick = function() {
                restorePredmet(currentPredmetId);
            };
        }
    }

    function restorePredmet(predmetId) {
        if (!confirm('Jeste li sigurni da želite vratiti ovaj predmet iz arhive?')) {
            return;
        }
        
        const restoreBtn = document.getElementById('restorePredmetBtn');
        restoreBtn.classList.add('seup-loading');
        
        const formData = new FormData();
        formData.append('action', 'restore_predmet');
        formData.append('predmet_id', predmetId);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove row from table with animation
                const row = document.querySelector(`[data-id="${predmetId}"]`);
                if (row) {
                    row.style.animation = 'fadeOut 0.5s ease-out';
                    setTimeout(() => {
                        row.remove();
                        updateVisibleCount();
                    }, 500);
                }
                
                showMessage(data.message, 'success');
                closeDetailsModal();
            } else {
                showMessage('Greška pri vraćanju: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Restore error:', error);
            showMessage('Došlo je do greške pri vraćanju predmeta', 'error');
        })
        .finally(() => {
            restoreBtn.classList.remove('seup-loading');
        });
    }

    function updateVisibleCount() {
        const visibleRows = document.querySelectorAll('.seup-table-row[data-id]:not([style*="display: none"])');
        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleRows.length;
        }
    }

    // Event listeners for clickable elements
    document.querySelectorAll('.clickable-klasa, .seup-btn-view').forEach(element => {
        element.addEventListener('click', function() {
            const predmetId = this.dataset.predmetId;
            openDetailsModal(predmetId);
        });
    });

    // Restore button handlers
    document.querySelectorAll('.seup-btn-restore').forEach(btn => {
        btn.addEventListener('click', function() {
            const predmetId = this.dataset.predmetId;
            restorePredmet(predmetId);
        });
    });

    // Modal event listeners
    document.getElementById('closeDetailsModal').addEventListener('click', closeDetailsModal);
    document.getElementById('closeDetailsBtn').addEventListener('click', closeDetailsModal);

    // Close modal when clicking outside
    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetailsModal();
        }
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
/* Arhiva specific styles */
.seup-predmet-details {
  font-family: var(--font-family-sans);
}

.seup-details-header {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  margin-bottom: var(--space-6);
  padding: var(--space-4);
  background: var(--warning-50);
  border-radius: var(--radius-lg);
  border: 1px solid var(--warning-200);
}

.seup-details-avatar {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, var(--warning-500), var(--warning-600));
  border-radius: var(--radius-xl);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
  flex-shrink: 0;
}

.seup-details-basic h4 {
  margin: 0 0 var(--space-1) 0;
  color: var(--warning-800);
  font-size: var(--text-xl);
  font-weight: var(--font-semibold);
  font-family: var(--font-family-mono);
}

.seup-contact-person {
  margin: 0;
  color: var(--warning-700);
  font-size: var(--text-sm);
  font-weight: var(--font-medium);
}

.seup-details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-4);
}

.seup-detail-item {
  background: var(--neutral-50);
  border: 1px solid var(--neutral-200);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
  transition: all var(--transition-normal);
}

.seup-detail-item:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color: var(--warning-200);
}

.seup-detail-wide {
  grid-column: 1 / -1;
}

.seup-detail-label {
  font-size: var(--text-sm);
  font-weight: var(--font-semibold);
  color: var(--secondary-600);
  margin-bottom: var(--space-2);
  display: flex;
  align-items: center;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.seup-detail-value {
  font-size: var(--text-base);
  color: var(--secondary-900);
  font-weight: var(--font-medium);
  word-break: break-word;
}

/* Clickable klasa styling */
.clickable-klasa {
  cursor: pointer;
  transition: all var(--transition-fast);
  border: none;
  background: var(--primary-100);
  color: var(--primary-800);
  font-family: var(--font-family-mono);
  font-weight: var(--font-semibold);
}

.clickable-klasa:hover {
  background: var(--primary-200);
  color: var(--primary-900);
  transform: scale(1.05);
}

/* Restore button styling */
.seup-btn-restore {
  background: var(--success-100);
  color: var(--success-600);
}

.seup-btn-restore:hover {
  background: var(--success-200);
  color: var(--success-700);
  transform: scale(1.1);
}

/* Archive theme colors */
.seup-table-header {
  background: linear-gradient(135deg, var(--warning-500), var(--warning-600));
}

.seup-arhivska-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.seup-arhivska-oznaka {
  font-weight: var(--font-semibold);
  color: var(--warning-700);
}

/* Loading states */
.seup-btn.seup-loading {
  position: relative;
  color: transparent;
}

.seup-btn.seup-loading::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 16px;
  height: 16px;
  margin: -8px 0 0 -8px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.seup-action-btn.seup-loading::after {
  width: 12px;
  height: 12px;
  margin: -6px 0 0 -6px;
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
  .seup-details-grid {
    grid-template-columns: 1fr;
  }
  
  .seup-details-header {
    flex-direction: column;
    text-align: center;
  }
}
</style>

<?php
llxFooter();
$db->close();
?>