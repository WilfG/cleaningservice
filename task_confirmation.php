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

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once './class/cleaningservicetask.class.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$token = GETPOST('token', 'alpha');

// Security check
if (!$user->rights->cleaningservice->task->read) {
    accessforbidden();
}

if ($action == 'confirm' && !empty($id)) {
    if (!validateSecurityToken($token)) {
        setEventMessages($langs->trans("InvalidToken"), null, 'errors');
        header("Location: schedule.php");
        exit;
    }

    $object = new CleaningServiceTask($db);
    $result = $object->fetch($id);

    if ($result > 0) {
        // Check if user is assigned to this task
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "cleaningservice_task_assigned";
        $sql .= " WHERE fk_task = " . $id . " AND fk_user = " . $user->id;
        $resql = $db->query($sql);

        if ($resql && $db->num_rows($resql) > 0) {
            $object->status = 1; // Confirmed
            $result = $object->update($user);

            if ($result > 0) {
                setEventMessages($langs->trans("PresenceConfirmed"), null);
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        } else {
            setEventMessages($langs->trans("NotAssignedToTask"), null, 'errors');
        }
    }

    header("Location: schedule.php?mode=my");
    exit;
}
