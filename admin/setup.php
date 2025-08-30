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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/cleaningservice.lib.php';

// Security check
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');

// Initialize technical object to manage hooks of page
$hookmanager->initHooks(array('cleaningservicesetup'));

/*
 * Actions
 */
if ($action == 'update') {
    $price_per_hour = GETPOST('CLEANINGSERVICE_PRICE_PER_HOUR', 'alpha');
    $auto_invoice = GETPOST('CLEANINGSERVICE_AUTO_INVOICE', 'alpha');
    
    dolibarr_set_const($db, 'CLEANINGSERVICE_PRICE_PER_HOUR', $price_per_hour, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CLEANINGSERVICE_AUTO_INVOICE', $auto_invoice, 'chaine', 0, '', $conf->entity);
    
    setEventMessages($langs->trans("SetupSaved"), null);
}

/*
 * View
 */
$page_name = "CleaningServiceSetup";
$help_url = '';
llxHeader('', $langs->trans($page_name), $help_url);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Price per hour
print '<tr class="oddeven">';
print '<td>'.$langs->trans("PricePerHour").'</td>';
print '<td>';
print '<input type="text" name="CLEANINGSERVICE_PRICE_PER_HOUR" value="'.$conf->global->CLEANINGSERVICE_PRICE_PER_HOUR.'">';
print '</td></tr>';

// Auto invoice
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AutoInvoice").'</td>';
print '<td>';
print '<input type="checkbox" name="CLEANINGSERVICE_AUTO_INVOICE" value="1"'.($conf->global->CLEANINGSERVICE_AUTO_INVOICE ? ' checked' : '').'>';
print '</td></tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

llxFooter();
$db->close();