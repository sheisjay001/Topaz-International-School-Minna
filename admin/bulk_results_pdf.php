<?php
include __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$selected_class = $_GET['class'] ?? '';
$selected_term = $_GET['term'] ?? '';
$selected_session = $_GET['session'] ?? '';
if (!$selected_class || !$selected_term || !$selected_session) {
    die('Missing parameters');
}
$classes = ['Playgroup', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6', 'JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3'];
if (!in_array($selected_class, $classes)) {
    die('Invalid class');
}
$students_data = [];
$stmt = $conn->prepare("SELECT * FROM students WHERE class = ? ORDER BY full_name");
$stmt->bind_param("s", $selected_class);
$stmt->execute();
$result = $stmt->get_result();
while ($student = $result->fetch_assoc()) {
    $r_stmt = $conn->prepare("SELECT * FROM results WHERE student_id = ? AND term = ? AND session = ?");
    $r_stmt->bind_param("iss", $student['id'], $selected_term, $selected_session);
    $r_stmt->execute();
    $res_result = $r_stmt->get_result();
    $results = [];
    while ($r = $res_result->fetch_assoc()) {
        $results[] = $r;
    }
    if (!empty($results)) {
        $student['results'] = $results;
        $students_data[] = $student;
    }
}
if (empty($students_data)) {
    die('No results found');
}
$html = '<html><head><meta charset="UTF-8"><style>body{background:#fff;font-family:sans-serif} .report-card{width:100%;padding:10mm;margin-bottom:10mm;page-break-after:always} .school-header{text-align:center;border-bottom:2px solid #000;padding-bottom:5px;margin-bottom:10px} .school-logo{width:60px;height:60px} .student-info{margin-bottom:10px;font-size:12px} table{width:100%;border-collapse:collapse;font-size:12px} th,td{border:1px solid #000;padding:4px;text-align:center} th{text-align:center;background:#eee} td:first-child{text-align:left;font-weight:bold} .signature-section{margin-top:20px;display:flex;justify-content:space-between} .signature-box{text-align:center;border-top:1px solid #000;padding-top:5px;width:200px;font-size:12px} .footer{margin-top:10px;text-align:center;color:#777;font-size:10px}</style></head><body>';
foreach ($students_data as $std) {
    $html .= '<div class="report-card">';
    $html .= '<div class="school-header"><div><h2 style="margin:0;text-transform:uppercase">Topaz International School</h2><div>Minna, Niger State, Nigeria</div><div style="font-size:12px"><strong>Motto:</strong> Excellence in Education</div></div><h4 style="margin-top:8px;text-transform:uppercase">Termly Report Sheet</h4></div>';
    $html .= '<div class="student-info"><div><strong>Name:</strong> ' . strtoupper($std['full_name']) . '</div><div><strong>Admission No:</strong> ' . $std['admission_no'] . '</div><div><strong>Class:</strong> ' . $std['class'] . '</div><div><strong>Session:</strong> ' . $selected_session . ' | <strong>Term:</strong> ' . $selected_term . '</div></div>';
    $html .= '<table><thead><tr><th>Subject</th><th>CA 1 (20)</th><th>CA 2 (20)</th><th>Exam (60)</th><th>Total (100)</th><th>Grade</th><th>Remark</th></tr></thead><tbody>';
    foreach ($std['results'] as $res) {
        $html .= '<tr><td>' . htmlspecialchars($res['subject']) . '</td><td>' . (int)$res['ca1'] . '</td><td>' . (int)$res['ca2'] . '</td><td>' . (int)$res['exam'] . '</td><td>' . (int)$res['total'] . '</td><td>' . htmlspecialchars($res['grade']) . '</td><td>' . htmlspecialchars($res['remark']) . '</td></tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<div class="signature-section"><div class="signature-box">Class Teacher\'s Signature</div><div class="signature-box">Principal\'s Signature & Stamp</div></div>';
    $html .= '<div class="footer">Generated on ' . date('d M Y, h:i A') . ' from TISM Portal</div>';
    $html .= '</div>';
}
$html .= '</body></html>';
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    header('Content-Type: text/html; charset=utf-8');
    echo 'PDF library not installed. Please install dompdf via Composer.';
    exit();
}
require $vendorAutoload;
use Dompdf\Dompdf;
use Dompdf\Options;
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = 'results_' . preg_replace('/[^A-Za-z0-9]+/', '_', $selected_class . '_' . $selected_term . '_' . $selected_session) . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
