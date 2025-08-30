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

// Initialize objects
$object = new CleaningServiceTask($db);
$extrafields = new ExtraFields($db);
$form = new Form($db);

// Set the proper element for extrafields
$object->element = 'cleaningservice_task';

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Security check
if (!$user->rights->cleaningservice->task->read) {
    accessforbidden();
}

/*
 * Actions
 */
if ($action == 'add_hours' && !empty($id)) {
    $assignment_id = GETPOST('assignment_id', 'int');
    $work_date = dol_mktime(
        GETPOST('work_datehour', 'int'),
        GETPOST('work_datemin', 'int'),
        0,
        GETPOST('work_datemonth', 'int'),
        GETPOST('work_dateday', 'int'),
        GETPOST('work_dateyear', 'int')
    );
    $hours_worked = GETPOST('hours_worked');
    $note = GETPOST('note_private', 'restricthtml');
    $signature_data = GETPOST('signature_data', 'alpha');
    
    // Get extrafields data
    $extrafields_data = array();
    $extrafields_data['matelas_double'] = GETPOST('matelas_double', 'int');
    $extrafields_data['matelas_simple'] = GETPOST('matelas_simple', 'int');
    $extrafields_data['duvet_double'] = GETPOST('duvet_double', 'int');
    $extrafields_data['duvet_simple'] = GETPOST('duvet_simple', 'int');
    $extrafields_data['oreiller'] = GETPOST('oreiller', 'int');
    $extrafields_data['canapes_lit'] = GETPOST('canapes_lit', 'int');
    $extrafields_data['salles_bain'] = GETPOST('salles_bain', 'int');
    $extrafields_data['salles_douche'] = GETPOST('salles_douche', 'int');
    $extrafields_data['code_boite_cle'] = GETPOST('code_boite_cle', 'alpha');
    $extrafields_data['code_batiment'] = GETPOST('code_batiment', 'alpha');
    $extrafields_data['informations_plus'] = GETPOST('informations_plus', 'restricthtml');
    
    // die(var_dump($signature_data)); // Debugging line to check signature data
    if (empty($signature_data)) {
        setEventMessages($langs->trans("SignatureRequired"), null, 'errors');
    } else {
        $db->begin();
        
        // Update the task with new extrafields values
        if ($object->fetch($id) > 0) {
            // Update extrafields in the task
            foreach ($extrafields_data as $key => $value) {
                if (!empty($value)) {
                    $object->array_options['options_' . $key] = $value;
                }
            }
            
            // Save extrafields
            $result_extrafields = $object->insertExtraFields();
        }
        
        // First update the task_assigned table with signature
        $sql = "UPDATE " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
        $sql .= " SET signature_data = '" . $db->escape($signature_data) . "'";
        $sql .= ", signature_date = '" . $db->idate(dol_now()) . "'";
        $sql .= ", last_visit_date = '" . $db->idate($work_date) . "'";
        $sql .= " WHERE rowid = " . $assignment_id;

        $resql1 = $db->query($sql);

        // Then insert the timesheet entry
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cleaningservice_task_timesheet";
        $sql .= " (fk_task_assigned, work_date, hours_worked,";
        $sql .= " note_private, date_creation, fk_user_creat)";
        $sql .= " VALUES (" . $assignment_id . ", '" . $db->idate($work_date) . "',";
        $sql .= " " . $hours_worked . ", ";
        $sql .= " '" . $db->escape($note) . "', '" . $db->idate(dol_now()) . "',";
        $sql .= " " . $user->id . ")";

        $resql2 = $db->query($sql);

        if ($resql1 && $resql2) {
            $db->commit();
            setEventMessages($langs->trans("HoursAndSignatureSaved"), null);
        } else {
            $db->rollback();
            setEventMessages($db->lasterror(), null, 'errors');
        }
    }
}

/*
 * View
 */
$title = $langs->trans("TimeEntry");
llxHeader('', $title, '', '', 0, 0, array('https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js'), array(), 0, 'defer');
print '<style>
    .signature-container { 
        border: 1px solid #ccc; 
        margin: 10px 0;
        background: #fff;
    }
    #signature-pad { 
        width: 100%; 
        height: 200px;
    }
    .signature-buttons {
        margin-top: 10px;
    }
