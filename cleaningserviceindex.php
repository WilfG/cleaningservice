<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       cleaningservice/cleaningserviceindex.php
 *	\ingroup    cleaningservice
 *	\brief      Home page of cleaningservice top menu
 */

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

// Add after loading required files
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
// require_once DOL_DOCUMENT_ROOT.'/swissqr/class/swissqr.class.php';

if (!$user->admin && !$user->rights->cleaningservice->task->create) {
    accessforbidden();
}

/*
 * Actions
 */

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'taskcard';



// Initialize Form
$form = new Form($db);

// Get filter parameters
$search_employee = GETPOST('search_employee', 'int');
$search_client = GETPOST('search_client', 'int');
$search_status = GETPOST('search_status', 'alpha');
$date_startday = GETPOST('date_startday', 'int');
$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

// die(var_dump($search_employee));
// Export action
$where = array();
if ($search_employee > 0) {
    $where[] = "ta.fk_user = " . (int)$search_employee;
}
if ($search_client > 0) {
    $where[] = "t.fk_soc = " . (int)$search_client;
}
if ($search_status != '-1') {
    $where[] = "t.status = " . (int)$search_status;
}
if ($date_startyear && $date_startmonth && $date_startday) {
    $date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
    $where[] = "t.date_start >= '" . $db->idate($date_start) . "'";
}
if ($date_endyear && $date_endmonth && $date_endday) {
    $date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);
    $where[] = "t.date_end <= '" . $db->idate($date_end) . "'";
}

if ($action == 'export' && $user->admin) {
    // Build WHERE clause for filters

    // Build WHERE clause
    $whereClause = '';
    if (!empty($where)) {
        $whereClause = ' WHERE ' . implode(' AND ', $where);
    }

    $sql = 'SELECT t.ref, t.label, t.description,';
    $sql .= ' t.date_start, t.date_end, t.status,';
    $sql .= ' s.nom as client_name, s.email as client_email,';
    $sql .= ' u.firstname, u.lastname,';
    $sql .= ' ta.last_visit_date,';
    $sql .= ' (SELECT SUM(hours_worked) FROM ' . MAIN_DB_PREFIX . 'cleaningservice_task_timesheet';
    $sql .= ' WHERE fk_task_assigned = ta.rowid) as total_hours_worked,';
    $sql .= ' (SELECT MAX(work_date) FROM ' . MAIN_DB_PREFIX . 'cleaningservice_task_timesheet';
    $sql .= ' WHERE fk_task_assigned = ta.rowid) as last_timesheet';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'cleaningservice_task as t';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON t.fk_soc = s.rowid';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'cleaningservice_task_assigned as ta ON t.rowid = ta.fk_task';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u ON ta.fk_user = u.rowid';
    $sql .= $whereClause;
    $sql .= ' ORDER BY t.date_start DESC';


    $resql = $db->query($sql);
    // die(var_dump($resql));
    if ($resql) {
        $filename = 'tasks_report_' . dol_print_date(dol_now(), 'dayxcard') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $db->escape($filename));

        $out = fopen('php://output', 'w');

        // Write CSV headers
        fputcsv($out, array(
            $langs->trans('Ref'),
            $langs->trans('Label'),
            $langs->trans('Customer'),
            $langs->trans('Employee'),
            $langs->trans('DateStart'),
            $langs->trans('DateEnd'),
            $langs->trans('TotalHoursWorked'),
            $langs->trans('LastTimesheet'),
            $langs->trans('LastVisit'),
            $langs->trans('Status')
        ));

        while ($obj = $db->fetch_object($resql)) {
            $status = '';
            switch ($obj->status) {
                case 0:
                    $status = $langs->trans('Pending');
                    break;
                case 1:
                    $status = $langs->trans('InProgress');
                    break;
                case 2:
                    $status = $langs->trans('Completed');
                    break;
            }

            fputcsv($out, array(
                $obj->ref,
                $obj->label,
                $obj->client_name,
                $obj->firstname . ' ' . $obj->lastname,
                dol_print_date($db->jdate($obj->date_start), 'dayhour'),
                dol_print_date($db->jdate($obj->date_end), 'dayhour'),
                price2num($obj->total_hours_worked),
                dol_print_date($db->jdate($obj->last_timesheet), 'dayhour'),
                dol_print_date($db->jdate($obj->last_visit_date), 'dayhour'),
                $status
            ));
        }
        fclose($out);
        exit;
    }
}

