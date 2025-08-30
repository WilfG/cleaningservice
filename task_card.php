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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
require_once './class/cleaningservicetask.class.php';


// Load translation files
$langs->loadLangs(array("cleaningservice@cleaningservice"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'taskcard';

// Initialize objects
$object = new CleaningServiceTask($db);
$extrafields = new ExtraFields($db);
$form = new Form($db);
$formcompany = new FormCompany($db);
$formfile = new FormFile($db);
$notify = new Notify($db);  // Add this line

// Initialize technical objects
$hookmanager->initHooks(array('cleaningservicetask', 'globalcard'));

// Set the proper element for extrafields
$object->element = 'cleaningservice_task';

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Security check
if (!$user->rights->cleaningservice->task->read) {
    accessforbidden();
}

// Additional admin check for creation
if ($action == 'create' || $action == 'add') {
    if (!$user->admin && !$user->rights->cleaningservice->task->create) {
        accessforbidden();
    }
}

/*
 * Actions
 */

if ($cancel) {
    header("Location: task_list.php");
    exit;
}

// Create task
if ($action == 'add' && $user->rights->cleaningservice->task->create) {
    $object->label = GETPOST('label', 'alpha');
    $object->description = GETPOST('description', 'restricthtml');
    $object->date_start = dol_mktime(
        GETPOST('datestarthour', 'int'),
        GETPOST('datestartmin', 'int'),
        0,
        GETPOST('datestartmonth', 'int'),
        GETPOST('datestartday', 'int'),
        GETPOST('datestartyear', 'int')
    );
    $object->date_end = dol_mktime(
        GETPOST('dateendhour', 'int'),
        GETPOST('dateendmin', 'int'),
        0,
        GETPOST('dateendmonth', 'int'),
        GETPOST('dateendday', 'int'),
        GETPOST('dateendyear', 'int')
    );
    $object->fk_soc = GETPOST('fk_soc', 'int');

    if (empty($object->ref)) {
        $object->ref = $object->getNextNumRef();
    }
    $extrafields->setOptionalsFromPost(null, $object);

    // Handle bimonthly frequency
    if (!empty($object->array_options['options_frequency']) && $object->array_options['options_frequency'] != 'one-off') {
        // Get assigned users before creating clones
        $assigned_users = GETPOST('housekeepers', 'array');



        // Store original values
        $original_start = $object->date_start;
        $original_end = $object->date_end;
        $original_ref = $object->ref;
        $original_label = $object->label;

        $end_hour = GETPOST('dateendhour', 'int');
        $end_min = GETPOST('dateendmin', 'int');

        // Calculate interval based on frequency
        switch ($object->array_options['options_frequency']) {
            case 'daily':
                $interval = 1;
                $interval_unit = 'd';
                break;
            case 'weekly':
                $interval = 1;
                $interval_unit = 'w';
                break;
            case 'bimonthly':
                $interval = 2;
                $interval_unit = 'w';
                break;
            case 'monthly':
                $interval = 1;
                $interval_unit = 'm';
                break;
            default:
                $interval = 0;
                $interval_unit = 'd';
        }
        
        $total_period = $original_end - $original_start;
        $interval_seconds = 0;

        switch ($interval_unit) {
            case 'd':
                $interval_seconds = $interval * 24 * 3600;
                break;
            case 'w':
                $interval_seconds = $interval * 7 * 24 * 3600;
                break;
            case 'm':
                // Approximate month length
                $interval_seconds = $interval * 30 * 24 * 3600;
                break;
        }

        $num_tasks = ceil($total_period / $interval_seconds);
        // var_dump($num_tasks, $total_period, $interval_seconds, $interval_unit);
        // die;

        // Create recurring tasks
        for ($i = 0; $i < $num_tasks; $i++) {
            $new_task = clone $object;

            // Set ref and label
            if ($i == 0) {
                $new_task->ref = $original_ref;
                $new_task->label = $original_label . ' ' . $langs->trans("TaskOriginal");
            } else {
                $new_task->ref = $object->getNextNumRef();
                $new_task->label = $original_label . ' ' . $langs->trans("TaskClone", $i);
            }

            // Set dates and frequency

             // Calculate the date for this instance while keeping hours from original
            $base_date = dol_time_plus_duree($original_start, $i * $interval, $interval_unit);

            // Extract hours and minutes from original times
            $start_hour = dol_print_date($original_start, '%H');
            $start_min = dol_print_date($original_start, '%M');
            $end_hour = dol_print_date($original_end, '%H');
            $end_min = dol_print_date($original_end, '%M');

            // Set start date with original start hour
            $new_task->date_start = dol_mktime(
                $start_hour,
                $start_min,
                0,
                dol_print_date($base_date, '%m'),
                dol_print_date($base_date, '%d'),
                dol_print_date($base_date, '%Y')
            );

            // Build date_end with the same date as start, but with selected end time
            $new_task->date_end = dol_mktime(
                $end_hour,
                $end_min,
                0,
                dol_print_date($base_date, '%m'),
                dol_print_date($base_date, '%d'),
                dol_print_date($base_date, '%Y')
            );
            $new_task->array_options['options_frequency'] = 'one-off';

            if ($i == 0 && $action == 'update') {
                $result = $new_task->update($user);
            } else {
                $result = $new_task->create($user);
            }

            if ($result > 0) {
                // Assign users to the new task
                foreach ($assigned_users as $user_id) {
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
                    $sql .= " (fk_task, fk_user, date_creation, status) VALUES ";
                    $sql .= "(" . $result . ", " . $user_id . ", " . dol_now() . ", 0)";
                    if (!$db->query($sql)) {
                        setEventMessages($db->lasterror(), null, 'errors');
                    }
                }
            } else {
                setEventMessages($new_task->error, $new_task->errors, 'errors');
                break;
            }
        }

        if ($result > 0) {
             setEventMessages($langs->trans("RecurringTasksCreated"), null);
             }

        header("Location: task_list.php");
        exit;
    }

    $id = $object->create($user);
    if ($id > 0) {
        // Save extrafields
        $result = $object->insertExtraFields();

        // Fetch company info for notification
        $company = new Societe($db);
        if ($object->fk_soc > 0) {
            $company->fetch($object->fk_soc);
        }
        // Assign housekeepers
        $housekeepers = GETPOST('housekeepers', 'array');
        if (is_array($housekeepers) && !empty($housekeepers)) {
            foreach ($housekeepers as $housekeeper_id) {
                // Insert assignment
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
                $sql .= " (fk_task, fk_user, date_creation, fk_user_creat)";
                $sql .= " VALUES (" . $id . ", " . $housekeeper_id . ", '" . $db->idate(dol_now()) . "', " . $user->id . ")";
                $resql = $db->query($sql);

                if ($resql) {
                    // Prepare notification
                    $notify->trackid = $object->ref;
                    $notify->context = 'cleaningservice_task';

                    // Build notification message
                    $message = $langs->transnoentities('TaskAssignmentNotification', $object->ref);
                    $message .= "\n\n" . $langs->transnoentities('Customer') . ': ' . $company->name;
                    $message .= "\n" . $langs->transnoentities('DateStart') . ': ' . dol_print_date($object->date_start, 'dayhourtext');
                    $message .= "\n" . $langs->transnoentities('DateEnd') . ': ' . dol_print_date($object->date_end, 'dayhourtext');
                    if (!empty($object->array_options['options_frequency'])) {
                        $message .= "\n" . $langs->transnoentities('Frequency') . ': ' . $langs->trans($object->array_options['options_frequency']);
                    }
                    $message .= "\n\n" . $langs->transnoentities('Description') . ': ' . $object->description;

                    // Send notification - using correct method
                    $result = $notify->send(
                        'USER_NOTIFY', // Type of notification
                        $housekeeper_id, // ID of the recipient
                        $message, // Message content
                        array(), // Array of files to attach
                        array(), // Array of special parameters
                        'cleaningservice_task' // Module name
                    );

                    if ($result > 0) {
                        dol_syslog("Notification sent to housekeeper ID: " . $housekeeper_id, LOG_DEBUG);
                    }
                    // else {
                    //     dol_syslog("Error sending notification to housekeeper ID: " . $housekeeper_id, LOG_ERR);
                    //     setEventMessages($langs->trans("ErrorSendingNotification"), null, 'errors');
                    // }
                } else {
                    setEventMessages($db->lasterror(), null, 'errors');
                }
            }
        }




        header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = 'create';
    }
}


if ($action == 'delete' && $user->rights->cleaningservice->task->delete) {
    if ($confirm == 'yes') {
        $db->begin();

        // Delete task assignments first
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
        $sql .= " WHERE fk_task = " . $id;
        $resql1 = $db->query($sql);

        // Delete task timesheets
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "cleaningservice_task_timesheet";
        $sql .= " WHERE fk_task_assigned IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned WHERE fk_task = " . $id . ")";
        $resql2 = $db->query($sql);

        // Delete the task
        $result = $object->delete($user);

        if ($resql1 && $resql2 && $result > 0) {
            $db->commit();
            header("Location: task_list.php");
            exit;
        } else {
            $db->rollback();
            setEventMessages($object->error, $object->errors, 'errors');
        }
    } else {
        $formconfirm = $form->formconfirm(
            $_SERVER["PHP_SELF"] . '?id=' . $id,
            $langs->trans('DeleteTask'),
            $langs->trans('ConfirmDeleteTask'),
            'confirm_delete',
            '',
            0,
            1
        );
        print $formconfirm;
    }
}

if ($action == 'update' && $user->rights->cleaningservice->task->write) {
    $object->label = GETPOST('label', 'alpha');
    $object->description = GETPOST('description', 'restricthtml');
    $object->date_start = dol_mktime(
        GETPOST('datestarthour', 'int'),
        GETPOST('datestartmin', 'int'),
        0,
        GETPOST('datestartmonth', 'int'),
        GETPOST('datestartday', 'int'),
        GETPOST('datestartyear', 'int')
    );
    $object->date_end = dol_mktime(
        GETPOST('dateendhour', 'int'),
        GETPOST('dateendmin', 'int'),
        0,
        GETPOST('dateendmonth', 'int'),
        GETPOST('dateendday', 'int'),
        GETPOST('dateendyear', 'int')
    );
    $object->fk_soc = GETPOST('fk_soc', 'int');
    $object->id = $id;
    $object->rowid = $id;
    $result = $object->update($user);
    if ($result > 0) {
        // Update extrafields
        $result = $object->insertExtraFields();

        $housekeepers = GETPOST('housekeepers', 'array');
        if (is_array($housekeepers)) {
            // die(var_dump($housekeepers));
            // Remove old assignments
            $db->query("DELETE FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned WHERE fk_task = " . (int)$object->id);
            // Insert new assignments
            foreach ($housekeepers as $housekeeper_id) {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cleaningservice_task_assigned (fk_task, fk_user, date_creation, fk_user_creat, status) ";
                $sql .= "VALUES (" . (int)$object->id . ", " . (int)$housekeeper_id . ", '" . $db->idate(dol_now()) . "', " . (int)$user->id . ", 0)";
                $db->query($sql);
            }
        }
        header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $object->id);
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}
/*
 * View
 */

$title = $langs->trans("Task");
$help_url = '';
llxHeader('', $title, $help_url);

// Create mode
if ($action == 'create' && $user->rights->cleaningservice->task->create) {
    print load_fiche_titre($langs->trans("NewTask"), '', 'cleaningservice@cleaningservice');

    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';

    print dol_get_fiche_head(array(), '');

    print '<table class="border centpercent tableforfieldcreate">';

    // Label
    print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Label") . '</td><td>';
    print '<input class="flat" type="text" size="36" name="label" value="' . dol_escape_htmltag(GETPOST('label')) . '">';
    print '</td></tr>';

    // Description
    print '<tr><td class="tdtop">' . $langs->trans("Description") . '</td><td>';
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
    $doleditor = new DolEditor('description', GETPOST('description', 'restricthtml'), '', 200, 'dolibarr_notes', '', false, true, true, ROWS_8, '90%');
    $doleditor->Create();
    print '</td></tr>';

    // Date Start
    print '<tr><td>' . $langs->trans("DateStart") . '</td><td>';
    print $form->selectDate('', 'datestart', 1, 1, 0, '', 1, 1);
    print '</td></tr>';

    // Date End
    print '<tr><td>' . $langs->trans("DateEnd") . '</td><td>';
    print $form->selectDate('', 'dateend', 1, 1, 0, '', 1, 1);
    print '</td></tr>';

    // Customer
    print '<tr><td>' . $langs->trans("Customer") . '</td><td>';
    print $form->select_company('', 'fk_soc', '', 1);
    print '</td></tr>';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    // Housekeepers
    print '<tr><td class="titlefield fieldrequired">' . $langs->trans("Housekeepers") . '</td><td>';
    $sql = "SELECT u.rowid, u.firstname, u.lastname";
    $sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
    $sql .= " WHERE u.statut = 1"; // Only active users
    $sql .= " AND u.employee = 1"; // Only employees
    $sql .= " AND u.admin = 0"; // Only employees
    $sql .= " ORDER BY u.lastname";

    $resql = $db->query($sql);
    if ($resql) {
        print '<select class="flat minwidth300" multiple name="housekeepers[]" id="housekeepers" required>';
        while ($obj = $db->fetch_object($resql)) {
            print '<option value="' . $obj->rowid . '">' . $obj->lastname . ' ' . $obj->firstname . '</option>';
        }
        print '</select>';
    }
    print '</td></tr>';

    print '</table>';

    print dol_get_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" name="add" value="' . $langs->trans("Create") . '">';
    print '&nbsp; ';
    print '<input type="submit" class="button button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
    print '</div>';

    print '</form>';
} else if ($id > 0 || $ref) {
    // Show task
    $result = $object->fetch($id);
    if ($result > 0) {
        // Load extrafields
        $object->fetch_optionals();

        print load_fiche_titre($langs->trans("Task") . ' ' . $object->ref, '', 'cleaningservice@cleaningservice');

        // Add action buttons
        print '<div class="tabsAction">';
        // if ($user->rights->cleaningservice->task->write) {
        //     print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit&token=' . newToken() . '">' . $langs->trans('Modify') . '</a>';
        // }
        // if ($user->rights->cleaningservice->task->delete) {
        //     print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&token=' . newToken() . '">' . $langs->trans('Delete') . '</a>';
        // }
        // if ($user->rights->cleaningservice->task->read) {
        //     print '<a class="butAction" href="task_hours.php?id=' . $object->id . '">' . $langs->trans('TaskHours') . '</a>';
        // }


        // if ($user->admin) {
        //     print '<a class="butAction" href="task_invoice.php?id=' . $object->id . '">' . $langs->trans('CreateInvoice') . '</a>';
        // }

        // if ($object->fk_soc > 0 && $user->rights->cleaningservice->task->read) {
        //     print '<a class="butAction" href="' . dol_buildpath('/custom/cleaningservice/hours_report.php', 1) . '?action=generate&socid=' . $object->fk_soc . '">';
        //     print $langs->trans('HoursSummary');
        //     print '</a>';
        // }

        // Add timesheet button in the action buttons section
        // Only show timesheet button if user is assigned to this task
        $sql = "SELECT rowid, status FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
        $sql .= " WHERE fk_task = " . $id . " AND fk_user = " . $user->id;
        $assigned = $db->query($sql) && $db->num_rows($result) > 0;

        $task_completed = $db->fetch_object($assigned)->status;
        if ($assigned || $user->admin) {
            // die($task_completed);
            if ($task_completed != 2 && !$user->admin) {
                print '<a class="butAction" href="task_timesheet.php?id=' . $object->id . '">';
                print $langs->trans("TimeEntry");
                print '</a>';
            }
        }

        if ($user->rights->cleaningservice->task->write) {
            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit&token=' . newToken() . '">' . $langs->trans('Modify') . '</a>';
        }
        print '</div>';

        print '<table class="border centpercent tableforfield">';

        // Ref
        print '<tr><td class="titlefield">' . $langs->trans("Ref") . '</td><td>';
        print $object->ref;
        print '</td></tr>';

        // Label
        print '<tr><td>' . $langs->trans("Label") . '</td><td>';
        print $object->label;
        print '</td></tr>';

        // Description
        print '<tr><td>' . $langs->trans("Description") . '</td><td>';
        print nl2br($object->description);
        print '</td></tr>';

        // Customer
        print '<tr><td>' . $langs->trans("Customer") . '</td><td>';
        $company = new Societe($db);
        if ($object->fk_soc > 0) {
            $company->fetch($object->fk_soc);
            print $company->getNomUrl(1);
        }
        print '</td></tr>';

        // Start Date
        print '<tr><td>' . $langs->trans("DateStart") . '</td><td>';
        print dol_print_date($object->date_start, 'dayhour');
        print '</td></tr>';

        // End Date
        print '<tr><td>' . $langs->trans("DateEnd") . '</td><td>';
        print dol_print_date($object->date_end, 'dayhour');
        print '</td></tr>';

        // Add Frequency display
        print '<tr><td>' . $langs->trans("Frequency") . '</td><td>';
        if (!empty($object->array_options['options_frequency'])) {
            print $langs->trans($object->array_options['options_frequency']);
        } else {
            print $langs->trans("OneOff");
        }
        print '</td></tr>';

// Display Extrafields Values
        print '<tr><td colspan="2"><hr></td></tr>'; // Separator
        print '<tr><td colspan="2"><strong>' . $langs->trans("PropertyDetails") . '</strong></td></tr>';

        // Integer fields
        $int_fields = array(
            'matelas_double' => 'MatelasDouble',
            'matelas_simple' => 'MatelasSimple',
            'duvet_double' => 'DuvetDouble',
            'duvet_simple' => 'DuvetSimple',
            'oreiller' => 'Oreiller',
            'canapes_lit' => 'CanapesLit',
            'salles_bain' => 'SallesDeBain',
            'salles_douche' => 'SallesDeDouche'
        );

        foreach ($int_fields as $field => $label) {
            if (isset($object->array_options['options_' . $field])) {
                print '<tr><td>' . $langs->trans($label) . '</td><td>';
                print $object->array_options['options_' . $field];
                print '</td></tr>';
            }
        }

        // Varchar fields
        if (isset($object->array_options['options_code_boite_cle'])) {
            print '<tr><td>' . $langs->trans("CodeBoiteCle") . '</td><td>';
            print $object->array_options['options_code_boite_cle'];
            print '</td></tr>';
        }

        if (isset($object->array_options['options_code_batiment'])) {
            print '<tr><td>' . $langs->trans("CodeBatiment") . '</td><td>';
            print $object->array_options['options_code_batiment'];
            print '</td></tr>';
        }

        // Text field
        if (isset($object->array_options['options_informations_plus'])) {
            print '<tr><td>' . $langs->trans("InformationsPlus") . '</td><td>';
            print nl2br($object->array_options['options_informations_plus']);
            print '</td></tr>';
        }

        print '</table>';

        // Assigned Housekeepers
        print load_fiche_titre($langs->trans("AssignedHousekeepers"), '', '');

        $sql = "SELECT u.rowid, u.firstname, u.lastname, ta.last_visit_date";
        $sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta ON ta.fk_user = u.rowid";
        $sql .= " WHERE ta.fk_task = " . $object->id;

        $resql = $db->query($sql);
        if ($resql) {
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<th>' . $langs->trans("Name") . '</th>';
            print '<th>' . $langs->trans("LastVisit") . '</th>';
            print '</tr>';

            $num = $db->num_rows($resql);
            if ($num) {
                while ($obj = $db->fetch_object($resql)) {
                    print '<tr class="oddeven">';
                    print '<td>' . $obj->firstname . ' ' . $obj->lastname . '</td>';
                    print '<td>' . ($obj->last_visit_date ? dol_print_date($obj->last_visit_date, 'dayhour') : $langs->trans("Never")) . '</td>';
                    print '</tr>';
                }
            } else {
                print '<tr><td class="opacitymedium">' . $langs->trans("NoAssignedHousekeepers") . '</td></tr>';
            }
            print '</table>';
        }

        // Add after the assigned housekeepers table display
        print load_fiche_titre($langs->trans("TimesheetEntries"), '', '');

        $sql = "SELECT ts.work_date, ts.hours_worked, ts.note_private, u.firstname, u.lastname";
        $sql .= " FROM " . MAIN_DB_PREFIX . "cleaningservice_task_timesheet as ts";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta ON ts.fk_task_assigned = ta.rowid";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "user as u ON ta.fk_user = u.rowid";
        $sql .= " WHERE ta.fk_task = " . $object->id;
        $sql .= " ORDER BY ts.work_date DESC";

        $resql = $db->query($sql);
        if ($resql) {
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<th>' . $langs->trans("Date") . '</th>';
            print '<th>' . $langs->trans("Employee") . '</th>';
            print '<th>' . $langs->trans("HoursWorked") . '</th>';
            print '<th>' . $langs->trans("Notes") . '</th>';
            print '</tr>';

            $num = $db->num_rows($resql);
            if ($num) {
                while ($obj = $db->fetch_object($resql)) {
                    print '<tr class="oddeven">';
                    print '<td>' . dol_print_date($db->jdate($obj->work_date), 'dayhour') . '</td>';
                    print '<td>' . $obj->firstname . ' ' . $obj->lastname . '</td>';
                    print '<td>' . price2num($obj->hours_worked) . '</td>';
                    print '<td>' . $obj->note_private . '</td>';
                    print '</tr>';
                }
            } else {
                print '<tr><td colspan="4" class="opacitymedium">' . $langs->trans("NoTimesheetEntries") . '</td></tr>';
            }
            print '</table>';
        }
    }
}

if ($action == 'edit' && $user->rights->cleaningservice->task->write) {
    print load_fiche_titre($langs->trans("ModifyTask"), '', 'cleaningservice@cleaningservice');

    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $id . '">';

    print dol_get_fiche_head(array(), '');

    print '<table class="border centpercent tableforfield">';

    // Label
    print '<tr><td class="titlefield fieldrequired">' . $langs->trans("Label") . '</td><td>';
    print '<input type="text" name="label" value="' . $object->label . '" size="40">';
    print '</td></tr>';

    // Description
    print '<tr><td class="tdtop">' . $langs->trans("Description") . '</td><td>';
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
    $doleditor = new DolEditor('description', $object->description, '', 200, 'dolibarr_notes', '', false, true, true, ROWS_8, '90%');
    $doleditor->Create();
    print '</td></tr>';

    // Date Start
    print '<tr><td>' . $langs->trans("DateStart") . '</td><td>';
    print $form->selectDate($object->date_start, 'datestart', 1, 1, 0, '', 1, 1);
    print '</td></tr>';

    // Date End
    print '<tr><td>' . $langs->trans("DateEnd") . '</td><td>';
    print $form->selectDate($object->date_end, 'dateend', 1, 1, 0, '', 1, 1);
    print '</td></tr>';

    // Customer
    print '<tr><td>' . $langs->trans("Customer") . '</td><td>';
    print $form->select_company($object->fk_soc, 'fk_soc', '', 1);
    print '</td></tr>';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

    // Housekeepers
    print '<tr><td class="titlefield fieldrequired">' . $langs->trans("Housekeepers") . '</td><td>';
    $sql = "SELECT u.rowid, u.firstname, u.lastname FROM " . MAIN_DB_PREFIX . "user as u WHERE u.statut = 1 AND u.employee = 1 AND u.admin = 0 ORDER BY u.lastname";
    $resql = $db->query($sql);
    if ($resql) {
        // Get currently assigned housekeepers
        $assigned = array();
        $sql2 = "SELECT fk_user FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned WHERE fk_task = " . (int)$object->id;
        $resql2 = $db->query($sql2);
        while ($obj2 = $db->fetch_object($resql2)) {
            $assigned[] = $obj2->fk_user;
        }
        print '<select class="flat minwidth300" multiple name="housekeepers[]" id="housekeepers" required>';
        while ($obj = $db->fetch_object($resql)) {
            $selected = in_array($obj->rowid, $assigned) ? ' selected' : '';
            print '<option value="' . $obj->rowid . '"' . $selected . '>' . $obj->lastname . ' ' . $obj->firstname . '</option>';
        }
        print '</select>';
    }
    print '</td></tr>';
    print '</table>';

    print dol_get_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
    print '</div>';

    print '</form>';
}
// End of page
llxFooter();
$db->close();