</style>';
if ($id > 0 && $object->fetch($id) > 0) {
    // Load extrafields
    $object->fetch_optionals();
    
    // Get task assignment
    $sql = "SELECT ta.rowid, ta.fk_user FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta";
    $sql .= " WHERE ta.fk_task = " . $id . " AND ta.fk_user = " . $user->id;

    $resql = $db->query($sql);
    if ($resql && ($assignment = $db->fetch_object($resql))) {
        print load_fiche_titre($langs->trans("TimeEntry") . ' - ' . $object->ref, '', 'cleaningservice@cleaningservice');

        // Add the go back button
        print '<div class="tabsAction">';
        print '<a class="butAction" href="' . DOL_URL_ROOT . '/custom/cleaningservice/task_card.php?id=' . $id . '">' . $langs->trans("GoBack") . '</a>';
        print '</div>';

        // Time entry form
        print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" id="timesheet-form">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="add_hours">';
        print '<input type="hidden" name="assignment_id" value="' . $assignment->rowid . '">';
        print '<input type="hidden" name="signature_data" id="signature_data">';

        print '<table class="border centpercent">';

        // Work Date (simple date input)
        print '<tr><td class="titlefield fieldrequired">' . $langs->trans("WorkDate") . '</td><td>';
        print $form->selectDate('', 'work_date', 1, 1, 0, '', 1, 1); // Added hour and minute selection
        print '</td></tr>';

        // Time Range (simple time inputs)
        print '<tr><td class="fieldrequired">' . $langs->trans("TimeRange") . '</td><td>';
        print '<input type="time" name="start_time" required> - ';
        print '<input type="time" name="end_time"  required>';
        print '</td></tr>';

        // Hours Worked
        print '<tr><td class="fieldrequired">' . $langs->trans("HoursWorked") . '</td><td>';
        print '<input type="number" step="0.5" name="hours_worked" required>';
        print '</td></tr>';

        // Notes
        print '<tr><td>' . $langs->trans("Notes") . '</td><td>';
        print '<textarea name="note_private" rows="3" cols="70"></textarea>';
        print '</td></tr>';

        // Property Details Section
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
            print '<tr><td>' . $langs->trans($label) . '</td><td>';
            $current_value = isset($object->array_options['options_' . $field]) ? $object->array_options['options_' . $field] : '';
            print '<input type="number" name="' . $field . '" value="' . $current_value . '" min="0">';
            print '</td></tr>';
        }

        // Varchar fields
        print '<tr><td>' . $langs->trans("CodeBoiteCle") . '</td><td>';
        $current_value = isset($object->array_options['options_code_boite_cle']) ? $object->array_options['options_code_boite_cle'] : '';
        print '<input type="text" name="code_boite_cle" value="' . dol_escape_htmltag($current_value) . '" size="30">';
        print '</td></tr>';

        print '<tr><td>' . $langs->trans("CodeBatiment") . '</td><td>';
        $current_value = isset($object->array_options['options_code_batiment']) ? $object->array_options['options_code_batiment'] : '';
        print '<input type="text" name="code_batiment" value="' . dol_escape_htmltag($current_value) . '" size="30">';
        print '</td></tr>';

        // Text field
        print '<tr><td>' . $langs->trans("InformationsPlus") . '</td><td>';
        $current_value = isset($object->array_options['options_informations_plus']) ? $object->array_options['options_informations_plus'] : '';
        print '<textarea name="informations_plus" rows="3" cols="70">' . dol_escape_htmltag($current_value) . '</textarea>';
        print '</td></tr>';

        print '<tr><td colspan="2"><hr></td></tr>'; // Separator

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
        print '<input type="submit" class="button" value="' . $langs->trans("SaveHours") . '">';
        print '</div>';
        print '</form>';

        // Display time history
        print '<br>';
        print load_fiche_titre($langs->trans("TimeHistory"), '', '');

        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "cleaningservice_task_timesheet";
        $sql .= " WHERE fk_task_assigned = " . $assignment->rowid;
        $sql .= " ORDER BY work_date DESC, date_creation DESC";

        $resql = $db->query($sql);
        if ($resql) {
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<th>' . $langs->trans("Date") . '</th>';
            // print '<th>' . $langs->trans("TimeRange") . '</th>';
            print '<th>' . $langs->trans("HoursWorked") . '</th>';
            print '<th>' . $langs->trans("Notes") . '</th>';
            print '</tr>';

            while ($obj = $db->fetch_object($resql)) {
                print '<tr class="oddeven">';
                print '<td>' . $obj->work_date . '</td>';
                print '<td>' . $obj->hours_worked . '</td>';
                print '<td>' . $obj->note_private . '</td>';
                print '</tr>';
            }
            print '</table>';
        }
    }
}

print '<script>
    // Initialize signature pad
    var canvas = document.querySelector("#signature-pad");
    var signaturePad = new SignaturePad(canvas, {
        backgroundColor: "rgb(255, 255, 255)"
    });

    // Handle canvas resize
    function resizeCanvas() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear();
    }

    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();

    // Clear signature button
    document.querySelector("#clear-signature").addEventListener("click", function() {
        signaturePad.clear();
    });

    // Form submission
    var form = document.getElementById("timesheet-form");
    if (form) {
        form.addEventListener("submit", function(e) {
            if (signaturePad.isEmpty()) {
                alert("' . $langs->trans("PleaseProvideSignature") . '");
                e.preventDefault();
                return false;
            }
            document.getElementById("signature_data").value = signaturePad.toDataURL();
        });
    }
</script>';

llxFooter();
$db->close();