// Replace the invoice generation section with:
if ($action == 'generate_invoice' && $user->admin) {
    $error = 0;
    $db->begin();

    // Check if client is selected
    if (empty($search_client)) {
        setEventMessages($langs->trans("PleaseSelectClient"), null, 'warnings');
    } else {
        // Get timesheet entries for selected client with filters
        $sql = 'SELECT t.ref, t.label, ts.work_date, ts.hours_worked, ts.note_private,';
        $sql .= ' CONCAT(u.firstname, " ", u.lastname) as employee,';
        $sql .= ' s.rowid as socid, s.nom as client_name';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'cleaningservice_task as t';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON t.fk_soc = s.rowid';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'cleaningservice_task_assigned as ta ON t.rowid = ta.fk_task';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'cleaningservice_task_timesheet as ts ON ta.rowid = ts.fk_task_assigned';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u ON ta.fk_user = u.rowid';
        $sql .= ' WHERE t.fk_soc = ' . (int)$search_client;
        $sql .= ' AND ts.hours_worked > 0';

        // Add other filters
        if ($search_employee > 0) {
            $sql .= ' AND ta.fk_user = ' . (int)$search_employee;
        }
        if ($search_status != '-1') {
            $sql .= ' AND t.status = ' . (int)$search_status;
        }
        if ($date_startyear && $date_startmonth && $date_startday) {
            $date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
            $sql .= ' AND ts.work_date >= "' . $db->idate($date_start) . '"';
        }
        if ($date_endyear && $date_endmonth && $date_endday) {
            $date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);
            $sql .= ' AND ts.work_date <= "' . $db->idate($date_end) . '"';
        }
        $sql .= ' ORDER BY ts.work_date ASC';

        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            // Get first row to initialize invoice
            $first_row = $db->fetch_object($resql);
            // Create new invoice
            $facture = new Facture($db);
            $facture->socid = $first_row->socid;
            $facture->date = dol_now();
            $facture->type = Facture::TYPE_STANDARD;
            $facture->cond_reglement_id = $conf->global->CLEANINGSERVICE_DEFAULT_PAYMENT_TERM;
            $facture->mode_reglement_id = $conf->global->CLEANINGSERVICE_DEFAULT_PAYMENT_MODE;

            // Create invoice
            $result = $facture->create($user);
            if ($result > 0) {

                // if (!empty($conf->global->CLEANINGSERVICE_SWISSQR_IBAN)) {
                //     $swissqr = new SwissQR($db);
                //     $swissqr->iban = $conf->global->CLEANINGSERVICE_SWISSQR_IBAN;
                //     $swissqr->address_type = $conf->global->CLEANINGSERVICE_SWISSQR_ADDRESS_TYPE;

                //     // Get company info
                //     $company = $mysoc;
                //     $swissqr->creditor = array(
                //         'name' => $company->name,
                //         'street' => $company->address,
                //         'number' => '',
                //         'zip' => $company->zip,
                //         'city' => $company->town,
                //         'country' => $company->country_code
                //     );

                //     // Get customer info
                //     $client = new Societe($db);
                //     $client->fetch($facture->socid);
                //     $swissqr->debtor = array(
                //         'name' => $client->name,
                //         'street' => $client->address,
                //         'number' => '',
                //         'zip' => $client->zip,
                //         'city' => $client->town,
                //         'country' => $client->country_code
                //     );

                //     // Set amount and currency
                //     $swissqr->amount = $facture->total_ttc;
                //     $swissqr->currency = $conf->currency;

                //     // Set reference
                //     $swissqr->reference_type = 'NON';
                //     $swissqr->reference = '';

                //     // Set message
                //     $swissqr->message = $langs->trans('InvoiceRef') . ' ' . $facture->ref;

                //     // Attach SwissQR to invoice
                //     $result = $swissqr->attachToObject($facture);
                //     if ($result < 0) {
                //         $error++;
                //         setEventMessages($swissqr->error, $swissqr->errors, 'errors');
                //     }
                // }
                // Add first line
                $desc = $langs->trans("Task") . ": " . $first_row->ref . " - " . $first_row->label . "\n";
                $desc .= $langs->trans("Date") . ": " . dol_print_date($db->jdate($first_row->work_date), 'dayhour') . "\n";
                $desc .= $langs->trans("Housekeeper") . ": " . $first_row->employee;

                if (!empty($first_row->note_private)) {
                    $desc .= "\n" . $langs->trans("Notes") . ": " . $first_row->note_private;
                }

                $result = $facture->addline(
                    $desc,
                    $conf->global->CLEANINGSERVICE_PRICE_PER_HOUR,
                    $first_row->hours_worked,
                    $conf->global->CLEANINGSERVICE_DEFAULT_VAT_RATE,
                    0,
                    0,
                    0,
                    0,
                    '',
                    '',
                    0,
                    0,
                    '',
                    'HT',
                    0,
                    1
                );

                // Add remaining lines
                while ($timesheet = $db->fetch_object($resql)) {
                    $desc = $langs->trans("Task") . ": " . $timesheet->ref . " - " . $timesheet->label . "\n";
                    $desc .= $langs->trans("Date") . ": " . dol_print_date($db->jdate($timesheet->work_date), 'dayhour') . "\n";
                    $desc .= $langs->trans("Housekeeper") . ": " . $timesheet->employee;

                    if (!empty($timesheet->note_private)) {
                        $desc .= "\n" . $langs->trans("Notes") . ": " . $timesheet->note_private;
                    }


                    $result = $facture->addline(
                        $desc,
                        $conf->global->CLEANINGSERVICE_PRICE_PER_HOUR,
                        $timesheet->hours_worked,
                        $conf->global->CLEANINGSERVICE_DEFAULT_VAT_RATE,
                        0,
                        0,
                        0,
                        0,
                        '',
                        '',
                        0,
                        0,
                        '',
                        'HT',
                        0,
                        1
                    );
                    if ($result < 0) {
                        $error++;
                        setEventMessages($facture->error, $facture->errors, 'errors');
                        break;
                    }
                }
                if (!$error) {
                    $db->commit();
                    setEventMessages($langs->trans("InvoiceGenerated"), null);
                } else {
                    $db->rollback();
                }
            } else {
                $error++;
                $db->rollback();
                setEventMessages($facture->error, $facture->errors, 'errors');
            }
        } else {
            setEventMessages($langs->trans("NoTimesheetEntries"), null, 'warnings');
        }
    }
}

