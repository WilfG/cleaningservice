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

// Load translation files
$langs->loadLangs(array("cleaningservice@cleaningservice"));

// Define constants for task status
define('TASK_STATUS_NEW', 0);
define('TASK_STATUS_IN_PROGRESS', 1);
define('TASK_STATUS_COMPLETED', 2);
define('TASK_STATUS_VALIDATED', 3);

// Parameters
$action = GETPOST('action', 'alpha');
$week = GETPOST('week', 'int') ? GETPOST('week', 'int') : date('W');
$year = GETPOST('year', 'int') ? GETPOST('year', 'int') : date('Y');
$mode = GETPOST('mode', 'alpha');
if (empty($mode)) {
    $mode = $user->admin ? 'all' : 'my';
}
$employee_id = GETPOST('employee_id', 'int');
if ($mode == 'employee' && empty($employee_id)) {
    $employee_id = $user->id;
}
// Security check
if (!$user->rights->cleaningservice->task->read) {
    accessforbidden();
}

// Initialize objects
$object = new CleaningServiceTask($db);
$form = new Form($db);
$extrafields = new ExtraFields($db);

// Handle task confirmation
if ($action == 'confirm' && !empty(GETPOST('id'))) {
    $task_id = GETPOST('id', 'int');

    // Check if user is assigned to this task
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
    $sql .= " WHERE fk_task = " . $task_id . " AND fk_user = " . $user->id;
    $resql = $db->query($sql);

    if ($resql && $db->num_rows($resql) > 0) {
        $object->fetch($task_id);
        $object->status = 1; // Set as confirmed
        $result = $object->update($user);
        if ($result > 0) {
            setEventMessages($langs->trans("PresenceConfirmed"), null);
        }
    } else {
        setEventMessages($langs->trans("NotAssignedToTask"), null, 'errors');
    }
}

// Handle actions
if ($action == 'complete' && !empty(GETPOST('id'))) {
    $task_id = GETPOST('id', 'int');
    $sql = "UPDATE " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
    $sql .= " SET status = " . TASK_STATUS_COMPLETED;
    $sql .= " WHERE fk_task = " . $task_id;
    $sql .= " AND fk_user = " . $user->id;

    $resql = $db->query($sql);
    if ($resql) {
        setEventMessages($langs->trans("TaskCompletedAwaitingValidation"), null);
    }
}



/*
 * View
 */

$title = $langs->trans("Schedule");
llxHeader('', $title);

print load_fiche_titre($title, '', 'cleaningservice@cleaningservice');

// Navigation
print '<div class="inline-block floatleft">';
print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?week=' . ($week - 1) . '&year=' . $year . '&mode=' . $mode . '">' . $langs->trans("PreviousWeek") . '</a>';
print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?week=' . ($week + 1) . '&year=' . $year . '&mode=' . $mode . '">' . $langs->trans("NextWeek") . '</a>';
print '<button class="butAction" id="print-timetable">' . $langs->trans("ExportHousekeepersWeekPDF") . '</button>';
print '</div>';

print '<div class="inline-block floatright marginbottom">';
print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter" id="formfilter" class="inline-block">';
print $langs->trans("View") . ': ';
print '<select class="flat" name="mode" id="mode" onchange="document.getElementById(\'formfilter\').submit();">';
if ($user->admin || $user->rights->cleaningservice->task->write) {
    print '<option value="all"' . ($mode == 'all' ? ' selected' : '') . '>' . $langs->trans("AllTasks") . '</option>';
}
print '<option value="my"' . ($mode == 'my' ? ' selected' : '') . '>' . $langs->trans("MyTasks") . '</option>';
if ($user->admin || $user->rights->cleaningservice->task->write) {
    print '<option value="employee"' . ($mode == 'employee' ? ' selected' : '') . '>' . $langs->trans("EmployeeTasks") . '</option>';
}
print '</select>';

if ($mode == 'employee' && ($user->admin || $user->rights->cleaningservice->task->write)) {
    print ' ' . $langs->trans("Employee") . ': ';
    print $form->select_dolusers($employee_id, 'employee_id', 1, null, 0, 'user-employee', '', 0, 0, 0, '', 0, '', '', 1);
    // Add submit button for employee selection
    print ' <input type="submit" class="button" value="' . $langs->trans("Filter") . '">';
}

// Keep the week and year in the form
print '<input type="hidden" name="week" value="' . $week . '">';
print '<input type="hidden" name="year" value="' . $year . '">';
print '</form>';
print '</div>';

print '<div class="clearboth"></div><br>';

// Get week dates
$dto = new DateTime();
$dto->setISODate($year, $week);
$week_start = $dto->format('Y-m-d');
$dto->modify('+6 days');
$week_end = $dto->format('Y-m-d');

