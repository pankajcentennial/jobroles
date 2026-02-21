<?php
include "db.php";
try {

    echo "staff_id" . $staff_id = $_POST['newstaff_id'];
    echo $assigned_date = $_POST['assigned_date'];
    echo $job_id = $_POST['job_id'];
    echo $applicants_count = $_POST['applicants_count'];

    $update = $pdo->prepare("
        UPDATE assignments
        SET applicants_count = :applicants_count
        WHERE staff_id = :staff_id
        AND assigned_date = :assigned_date
        AND job_id = :job_id
    ");

    $result = $update->execute([
        'applicants_count' => $applicants_count,
        'staff_id' => $staff_id,
        'assigned_date' => $assigned_date,
        'job_id' => $job_id
    ]);

    if ($result) {
        header("Location: index.php");
        exit;
    } else {
        echo "Query failed";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
