<?php
include "db.php";

// Get filter values
$selected_date = $_GET['date'] ?? '';
$selected_staff = $_GET['staff_id'] ?? '';
$selected_job = $_GET['job_id'] ?? '';

// Fetch staff list
$staff_list = $conn->query("SELECT * FROM staff ORDER BY name ASC");

// Fetch job list
$job_list = $conn->query("SELECT * FROM jobs ORDER BY title ASC");

// Main query
$query = "
SELECT 
    a.id,
    s.name AS staff_name,
    j.title AS job_title,
    a.assigned_date
FROM assignments a
JOIN staff s ON a.staff_id = s.id
JOIN jobs j ON a.job_id = j.id
WHERE 1=1
";

// Apply filters
if (!empty($selected_date)) {
    $query .= " AND a.assigned_date = '$selected_date'";
}

if (!empty($selected_staff)) {
    $query .= " AND a.staff_id = '$selected_staff'";
}

if (!empty($selected_job)) {
    $query .= " AND a.job_id = '$selected_job'";
}

$query .= " ORDER BY a.assigned_date DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Job Posting History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

    <h2 class="mb-4 text-center">📌 Job Posting History</h2>

    <!-- Filter Form -->
    <form method="GET" class="row g-3 mb-4 bg-white p-3 rounded shadow">

        <div class="col-md-4">
            <label class="form-label">Select Date</label>
            <input type="date" name="date" class="form-control" value="<?= $selected_date ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Select Staff</label>
            <select name="staff_id" class="form-control">
                <option value="">-- All Staff --</option>
                <?php while($staff = $staff_list->fetch_assoc()): ?>
                    <option value="<?= $staff['id'] ?>"
                        <?= ($selected_staff == $staff['id']) ? "selected" : "" ?>>
                        <?= $staff['name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Select Job Title</label>
            <select name="job_id" class="form-control">
                <option value="">-- All Jobs --</option>
                <?php while($job = $job_list->fetch_assoc()): ?>
                    <option value="<?= $job['id'] ?>"
                        <?= ($selected_job == $job['id']) ? "selected" : "" ?>>
                        <?= $job['title'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary px-4">Search</button>
            <a href="job_history.php" class="btn btn-secondary px-4">Reset</a>
        </div>

    </form>

    <!-- Table -->
    <div class="table-responsive bg-white p-3 rounded shadow">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Staff Name</th>
                    <th>Job Title</th>
                    <th>Date Posted</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['staff_name'] ?></td>
                            <td><?= $row['job_title'] ?></td>
                            <td><?= $row['assigned_date'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-danger fw-bold">
                            No job posting found!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