// Build SQL query for tasks
$sql = "SELECT t.rowid, t.ref, t.label, t.date_start, t.date_end, t.fk_soc, t.status,";
$sql .= " s.nom as socname, ef.frequency as frequency, ta.status as task_status,";
$sql .= " GROUP_CONCAT(CONCAT(u.firstname, ' ', u.lastname) SEPARATOR ', ') as assigned_users,";
$sql .= " GROUP_CONCAT(u.rowid) as assigned_user_ids"; // Add this line
$sql .= " FROM " . MAIN_DB_PREFIX . "cleaningservice_task as t";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON t.fk_soc = s.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "cleaningservice_task_extrafields as ef ON t.rowid = ef.fk_object";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta ON t.rowid = ta.fk_task";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON ta.fk_user = u.rowid";
$sql .= " WHERE (";

// One-off tasks within current week
$sql .= " ((t.date_start <= '" . $db->idate($week_end) . "' AND t.date_end >= '" . $db->idate($week_start) . "')";
$sql .= " AND (ef.frequency = 'one-off' OR ef.frequency IS NULL))";
// Daily tasks within their date range
$sql .= " OR (ef.frequency = 'daily' AND t.date_start <= '" . $db->idate($week_end) . "'";
$sql .= " AND t.date_end >= '" . $db->idate($week_start) . "')";
// Weekly tasks within their date range
$sql .= " OR (ef.frequency = 'weekly' AND t.date_start <= '" . $db->idate($week_end) . "'";
$sql .= " AND t.date_end >= '" . $db->idate($week_start) . "')";
// Bimonthly tasks within their date range
// $sql .= " OR (ef.frequency = 'bimonthly' AND t.date_start <= '" . $db->idate($week_end) . "'";
// $sql .= " AND t.date_end >= '" . $db->idate($week_start) . "')";
// Monthly tasks within their date range
$sql .= " OR (ef.frequency = 'monthly' AND t.date_start <= '" . $db->idate($week_end) . "'";
$sql .= " AND t.date_end >= '" . $db->idate($week_start) . "')";
$sql .= ")";
// Add mode filters
if ($mode == 'my') {
    $sql .= " AND ta.fk_user = " . $user->id;
} elseif ($mode == 'employee' && !empty($employee_id)) {
    $sql .= " AND ta.fk_user = " . ((int) $employee_id);
    $sql .= " AND ta.status IS NOT NULL"; // Only show actually assigned tasks
} elseif (!$user->admin && !$user->rights->cleaningservice->task->write) {
    $sql .= " AND ta.fk_user = " . $user->id;
}

$sql .= " GROUP BY t.rowid, t.ref, t.label, t.date_start, t.date_end, t.fk_soc, s.nom, ef.frequency, t.status";
$sql .= " ORDER BY t.date_start";

// Display weekly calendar
print '<table class="noborder centpercent" id="timetable">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans("Time") . '</th>';
for ($i = 0; $i < 7; $i++) {
    $day = date('Y-m-d', strtotime($week_start . ' +' . $i . ' days'));
    print '<th>' . dol_print_date($day, 'day') . '</th>';
}
print '</tr>';

// Time slots (5 AM to 7 PM)
$time_slots = array();
for ($hour = 5; $hour <= 19; $hour++) {
    $time_slots[] = sprintf("%02d:00", $hour);
}

$result = $db->query($sql);
$tasks = array();
if ($result) {
    while ($obj = $db->fetch_object($result)) {
        $tasks[] = $obj;
    }
}

foreach ($time_slots as $time) {
    print '<tr>';
    print '<td>' . $time . '</td>';
    for ($i = 0; $i < 7; $i++) {
        print '<td class="center">';
        $current_day = date('Y-m-d', strtotime($week_start . ' +' . $i . ' days'));

        foreach ($tasks as $task) {
            // Task display logic
            $display_task = checkTaskDisplay($task, $current_day, $time);

            if ($display_task) {
                displayTask($task, $current_day, $time, $mode, $employee_id, $user, $week, $year);
            }
        }
        print '</td>';
    }
    print '</tr>';
}
print '</table>';

// Add styles
print '<style>
.task-item {
    position: relative;
    min-height: 30px;
    margin-bottom: 5px;
    padding: 5px;
    border-radius: 3px;
}

.task-new {
    background-color: #e3f2fd;
    border-left: 3px solid #2196f3;
}

.task-in-progress {
    background-color: #fff3e0;
    border-left: 3px solid #ff9800;
}

.task-late {
    background-color: #ffebee;
    border-left: 3px solid #f44336;
}

.task-awaiting-validation {
    background-color: #fff3e0;
    border-left: 3px solid #ff9800;
}

.task-validated {
    background-color: #e8f5e9;
    border-left: 3px solid #4caf50;
}

.task-continuation {
    height: 100%;
    min-height: 30px;
    opacity: 0.6;
}

.button-validated,
.button-awaiting {
    padding: 4px 8px;
    border-radius: 3px;
    cursor: not-allowed;
    opacity: 0.8;
    border: none;
    color: white;
}