/*
 * View
 */

$title = $langs->trans("CleaningService");
llxHeader('', $title);

print load_fiche_titre($langs->trans("Reports"), '', 'cleaningservice@cleaningservice');

// Filter form
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="filter">';

print '<div class="fichecenter">';
print '<table class="border centpercent">';

// Employee filter
print '<tr><td class="titlefield">' . $langs->trans("Employee") . '</td><td>';
print $form->select_dolusers($search_employee, 'search_employee', 1, null, 0, '', '', 0, 0, 0, '', 0, '', '', 1);
print '</td></tr>';

// Client filter
print '<tr><td>' . $langs->trans("Customer") . '</td><td>';
print $form->select_company($search_client, 'search_client', '', 1);
print '</td></tr>';

// Date range
print '<tr><td>' . $langs->trans("Period") . '</td><td>';
print $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 1);
print ' - ';
print $form->selectDate($date_end, 'date_end', 0, 0, 1, '', 1, 1);
print '</td></tr>';

// Status
print '<tr><td>' . $langs->trans("Status") . '</td><td>';
$status_array = array(
    0 => 'Pending',
    1 => 'In Progress',
    2 => 'Completed'
);
print $form->selectarray('search_status', $status_array, $search_status, 1);
print '</td></tr>';

print '</table>';
print '<div class="center"><input type="submit" class="button" value="' . $langs->trans("Search") . '"></div>';
print '</div>';
print '</form>';


// Export buttonif ($user->admin) {
print '<div class="right">';
print '<a class="button" href="' . $_SERVER["PHP_SELF"] . '?action=export';
if ($search_employee > 0) print '&search_employee=' . $search_employee;
if ($search_client > 0) print '&search_client=' . $search_client;
if ($search_status != '-1') print '&search_status=' . $search_status;
if ($date_startyear) {
    print '&date_startday=' . $date_startday;
    print '&date_startmonth=' . $date_startmonth;
    print '&date_startyear=' . $date_startyear;
}
if ($date_endyear) {
    print '&date_endday=' . $date_endday;
    print '&date_endmonth=' . $date_endmonth;
    print '&date_endyear=' . $date_endyear;
}
print '&token=' . newToken() . '">';
print $langs->trans("Export");
print '</a>';

