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

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

class HoursSummaryPDF extends TCPDF
{
    private $db;
    private $langs;

    public function __construct($db, $langs)
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->db = $db;
        $this->langs = $langs;

        // Set document information
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor($langs->transnoentities("CleaningService"));
        $this->SetTitle($langs->transnoentities("HoursSummary"));

        // Set margins
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);
    }

    public function Header()
    {
        // Logo
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 10, $this->langs->trans("HoursSummary"), 0, 1, 'C');
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, $this->langs->trans("Page") . ' ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// Get parameters
$action = GETPOST('action', 'alpha');
$month = GETPOST('month', 'int');
$year = GETPOST('year', 'int');
$socid = GETPOST('socid', 'int');

$task_id = GETPOST('task_id', 'int');

// If coming from task card, get the customer ID from the task
if (empty($socid) && !empty($task_id)) {
    require_once DOL_DOCUMENT_ROOT.'/custom/cleaningservice/class/cleaningservicetask.class.php';
    $task = new CleaningServiceTask($db);
    if ($task->fetch($task_id) > 0) {
        $socid = $task->fk_soc;
    }
}
// Security check
if (!$user->rights->cleaningservice->task->read) {
    accessforbidden();
}

// If we have a socid, fetch the company
if ($socid > 0) {
    $object = new Societe($db);
    $object->fetch($socid);
}

if ($action == 'generate' && $socid > 0) {
    $object = new Societe($db);
    $object->fetch($socid);

    // Create new PDF document
    $pdf = new HoursSummaryPDF($db, $langs);

    // First page
    $pdf->AddPage();

    // Customer information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, $object->name, 0, 1, 'L');
    if ($month && $year) {
        $pdf->Cell(0, 10, dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), '%B %Y'), 0, 1, 'L');
    }
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $w = array(40, 30, 60, 30, 30);
    $header = array(
        $langs->trans("Date"),
        $langs->trans("Reference"),
        $langs->trans("Employee"),
        $langs->trans("Hours"),
        $langs->trans("Status")
    );

    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
    }
    $pdf->Ln();

    // Data
    $sql = "SELECT t.ref, t.date_start, ta.hours_worked, ta.status,";
    $sql .= " u.firstname, u.lastname";
    $sql .= " FROM " . MAIN_DB_PREFIX . "cleaningservice_task as t";
    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "cleaningservice_task_assigned as ta ON t.rowid = ta.fk_task";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON ta.fk_user = u.rowid";
    $sql .= " WHERE t.fk_soc = " . $socid;
    if ($month && $year) {
        $sql .= " AND MONTH(t.date_start) = " . $month;
        $sql .= " AND YEAR(t.date_start) = " . $year;
    }
    $sql .= " ORDER BY t.date_start DESC";

    $resql = $db->query($sql);
    if ($resql) {
        $total_hours = 0;
        $pdf->SetFont('helvetica', '', 10);

        while ($obj = $db->fetch_object($resql)) {
            $pdf->Cell($w[0], 6, dol_print_date($obj->date_start, 'day'), 1);
            $pdf->Cell($w[1], 6, $obj->ref, 1);
            $pdf->Cell($w[2], 6, $obj->firstname . ' ' . $obj->lastname, 1);
            $pdf->Cell($w[3], 6, price($obj->hours_worked), 1, 0, 'R');
            $pdf->Cell($w[4], 6, $langs->trans(getTaskStatusLabel($obj->status)), 1);
            $pdf->Ln();

            $total_hours += $obj->hours_worked;
        }

        // Total line
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(array_sum($w) - $w[3] - $w[4], 7, $langs->trans("Total"), 1, 0, 'R');
        $pdf->Cell($w[3], 7, price($total_hours), 1, 0, 'R');
        $pdf->Cell($w[4], 7, '', 1);
        $pdf->Ln();
    }

    // Output PDF
    $pdf->Output('hours_summary_' . $object->name . '_' . dol_print_date(dol_now(), '%Y%m') . '.pdf', 'D');
    exit;
}

function getTaskStatusLabel($status)
{
    switch ($status) {
        case 0:
            return 'StatusDraft';
        case 1:
            return 'StatusInProgress';
        case 2:
            return 'StatusCompleted';
        case 3:
            return 'StatusValidated';
        case 4:
            return 'StatusCanceled';
        default:
            return 'Unknown';
    }
}

// View
$title = $langs->trans("HoursSummary");
llxHeader('', $title);

print load_fiche_titre($title);

print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="action" value="generate">';
print '<table class="border centpercent">';

// Customer selection
print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Customer").'</td><td>';
if ($socid > 0) {
    print $object->getNomUrl(1);
    print '<input type="hidden" name="socid" value="'.$socid.'">';
} else {
    print $form->select_company($socid, 'socid', 's.client != 0', 1, 0, 0, array(), 0, 'minwidth300');
}
print '</td></tr>';

// Period
print '<tr><td>' . $langs->trans("Period") . '</td><td>';
print $form->selectDate($date_start, 'date_start', 0, 0, 1, '', 1, 1);
print ' - ';
print $form->selectDate($date_end, 'date_end', 0, 0, 1, '', 1, 1);
print '</td></tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("GeneratePDF") . '">';
print '</div>';

print '</form>';

llxFooter();
$db->close();
