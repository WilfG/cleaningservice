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


require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once './class/cleaningservicetask.class.php';

// Parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$task_id = GETPOST('task_id', 'int');

// Security check - Only admin can validate
if (!$user->rights->cleaningservice->task->write) {
    accessforbidden();
}

// Initialize objects
$object = new CleaningServiceTask($db);
$form = new Form($db);

/*
 * Actions
 */
if ($action == 'validate' || $action == 'reject') {


    $task_id = GETPOST('task_id', 'int');
    $assigned_id = GETPOST('assigned_id', 'int');
    $comment = GETPOST('admin_comment', 'restricthtml');

    $status = ($action == 'validate') ? 3 : 4; // 3 = validated, 4 = rejected
    // $status = ($action == 'validate') ? 3 : 4;

    $sql = "UPDATE " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
    $sql .= " SET status = " . $status;
    $sql .= ", admin_comment = '" . $db->escape($comment) . "'";
    $sql .= ", validation_date = '" . $db->idate(dol_now()) . "'";
    $sql .= ", fk_user_validation = " . $user->id;
    $sql .= " WHERE rowid = " . $assigned_id;

    $resql = $db->query($sql);
    if ($resql) {
        setEventMessages($langs->trans($action == 'validate' ? "TaskValidated" : "TaskRejected"), null);
        if ($status == 3 && !empty($conf->global->CLEANINGSERVICE_AUTO_INVOICE)) {
            //    die('pp');
            // Redirect to invoice creation
            header("Location: task_invoice.php?action=createinvoice&id=" . $task_id . "&token=" . newToken());
            exit;
        }
    } else {
        setEventMessages($db->lasterror(), null, 'errors');
    }
}

/*
 * View
 */
$title = $langs->trans("TaskValidation");
llxHeader('', $title);

print load_fiche_titre($langs->trans("TaskValidation"), '', 'cleaningservice@cleaningservice');

// List completed tasks awaiting validation
$sql = "SELECT t.rowid as task_id, t.ref, t.label, t.date_start, t.date_end,";
$sql .= " ta.rowid as assigned_id, ta.signature_date, ta.signature_data,";
$sql .= " ta.note_private, ta.note_public, ta.status,";
$sql .= " u.firstname, u.lastname, s.nom as customer_name,";
$sql .= " (SELECT SUM(hours_worked) FROM " . MAIN_DB_PREFIX . "cleaningservice_task_timesheet";
$sql .= " WHERE fk_task_assigned = ta.rowid) as total_hours_worked,";
$sql .= " (SELECT GROUP_CONCAT(CONCAT(work_date, ':', hours_worked) SEPARATOR '\n')";
$sql .= " FROM " . MAIN_DB_PREFIX . "cleaningservice_task_timesheet";
$sql .= " WHERE fk_task_assigned = ta.rowid) as timesheet_details";
$sql .= " FROM " . MAIN_DB_PREFIX . "cleaningservice_task as t";
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta ON t.rowid = ta.fk_task";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON ta.fk_user = u.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON t.fk_soc = s.rowid";
$sql .= " WHERE ta.status = 2"; // Only completed tasks
$sql .= " ORDER BY t.date_start DESC";


$resql = $db->query($sql);
if ($resql) {
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>' . $langs->trans("Task") . '</th>';
    print '<th>' . $langs->trans("Employee") . '</th>';
    print '<th>' . $langs->trans("Customer") . '</th>';
    print '<th>' . $langs->trans("Date") . '</th>';
    print '<th>' . $langs->trans("HoursWorked") . '</th>';
    print '<th>' . $langs->trans("TimesheetDetails") . '</th>';
    print '<th>' . $langs->trans("Signature") . '</th>';
    print '<th>' . $langs->trans("Action") . '</th>';
    print '</tr>';

    while ($obj = $db->fetch_object($resql)) {
        print '<tr class="oddeven">';
        print '<td>' . $obj->ref . ' - ' . $obj->label . '</td>';
        print '<td>' . $obj->firstname . ' ' . $obj->lastname . '</td>';
        print '<td>' . $obj->customer_name . '</td>';
        print '<td>' . dol_print_date($obj->date_start, 'dayhour') . '</td>';
        print '<td>' . price2num($obj->total_hours_worked) . '</td>';
        print '<td>';
        if (!empty($obj->timesheet_details)) {
            $details = explode("\n", $obj->timesheet_details);
            foreach ($details as $detail) {
                list($date, $hours) = explode(':', $detail);
                print dol_print_date($date, 'day') . ': ' . price2num($hours) . ' ' . $langs->trans("Hours") . '<br>';
            }
        }
        print '</td>';
        print '<td><img src="' . $obj->signature_data . '" style="max-width:100px;"></td>';
        print '<td>';
        print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="task_id" value="' . $obj->task_id . '">';
        print '<input type="hidden" name="assigned_id" value="' . $obj->assigned_id . '">';
        print '<textarea name="admin_comment" placeholder="' . $langs->trans("Comment") . '" rows="2"></textarea><br>';
        print '<button type="submit" name="action" value="validate" class="button">' . $langs->trans("Validate") . '</button> ';
        print '<button type="submit" name="action" value="reject" class="button">' . $langs->trans("Reject") . '</button>';
        print '</form>';
        print '</td>';
        print '</tr>';
    }
    print '</table>';
}

llxFooter();
$db->close();