print '<a class="button" href="' . $_SERVER["PHP_SELF"] . '?action=generate_invoice';
if ($search_employee > 0) print '&search_employee=' . $search_employee;
if ($search_client > 0) print '&search_client=' . $search_client;
if ($search_status != '-1') print '&search_status=' . $search_status;
if ($date_startyear) {
    print '&date_startday=' . $date_startday;
    print '&date_startmonth=' . $date_startmonth;
    print '&date_startyear=' . $date_startyear;
}
if ($date_endyear) {
    print '&date_endday=' . $date_endday;
    print '&date_endmonth=' . $date_endmonth;
    print '&date_endyear=' . $date_endyear;
}
print '&token=' . newToken() . '">';
print $langs->trans("GenerateInvoices");
print '</a>';
print '</div>';

// Statistics
print '<div class="fichecenter">';
print '<div class="div-table-responsive-no-min">';

// Build WHERE clause for filters
$where = array();
if ($search_employee > 0) {
    $where[] = "ta.fk_user = " . (int)$search_employee;
}
if ($search_client > 0) {
    $where[] = "t.fk_soc = " . (int)$search_client;
}
if ($search_status != '-1') {
    $where[] = "t.status = " . (int)$search_status;
}
if ($date_startyear && $date_startmonth && $date_startday) {
    $date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
    $where[] = "t.date_start >= '" . $db->idate($date_start) . "'";
}
if ($date_endyear && $date_endmonth && $date_endday) {
    $date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);
    $where[] = "t.date_end <= '" . $db->idate($date_end) . "'";
}

// Build WHERE clause
$whereClause = '';
if (!empty($where)) {
    $whereClause = ' WHERE ' . implode(' AND ', $where);
}

// Task Summary query
$sql = 'SELECT COUNT(*) as total,';
$sql .= ' SUM(CASE WHEN t.status = 0 THEN 1 ELSE 0 END) as pending,';
$sql .= ' SUM(CASE WHEN t.status = 1 THEN 1 ELSE 0 END) as inprogress,';
$sql .= ' SUM(CASE WHEN t.status = 2 THEN 1 ELSE 0 END) as completed';
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'cleaningservice_task as t';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'cleaningservice_task_assigned as ta ON t.rowid = ta.fk_task';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON t.fk_soc = s.rowid';
$sql .= $whereClause;

$resql = $db->query($sql);
if ($resql) {
    $stats = $db->fetch_object($resql);
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th colspan="4">' . $langs->trans("TaskSummary") . '</th>';
    print '</tr>';
    print '<tr class="oddeven">';
    print '<td>' . $langs->trans("TotalTasks") . ': ' . $stats->total . '</td>';
    print '<td>' . $langs->trans("Pending") . ': ' . $stats->pending . '</td>';
    print '<td>' . $langs->trans("InProgress") . ': ' . $stats->inprogress . '</td>';
    print '<td>' . $langs->trans("Completed") . ': ' . $stats->completed . '</td>';
    print '</tr>';
    print '</table>';
}

// Hours Summary
print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans("Employee") . '</th>';
print '<th>' . $langs->trans("TasksAssigned") . '</th>';
print '<th>' . $langs->trans("HoursWorked") . '</th>';
print '<th>' . $langs->trans("CompletionRate") . '</th>';
print '</tr>';

$sql = "SELECT u.rowid, u.firstname, u.lastname,";
$sql .= " COUNT(DISTINCT ta.fk_task) as tasks,";
$sql .= " SUM(ta.hours_worked) as hours,";
$sql .= " COUNT(CASE WHEN t.status = 2 THEN 1 END) * 100 / COUNT(*) as completion";
$sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta ON u.rowid = ta.fk_user";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "cleaningservice_task as t ON ta.fk_task = t.rowid";
$sql .= " GROUP BY u.rowid, u.firstname, u.lastname";

$resql = $db->query($sql);
while ($obj = $db->fetch_object($resql)) {
    print '<tr class="oddeven">';
    print '<td>' . $obj->firstname . ' ' . $obj->lastname . '</td>';
    print '<td>' . $obj->tasks . '</td>';
    print '<td>' . ($obj->hours ? price2num($obj->hours) : '0') . '</td>';
    print '<td>' . round($obj->completion) . '%</td>';
    print '</tr>';
}
print '</table>';



print '</div>';
print '</div>';

llxFooter();
$db->close();
