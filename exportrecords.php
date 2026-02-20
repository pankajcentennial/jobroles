<?php
include "db.php";

// Today + last 8 days (including today)
$today = date("Y-m-d");
$fromDate = date("Y-m-d", strtotime("-8 days"));

// Fetch staff
$staffStmt = $pdo->prepare("SELECT id, name FROM staff ORDER BY id ASC");
$staffStmt->execute();
$staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique dates (only last 8 days including today)
$dateStmt = $pdo->prepare("
    SELECT DISTINCT assigned_date::date AS assigned_date
    FROM assignments
    WHERE assigned_date::date BETWEEN CURRENT_DATE - INTERVAL '8 days' AND CURRENT_DATE
    ORDER BY assigned_date::date ASC
");
$dateStmt->execute();
$dates = $dateStmt->fetchAll(PDO::FETCH_ASSOC);
$assignmentStmt = $pdo->prepare("
    SELECT a.assigned_date::date AS assigned_date, a.staff_id, j.title
    FROM assignments a
    JOIN jobs j ON j.id = a.job_id
    WHERE a.assigned_date::date BETWEEN CURRENT_DATE - INTERVAL '8 days' AND CURRENT_DATE
");
$assignmentStmt->execute();
$assignmentsData = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);



// Store assignments in array
$assignments = [];
foreach ($assignmentsData as $row) {
    $assignments[$row['assigned_date']][$row['staff_id']] = $row['title'];
}

// Export CSV
$filename = "job_report_" . $fromDate . "_to_" . $today . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen("php://output", "w");

// Header row
$headerRow = ["Date"];
foreach ($staffList as $staff) {
    $headerRow[] = $staff['name'];
}
fputcsv($output, $headerRow);

// Data rows
foreach ($dates as $d) {
    $date = $d['assigned_date'];

    $row = [date("m/d/Y", strtotime($date))];

    foreach ($staffList as $staff) {
        $staffId = $staff['id'];

        $row[] = $assignments[$date][$staffId] ?? "";
    }

    fputcsv($output, $row);
}

fclose($output);
exit;
