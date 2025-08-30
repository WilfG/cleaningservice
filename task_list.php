<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
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
// Try main.inc.php using relative path
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

require_once DOL_DOCUMENT_ROOT . '/custom/cleaningservice/class/cleaningservicetask.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Load translation files
$langs->loadLangs(array("cleaningservice@cleaningservice"));
// var_dump($langs->trans("DateStart"));
// Get parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'tasklist';

// Security check
if (!$user->rights->cleaningservice->task->read) {
    accessforbidden();
}

// Initialize technical objects
$object = new CleaningServiceTask($db);
$hookmanager->initHooks(array('cleaningservicetasklist'));

/*
 * Actions
 */

if ($action == 'delete' && $user->rights->cleaningservice->task->delete) {
    // die;
    $object->fetch(GETPOST('id', 'int'));
    $result = $object->delete($user);
    if ($result > 0) {
        header('Location: ' . $_SERVER["PHP_SELF"]);
        exit;
    }
}

/*
 * View
 */

$title = $langs->trans("Tasks");
$help_url = '';

llxHeader('', $title, $help_url);

// Build and execute select
$sql = "SELECT t.rowid, t.ref, t.label, t.date_start, t.date_end, t.status, s.nom as socname, ";
$sql .= "(SELECT MAX(ta.last_visit_date) FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta WHERE ta.fk_task = t.rowid) as last_visit_date ";
$sql .= "FROM " . MAIN_DB_PREFIX . "cleaningservice_task as t ";
$sql .= "LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON t.fk_soc = s.rowid ";

// Only show assigned tasks for non-admins
if (!$user->admin) {
    $sql .= "INNER JOIN " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta ON ta.fk_task = t.rowid ";
    $sql .= "WHERE ta.fk_user = " . ((int) $user->id) . " ";
}

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);
$sql .= $hookmanager->resPrint;

$sql .= $db->order("t.date_start", "DESC");

// Count total nb of records
$nbtotalofrecords = '';
$result = $db->query($sql);
if ($result) {
    $nbtotalofrecords = $db->num_rows($result);
}

// List of tasks
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'task', 0, '', '', $limit, 0, 0, 1);

print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="list">';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

// Fields title
print '<tr class="liste_titre">';
print '<th class="liste_titre">' . $langs->trans("Ref") . '</th>';
print '<th class="liste_titre">' . $langs->trans("Label") . '</th>';
print '<th class="liste_titre">' . $langs->trans("Customer") . '</th>';
print '<th class="liste_titre center">' . $langs->trans("DateStart") . '</th>';
print '<th class="liste_titre center">' . $langs->trans("DateEnd") . '</th>';
print '<th class="liste_titre center">' . $langs->trans("LastVisit") . '</th>';
// print '<th class="liste_titre center">' . $langs->trans("Status") . '</th>';
print '<th class="liste_titre center"> Actions </th>';
print '</tr>';

$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $i = 0;
    if ($num) {
        while ($i < $num) {
            $obj = $db->fetch_object($result);

            print '<tr class="oddeven">';
            print '<td><a href="task_card.php?id=' . $obj->rowid . '">' . $obj->ref . '</a></td>';
            print '<td>' . $obj->label . '</td>';
            print '<td>' . $obj->socname . '</td>';
            print '<td class="center">' . dol_print_date($db->jdate($obj->date_start), 'dayhour') . '</td>';
            print '<td class="center">' . dol_print_date($db->jdate($obj->date_end), 'dayhour') . '</td>';
            print '<td class="center">' . ($obj->last_visit_date ? dol_print_date($db->jdate($obj->last_visit_date), 'dayhour') : $langs->trans("Never")) . '</td>';
            // print '<td class="center">' . $object->getLibStatut($obj->status, 3) . '</td>';
            print '<td class="center nowrap">';
            print '<a class="deletefielda" href="task_list.php?id=' . $obj->rowid . '&action=delete">' . img_delete() . '</a>';
            // if ($user->rights->cleaningservice->task->write) {
            //     print '<a class="editfielda" href="task_card.php?id=' . $obj->rowid . '&action=edit">' . img_edit() . '</a>';
            // }
            // if ($user->rights->cleaningservice->task->delete) {
            //     print '<a class="marginleftonly" href="' . $_SERVER["PHP_SELF"] . '?id=' . $obj->rowid . '&action=delete&token=' . newToken() . '">' . img_delete() . '</a>';
            // }
            print '</td>';
            print '</tr>';
            $i++;
        }
    } else {
        print '<tr><td colspan="7"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
    }
}

print '</table>';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
