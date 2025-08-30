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
if ($action == 'update_hours' && !empty($id)) {
    // First check if hours are already locked
    $sql = "SELECT hours_locked FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
    $sql .= " WHERE fk_task = " . $id;
    $sql .= " AND fk_user = " . $user->id;

    $resql = $db->query($sql);
    if ($obj = $db->fetch_object($resql)) {
        if ($obj->hours_locked) {
            setEventMessages($langs->trans("HoursAlreadyLocked"), null, 'errors');
        } else {
            $hours_worked = GETPOST('hours_worked', 'alpha');
            $note = GETPOST('note_private', 'restricthtml');
            $signature_data = GETPOST('signature_data', 'alpha');

            if (empty($signature_data)) {
                setEventMessages($langs->trans("SignatureRequired"), null, 'errors');
            } else {
                $sql = "UPDATE " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
                $sql .= " SET hours_worked = " . $hours_worked;
                $sql .= ", note_private = '" . $db->escape($note) . "'";
                $sql .= ", hours_locked = 1";
                $sql .= ", signature_data = '" . $db->escape($signature_data) . "'";
                $sql .= ", signature_date = '" . $db->idate(dol_now()) . "'";
                $sql .= ", last_visit_date = '" . $db->idate(dol_now()) . "'";
                $sql .= " WHERE fk_task = " . $id;
                $sql .= " AND fk_user = " . $user->id;

                $resql = $db->query($sql);
                if ($resql) {
                    setEventMessages($langs->trans("HoursAndSignatureSaved"), null);
                } else {
                    setEventMessages($db->lasterror(), null, 'errors');
                }
            }
        }
    }
}

/*
 * View
 */
$title = $langs->trans("TaskHours");
llxHeader('', $title);

if ($id > 0) {
    $result = $object->fetch($id);
    if ($result > 0) {
        print load_fiche_titre($langs->trans("TaskHours") . ' - ' . $object->ref, '', 'cleaningservice@cleaningservice');

        $sql = "SELECT hours_locked FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
        $sql .= " WHERE fk_task = " . $id;
        $sql .= " AND fk_user = " . $user->id;
        $hours_locked = false;

        $resql = $db->query($sql);
        if ($obj = $db->fetch_object($resql)) {
            $hours_locked = (bool) $obj->hours_locked;
        }

        if ($hours_locked) {
            print '<div class="warning">' . $langs->trans("HoursAlreadyLocked") . '</div>';
        } else {
            // Hours Entry Form
            print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" id="hours-form">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="update_hours">';
            print '<input type="hidden" name="signature_data" id="signature_data">';

            print '<table class="border centpercent">';

            // Hours Worked
            print '<tr><td class="titlefield fieldrequired">' . $langs->trans("HoursWorked") . '</td><td>';
            print '<input type="number" step="0.5" name="hours_worked" value="" required>';
            print '</td></tr>';

            // Notes
            print '<tr><td>' . $langs->trans("Notes") . '</td><td>';
            print '<textarea name="note_private" rows="3" cols="70"></textarea>';
            print '</td></tr>';

            // Signature
            print '<tr><td class="fieldrequired">' . $langs->trans("Signature") . '</td><td>';
            print '<div class="signature-container">';
            print '<canvas id="signature-pad"></canvas>';
            print '</div>';
            print '<div class="signature-buttons">';
            print '<button type="button" class="button" id="clear-signature">' . $langs->trans("ClearSignature") . '</button>';
            print '</div>';
            print '</td></tr>';

            print '</table>';

            print '<div class="center">';
            print '<input type="submit" class="button" value="' . $langs->trans("SaveHoursAndSign") . '">';
            print '</div>';

            print '</form>';

            // Add signature pad initialization
            print '<script>
        var canvas = document.querySelector("#signature-pad");
        var signaturePad = new SignaturePad(canvas, {
            backgroundColor: "rgb(255, 255, 255)"
        });

        // Handle window resize
        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();
        }

        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        // Clear signature
        document.querySelector("#clear-signature").addEventListener("click", function() {
            signaturePad.clear();
        });

        // Form submission
        document.querySelector("#hours-form").addEventListener("submit", function(e) {
            if (signaturePad.isEmpty()) {
                alert("' . $langs->trans("PleaseProvideSignature") . '");
                e.preventDefault();
                return false;
            }
            document.querySelector("#signature_data").value = signaturePad.toDataURL();
        });
    </script>';
        }
        // Display hours history
        print '<br>';
        print load_fiche_titre($langs->trans("HoursHistory"), '', '');

        $sql = "SELECT t.*, u.firstname, u.lastname";
        $sql .= " FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON t.fk_user = u.rowid";
        $sql .= " WHERE t.fk_task = " . $id;
        $sql .= " AND t.hours_worked IS NOT NULL";
        $sql .= " ORDER BY t.signature_date DESC";

        $resql = $db->query($sql);
        if ($resql) {
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<th>' . $langs->trans("Employee") . '</th>';
            print '<th>' . $langs->trans("HoursWorked") . '</th>';
            print '<th>' . $langs->trans("Date") . '</th>';
            print '<th>' . $langs->trans("Notes") . '</th>';
            print '</tr>';

            while ($obj = $db->fetch_object($resql)) {
                print '<tr class="oddeven">';
                print '<td>' . $obj->firstname . ' ' . $obj->lastname . '</td>';
                print '<td>' . $obj->hours_worked . '</td>';
                print '<td>' . dol_print_date($obj->signature_date, 'dayhour') . '</td>';
                print '<td>' . $obj->note_private . '</td>';
                print '</tr>';
            }
            print '</table>';
        }
    }
}

llxFooter();
$db->close();
