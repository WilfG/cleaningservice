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
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once './class/cleaningservicetask.class.php';

$langs->loadLangs(array("cleaningservice@cleaningservice", "bills"));

// Parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
if (!$user->rights->facture->creer) {
    accessforbidden();
}

// Initialize objects
$object = new CleaningServiceTask($db);
$form = new Form($db);

/*
 * Actions
 */
if ($action == 'createinvoice' && !empty($id)) {
    $object->fetch($id);

    // Check if task is completed
    $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
    $sql .= " WHERE fk_task = " . $id . " AND status = 3"; // Status 2 = Completed
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);

    if ($obj->count > 0) {
        // Create invoice
        $invoice = new Facture($db);
        $invoice->socid = $object->fk_soc;
        $invoice->date = dol_now();
        $invoice->type = Facture::TYPE_STANDARD;

        // Get worked hours
        $sql = "SELECT SUM(hours_worked) as total_hours FROM " . MAIN_DB_PREFIX . "cleaningservice_task_timesheet";
        $sql .= " WHERE fk_task_assigned = " . $id;
        $resql = $db->query($sql);
        $hours_obj = $db->fetch_object($resql);

        $result = $invoice->create($user);
        if ($result > 0) {
            // Add invoice line
            $product_desc = $langs->trans("CleaningService") . ' - ' . $object->ref;
            $product_desc .= "\n" . $langs->trans("Date") . ': ' . dol_print_date($object->date_start, 'dayhour');
            $product_desc .= "\n" . $langs->trans("HoursWorked") . ': ' . $hours_obj->total_hours;

            // Get price per hour from configuration
            $price_per_hour = !empty($conf->global->CLEANINGSERVICE_PRICE_PER_HOUR) ?
                $conf->global->CLEANINGSERVICE_PRICE_PER_HOUR : 25; // Default 25


            // In the createinvoice action, replace the addline() call with:
            $invoice->addline(
                $product_desc,                    // Description
                $price_per_hour,                  // Unit price
                $hours_obj->total_hours,          // Quantity
                20,                              // VAT rate (adjust as needed)
                0,                               // Local tax 1
                0,                               // Local tax 2
                0,                               // Product ID
                0,                               // Reduction %
                '',                              // Reduction absolute (changed from 0 to empty string)
                'HT',                            // Price base type
                $price_per_hour,                 // Unit price with tax
                1,                               // Product type
                0,                               // Range (changed from -1 to 0)
                '',                              // Special code (changed from 0 to empty string)
                0,                               // Origin line id (changed from $object->id to 0)
                0,                               // No origin
                0,                               // Product info
                array()                          // Array of options (changed from '' to array())
            );


            // Update task status
            $sql = "UPDATE " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
            $sql .= " SET status = 3"; // 3 = Invoiced
            $sql .= " WHERE fk_task = " . $id;
            $db->query($sql);

            setEventMessages($langs->trans("InvoiceCreated"), null);
            header("Location: " . DOL_URL_ROOT . "/compta/facture/card.php?id=" . $invoice->id);
            exit;
        } else {
            setEventMessages($invoice->error, $invoice->errors, 'errors');
        }
    } else {
        setEventMessages($langs->trans("TaskNotCompleted"), null, 'errors');
    }
}

/*
 * View
 */
$title = $langs->trans("CreateInvoice");
llxHeader('', $title);

if ($id > 0) {
    $object->fetch($id);
    print load_fiche_titre($langs->trans("CreateInvoice") . ' - ' . $object->ref);

    print '<div class="tabsAction">';
    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=createinvoice&id=' . $id . '&token=' . newToken() . '">';
    print $langs->trans("CreateInvoice");
    print '</a>';
    print '</div>';

    // Show task details and worked hours
    print '<table class="border centpercent">';
    print '<tr><td class="titlefield">' . $langs->trans("Task") . '</td><td>' . $object->ref . '</td></tr>';
    print '<tr><td>' . $langs->trans("Customer") . '</td><td>' . $object->thirdparty->name . '</td></tr>';
    print '<tr><td>' . $langs->trans("DateStart") . '</td><td>' . dol_print_date($object->date_start, 'dayhour') . '</td></tr>';
    print '<tr><td>' . $langs->trans("HoursWorked") . '</td><td>';

    $sql = "SELECT u.firstname, u.lastname, t.hours";
    $sql .= " FROM " . MAIN_DB_PREFIX . "cleaningservice_task_timesheet as t";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON t.fk_user = u.rowid";
    $sql .= " WHERE t.fk_task = " . $id;

    $resql = $db->query($sql);
    $total_hours = 0;
    while ($obj = $db->fetch_object($resql)) {
        print $obj->firstname . ' ' . $obj->lastname . ': ' . $obj->hours_worked . 'h<br>';
        $total_hours += $obj->hours;
    }

    print '<br>' . $langs->trans("TotalHours") . ': ' . $total_hours . 'h';
    print '</td></tr>';
    print '</table>';
}

llxFooter();
$db->close();