.button-validated {
    background: #4caf50;
}

.button-awaiting {
    background: #ff9800;
}

td.center {
    vertical-align: top;
    height: 80px;
    padding: 5px !important;
}
</style>';

/**
 * Check if task should be displayed in current time slot
 */


function checkTaskDisplay($task, $current_day, $time)
{
    $display_task = false;
    $current_slot = strtotime($current_day . ' ' . $time);
    $task_start = strtotime($task->date_start);
    $task_end = strtotime($task->date_end);

    // Get hours from task start/end times
    $task_start_hour = date('H:i', $task_start);
    $task_end_hour = date('H:i', $task_end);
    $current_hour = date('H:i', $current_slot);

    // Check if current time slot is within task hours
    $is_within_hours = ($current_hour >= $task_start_hour && $current_hour < $task_end_hour);

    switch ($task->frequency) {
        case 'one-off':
            $display_task = ($current_slot >= $task_start && $current_slot < $task_end);
            break;

        case 'daily':
            if ($current_slot >= $task_start && $current_slot < $task_end) {
                $display_task = $is_within_hours;
            }
            break;

        case 'weekly':
            if ($current_slot >= $task_start && $current_slot < $task_end) {
                if (date('w', strtotime($current_day)) == date('w', $task_start)) {
                    $display_task = $is_within_hours;
                }
            }
            break;

        case 'monthly':
            if ($current_slot >= $task_start && $current_slot < $task_end) {
                $start_day = (int)date('j', $task_start);
                $current_day_num = (int)date('j', strtotime($current_day));
                if ($current_day_num == $start_day) {
                    $display_task = $is_within_hours;
                }
            }
            break;

        case 'bimonthly':
            if ($current_slot >= $task_start && $current_slot < $task_end) {
                $start_week = (int)date('W', $task_start);
                $current_week = (int)date('W', strtotime($current_day));
                $weeks_diff = $current_week - $start_week + (date('Y', strtotime($current_day)) - date('Y', $task_start)) * 52;
                if ($weeks_diff % 2 == 0 && $weeks_diff >= 0) {
                    $display_task = $is_within_hours;
                }
            }
            break;
    }
    return $display_task;
}

/**
 * Display task in calendar
 */
function displayTask($task, $current_day, $time, $mode, $employee_id, $user, $week, $year)
{
    global $langs;

    $current_datetime = strtotime($current_day . ' ' . $time);
    $task_start = strtotime($task->date_start);
    $task_end = strtotime($task->date_end);
    $now = time();

    // Determine task status and class
    if ($task->task_status == TASK_STATUS_VALIDATED) {
        $class = 'task-validated';
        $button_text = $langs->trans("Validated");
    } elseif ($task->task_status == TASK_STATUS_COMPLETED) {
        $class = 'task-awaiting-validation';
        $button_text = $langs->trans("AwaitingValidation");
    } elseif ($task_end < $now) {
        $class = 'task-late';
        $button_text = $langs->trans("CompleteTask");
    } elseif ($current_datetime >= $task_start) {
        $class = 'task-in-progress';
        $button_text = $langs->trans("CompleteTask");
    } else {
        $class = 'task-new';
        $button_text = $langs->trans("CompleteTask");
    }

    print '<div class="task-item ' . $class . '">';

    // Show details for one-off/daily tasks only in first slot, but always for weekly/bimonthly
    if (
        $task->frequency == 'weekly' || $task->frequency == 'bimonthly' ||
        abs($current_datetime - $task_start) < 3600
    ) {
        print '<a href="task_card.php?id=' . $task->rowid . '">';
        print $task->ref . ' - ' . $task->socname;
        print '</a><br>';
        print '<small>' . $task->assigned_users . '</small>';

        if ($task->frequency && $task->frequency != 'one-off') {
            print '<br><span class="badge">' . $langs->trans($task->frequency) . '</span>';
        }

        // Show action button only for assigned users
        if (($mode == 'my' || ($mode == 'employee' && $employee_id == $user->id)) && !$user->admin) {
            print '<div class="task-actions">';
            if ($task->task_status == TASK_STATUS_VALIDATED) {
                print '<button class="button-validated" disabled>' . $button_text . '</button>';
            } elseif ($task->task_status == TASK_STATUS_COMPLETED) {
                print '<button class="button-awaiting" disabled>' . $button_text . '</button>';
            } elseif ($current_datetime >= $task_start) {
                print '<a class="button" href="' . $_SERVER["PHP_SELF"] . '?action=complete&id=' . $task->rowid
                    . '&week=' . $week . '&year=' . $year . '&mode=' . $mode . '&token=' . newToken() . '">';
                print $button_text;
                print '</a>';
            }
            print '</div>';
        }
    } else {
        print '<div class="task-continuation"></div>';
    }

    print '</div>';
}

llxFooter();
$db->close();
