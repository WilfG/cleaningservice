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

// Security check
if (!$user->rights->cleaningservice->task->read) {
    accessforbidden();
}

// Initialize objects
$object = new CleaningServiceTask($db);
$form = new Form($db);

/*
 * Actions
 */
if ($action == 'save_signature' && !empty($id)) {
    $signature_data = GETPOST('signature_data', 'alpha');

    $sql = "UPDATE " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
    $sql .= " SET signature_data = '" . $db->escape($signature_data) . "'";
    $sql .= ", signature_date = '" . $db->idate(dol_now()) . "'";
    $sql .= ", status = 2"; // Set status to completed
    $sql .= " WHERE fk_task = " . $id;
    $sql .= " AND fk_user = " . $user->id;

    $resql = $db->query($sql);
    if ($resql) {
        setEventMessages($langs->trans("SignatureSaved"), null);
    } else {
        setEventMessages($db->lasterror(), null, 'errors');
    }
}

/*
 * View
 */
$title = $langs->trans("TaskSignature");

llxHeader('', $title, '', '', 0, 0, array('https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js'), array(), 0, 'defer');

if ($id > 0) {
    $result = $object->fetch($id);
    if ($result > 0) {
        print load_fiche_titre($langs->trans("TaskSignature") . ' - ' . $object->ref, '', 'cleaningservice@cleaningservice');

        // Signature Canvas
        print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" id="signatureForm">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="save_signature">';
        print '<input type="hidden" name="signature_data" id="signature_data">';

        print '<div class="signature-container" style="border:1px solid #ccc; margin:20px 0;">';
        print '<canvas id="signature-pad" width="500" height="200"></canvas>';
        print '</div>';

        print '<div class="button-container">';
        print '<button type="button" class="button" id="clear-button">' . $langs->trans("Clear") . '</button>';
        print '<button type="submit" class="button" id="save-button">' . $langs->trans("SaveSignature") . '</button>';
        print '</div>';

        print '</form>';

        // JavaScript for signature pad
        // In the same file, update the script section:
        print '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var canvas = document.getElementById("signature-pad");
        var signaturePad = new SignaturePad(canvas, {
            backgroundColor: "rgb(255, 255, 255)",
            penColor: "rgb(0, 0, 0)"
        });
        
        document.getElementById("clear-button").addEventListener("click", function() {
            signaturePad.clear();
        });
        
        document.getElementById("signatureForm").addEventListener("submit", function(e) {
            if (signaturePad.isEmpty()) {
                e.preventDefault();
                alert("' . $langs->trans("PleaseProvideSignature") . '");
                return false;
            }
            
            document.getElementById("signature_data").value = signaturePad.toDataURL();
        });
    
    });
</script>';

        // Display existing signature if any
        $sql = "SELECT signature_data, signature_date";
        $sql .= " FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
        $sql .= " WHERE fk_task = " . $id;
        $sql .= " AND fk_user = " . $user->id;
        $sql .= " AND signature_data IS NOT NULL";

        $resql = $db->query($sql);
        if ($resql && $obj = $db->fetch_object($resql)) {
            print '<div class="existing-signature">';
            print '<h3>' . $langs->trans("ExistingSignature") . '</h3>';
            print '<img src="' . $obj->signature_data . '" style="max-width:500px;border:1px solid #ccc;">';
            print '<br>' . $langs->trans("SignedOn") . ': ' . dol_print_date($obj->signature_date, 'dayhour');
            print '</div>';
        }
    }
}

llxFooter();
$db->close();
