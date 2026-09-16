<?php
require_once(__DIR__ . '/../lib/fpdf/fpdf.php');
require_once(__DIR__ . '/../lib/fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

$campus_type = $_POST['campus_type'] ?? 'on';

if ($campus_type === 'off') {
    $pdf = renderOffCampusPermit($_POST);
} else {
    $pdf = renderInCampusPermit($_POST);
}

// Output the PDF
if (isset($_POST['preview']) && $_POST['preview'] === 'true') {
    $pdfContent = $pdf->Output('S');
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdfContent));
    echo $pdfContent;
} else {
    $filename = 'Activity_Permit_' . date('Ymd_His') . '.pdf';
    $pdf->Output('D', $filename);
}

// ============================================================
// Shared helpers
// ============================================================

/** Compact date range so it always fits the Date/s box. */
function formatDateRange($rawStart, $rawEnd)
{
    $ts = !empty($rawStart) ? strtotime($rawStart) : false;
    $te = !empty($rawEnd) ? strtotime($rawEnd) : false;
    if ($ts && $te) {
        if (date('Y-m-d', $ts) === date('Y-m-d', $te)) {
            return date("M j, Y", $ts);
        } elseif (date('Y-m', $ts) === date('Y-m', $te)) {
            return date("M j", $ts) . '-' . date("j, Y", $te);
        }
        return date("M j, Y", $ts) . ' - ' . date("M j, Y", $te);
    } elseif ($ts) {
        return date("M j, Y", $ts);
    }
    return '';
}

function formatTimeRange($rawStart, $rawEnd)
{
    $start = (!empty($rawStart)) ? date("g:i A", strtotime($rawStart)) : '';
    $end = (!empty($rawEnd)) ? date("g:i A", strtotime($rawEnd)) : '';
    return trim($start . ($start && $end ? ' - ' . $end : $end));
}

/**
 * Draws a bold "X" mark for a checked box. Using plain Helvetica instead of
 * ZapfDingbats because ZapfDingbats is not embedded and some PDF viewers
 * (e.g. Chrome's built-in viewer) substitute it with the wrong glyph/color.
 */
function drawCheckmark($pdf, $x, $y)
{
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($x, $y);
    $pdf->Write(0, 'X');
}

/** Column x-positions for the 3x3 Nature of Activity checkbox grid (shared). */
function natureCheckboxPosition($act_type, $campusType)
{
    $cols = [52, 104.5, 149.1];
    $rows = ($campusType === 'off') ? [113.3, 116.9, 120] : [115.2, 118.4, 121.5];

    $map = [
        'Meeting/Fellowship'          => [0, 0],
        'Socialization'                => [1, 0],
        'Campaign/Recruitment'         => [2, 0],
        'Maintenance/Cleaning'         => [0, 1],
        'Contest/Competition'          => [1, 1],
        'Income Generating Activity'   => [2, 1],
        'Seminar/Training/Forum'       => [0, 2],
        'Extension/Outreach'           => [1, 2],
        'Collection of Fees/Fines'     => [2, 2],
    ];

    if (!isset($map[$act_type])) {
        return null;
    }
    [$c, $r] = $map[$act_type];
    return [$cols[$c], $rows[$r]];
}

function loadTemplate($templateFile)
{
    if (!file_exists($templateFile)) {
        http_response_code(500);
        die('Template file not found: ' . $templateFile);
    }
    $width = 215.9;
    $height = 330.2;
    $pdf = new Fpdi();
    $pdf->setSourceFile($templateFile);
    $tplIdx = $pdf->importPage(1);
    $pdf->addPage('P', array($width, $height));
    $pdf->useTemplate($tplIdx, 0, 0, $width, $height);
    $pdf->SetTextColor(0, 0, 0);
    return $pdf;
}

// ============================================================
// IN-CAMPUS
// ============================================================
function renderInCampusPermit($post)
{
    $templateFile = __DIR__ . '/../premade/RSO Activity Permit (In-Campus).pdf';
    $pdf = loadTemplate($templateFile);

    $currentYear = date('Y');
    $schoolYear = $currentYear . '-' . ($currentYear + 1);

    // School Year
    $pdf->SetFont('Helvetica', '', 14);
    $pdf->SetXY(97, 31.3);
    $pdf->Write(0, $schoolYear);

    // Name of RSO
    $name = $post['rso_name'] ?? '';
    if (strlen($name) > 30) {
        $pdf->SetFont('Helvetica', '', 10);
        $lineHeight = 4;
    } else {
        $pdf->SetFont('Helvetica', '', 12);
        $lineHeight = 5;
    }
    $pdf->SetXY(50, 100.1);
    $pdf->MultiCell(150, $lineHeight, $name, 0, 'L');

    // Activity Title
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetXY(50, 110.5);
    $pdf->Write(0, $post['act_title'] ?? '');

    // Nature of Activity
    $act_type = $post['act_type'] ?? '';
    $pos = natureCheckboxPosition($act_type, 'on');
    if ($pos) {
        drawCheckmark($pdf, $pos[0], $pos[1]);
    } elseif ($act_type === 'Others') {
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(66, 125.1);
        $pdf->Write(0, trim($post['other_text'] ?? ''));
    }

    // Objectives
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetXY(50, 130.0); $pdf->Write(0, $post['objectives1'] ?? '');
    $pdf->SetXY(50, 136.1); $pdf->Write(0, $post['objectives2'] ?? '');
    $pdf->SetXY(50, 142.2); $pdf->Write(0, $post['objectives3'] ?? '');

    // Schedule: Date/s, Time, Venue
    $dateStr = formatDateRange($post['act_start'] ?? '', $post['act_end'] ?? '');
    $timeStr = formatTimeRange($post['time_start'] ?? '', $post['time_end'] ?? '');
    $venue = $post['act_venue'] ?? '';

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetXY(30, 154.0);
    $pdf->Cell(37, 4, $dateStr, 0, 0, 'C');
    $pdf->SetXY(102, 154.0);
    $pdf->Cell(20, 4, $timeStr, 0, 0, 'C');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(157, 151.0);
    $pdf->Cell(50, 10, $venue, 0, 0, 'L');

    // Signature block: "Activity Permit requested by:" is signed by the RSO
    // Adviser on this template. College Dean / SOAU Unit Head rows are
    // signed by hand later and intentionally left blank.
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetXY(75, 204.6);
    $pdf->Write(0, $post['adviser_name'] ?? '');
    $pdf->SetXY(170, 204.6);
    $pdf->Write(0, date('F d, Y'));

    return $pdf;
}

// ============================================================
// OUT-OF-CAMPUS
// ============================================================
function renderOffCampusPermit($post)
{
    $templateFile = __DIR__ . '/../premade/RSO Activity Permit (Out-of-Campus).pdf';
    $pdf = loadTemplate($templateFile);

    $currentYear = date('Y');
    $schoolYear = $currentYear . '-' . ($currentYear + 1);

    // School Year
    $pdf->SetFont('Helvetica', '', 14);
    $pdf->SetXY(97, 31.3);
    $pdf->Write(0, $schoolYear);

    // Name of RSO
    $name = $post['rso_name'] ?? '';
    if (strlen($name) > 30) {
        $pdf->SetFont('Helvetica', '', 10);
        $lineHeight = 4;
    } else {
        $pdf->SetFont('Helvetica', '', 12);
        $lineHeight = 5;
    }
    $pdf->SetXY(50, 98.4);
    $pdf->MultiCell(150, $lineHeight, $name, 0, 'L');

    // Activity Title
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetXY(50, 108.7);
    $pdf->Write(0, $post['act_title'] ?? '');

    // Nature of Activity
    $act_type = $post['act_type'] ?? '';
    $pos = natureCheckboxPosition($act_type, 'off');
    if ($pos) {
        drawCheckmark($pdf, $pos[0], $pos[1]);
    } elseif ($act_type === 'Others') {
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(66, 122.6);
        $pdf->Write(0, trim($post['other_text'] ?? ''));
    }

    // Objectives
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetXY(50, 128.3); $pdf->Write(0, $post['objectives1'] ?? '');
    $pdf->SetXY(50, 134.4); $pdf->Write(0, $post['objectives2'] ?? '');
    $pdf->SetXY(50, 140.5); $pdf->Write(0, $post['objectives3'] ?? '');

    // Schedule: Date/s, Time, Venue
    $dateStr = formatDateRange($post['act_start'] ?? '', $post['act_end'] ?? '');
    $timeStr = formatTimeRange($post['time_start'] ?? '', $post['time_end'] ?? '');
    $venue = $post['act_venue'] ?? '';

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetXY(30, 152.3);
    $pdf->Cell(46, 4, $dateStr, 0, 0, 'C');
    $pdf->SetXY(102, 152.3);
    $pdf->Cell(26, 4, $timeStr, 0, 0, 'C');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(157, 149.3);
    $pdf->Cell(50, 10, $venue, 0, 0, 'L');

    // Assembly Time / Assembly Point / Staging Area
    // NOTE: reg_permit.php does not currently collect assembly_time,
    // assembly_point, or staging_area - these print blank until the form
    // is updated to send them.
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(70, 160.0);
    $pdf->Write(0, $post['assembly_time'] ?? '');
    $pdf->SetXY(146, 160.0);
    $pdf->Write(0, $post['assembly_point'] ?? '');
    $pdf->SetXY(38, 165.5);
    $pdf->Write(0, $post['staging_area'] ?? '');

    // Signature block: this template has no "requested by / RSO Adviser"
    // line. College Dean / SOAU Unit Head / VP Academic Affairs all sign
    // by hand, so nothing is auto-filled here.

    return $pdf;
}