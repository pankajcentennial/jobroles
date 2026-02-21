<?php
include "db.php";
/*******************Base Url *************/
$env_base_url = getenv('BASE_URL');

// If environment variable exists, use it; otherwise calculate dynamically
if (!empty($env_base_url)) {
    $base_url = rtrim($env_base_url, '/') . '/';
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . ($dir ? $dir . '/' : '/');
}
/*******Export in CSV *******/




$activeJobTitleStmt = $pdo->prepare("
    SELECT DISTINCT j.title
    FROM assignments a
    JOIN jobs j ON j.id = a.job_id
    INNER JOIN (
        SELECT job_id, MAX(id) AS latest_id
        FROM assignments
        GROUP BY job_id
    ) latest
    ON a.id = latest.latest_id
    WHERE a.status = 2
");
$activeJobTitleStmt->execute();
$activeJobTitles = $activeJobTitleStmt->fetchAll(PDO::FETCH_COLUMN);



// Jobs which are ACTIVE (Posted but not Closed)
$postedJobsStmt = $pdo->prepare("
    SELECT a.job_id
    FROM assignments a
    INNER JOIN (
        SELECT job_id, MAX(id) AS latest_id
        FROM assignments
        GROUP BY job_id
    ) latest
    ON a.id = latest.latest_id
    WHERE a.status = 2
");
$postedJobsStmt->execute();
$activePostedJobIds = $postedJobsStmt->fetchAll(PDO::FETCH_COLUMN);


/*$postedJobsStmt = $pdo->prepare("
    SELECT DISTINCT j.jobroles_id
    FROM assignments a
    JOIN jobs j ON j.id = a.job_id
    JOIN (
        SELECT job_id, MAX(id) AS latest_id
        FROM assignments
        GROUP BY job_id
    ) latest
    ON a.id = latest.latest_id
    WHERE a.status = 2
");
$postedJobsStmt->execute();
$activeJobRoleIds = $postedJobsStmt->fetchAll(PDO::FETCH_COLUMN); */





$message = "";

// Today date
$today = date("Y-m-d");

if (isset($_POST['assign_job'])) {

    $staff_id = $_POST['staff_id'];
    $job_id = $_POST['job_id'];
    $assigned_date = $_POST['assigned_date'];

    // 1) Check if job already assigned on same date (any staff)
    $checkJob = $pdo->prepare("
        SELECT id FROM assignments
WHERE job_id = :job_id 
AND assigned_date = :assigned_date
AND status IN (1,2)
");
    $checkJob->execute([
        'job_id' => $job_id,
        'assigned_date' => $assigned_date
    ]);

    if ($checkJob->rowCount() > 0) {
        $message = "<div class='alert alert-danger'>❌ This job title is already assigned on this date!</div>";
    } else {

        // 2) Check 8 days rule for same staff + same job
        $checkStaffJob = $pdo->prepare("
            SELECT assigned_date FROM assignments
            WHERE staff_id = :staff_id AND job_id = :job_id
            ORDER BY assigned_date DESC
            LIMIT 1
        ");
        $checkStaffJob->execute([
            'staff_id' => $staff_id,
            'job_id' => $job_id
        ]);

        $last = $checkStaffJob->fetch(PDO::FETCH_ASSOC);

        if ($last) {
            $lastDate = $last['assigned_date'];
            $diffDays = (strtotime($assigned_date) - strtotime($lastDate)) / (60 * 60 * 24);

            if ($diffDays < 8) {
                $message = "<div class='alert alert-warning'>⚠️ Same staff cannot post same job within 8 days!</div>";
            }
        }

        // Insert if no error
        if ($message == "") {
            $insert = $pdo->prepare("
                INSERT INTO assignments (staff_id, job_id, assigned_date, status)
VALUES (:staff_id, :job_id, :assigned_date, 1)
");
            $insert->execute([
                'staff_id' => $staff_id,
                'job_id' => $job_id,
                'assigned_date' => $assigned_date
            ]);

            $message = "<div class='alert alert-success'>✅ Job Assigned Successfully!</div>";
        }
    }
}

// Fetch staff list
$staffStmt = $pdo->prepare("SELECT * FROM staff ORDER BY id ASC");
$staffStmt->execute();
$staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch jobs list
$jobStmt = $pdo->prepare("SELECT * FROM jobs ORDER BY title ASC");
$jobStmt->execute();
$jobList = $jobStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique dates
$dateStmt = $pdo->prepare("SELECT DISTINCT assigned_date FROM assignments ORDER BY assigned_date DESC");
$dateStmt->execute();
$dates = $dateStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch assignments data
$assignStmt = $pdo->prepare("
    SELECT a.assigned_date, a.staff_id, j.title, a.status, a.job_id, a.applicants_count
    FROM assignments a
    JOIN jobs j ON a.job_id = j.id
");
$assignStmt->execute();
$assignData = $assignStmt->fetchAll(PDO::FETCH_ASSOC);

// Store assignments in array
$assignments = [];
foreach ($assignData as $row) {
    $assignments[$row['assigned_date']][$row['staff_id']] = [
        "title" => $row['title'],
        "status" => $row['status'],
        "job_id" => $row['job_id'],
        "applicants_count" => $row['applicants_count']

    ];
}
if (isset($_POST['status'])) {

    $staff_id = $_POST['staff_id'];
    $assigned_date = $_POST['assigned_date'];
    $status = $_POST['status'];

    $update = $pdo->prepare("
        UPDATE assignments
        SET status = :status
        WHERE staff_id = :staff_id AND assigned_date = :assigned_date
    ");

    $update->execute([
        'status' => $status,
        'staff_id' => $staff_id,
        'assigned_date' => $assigned_date
    ]);

    header("Location: index.php");
    exit;
}
if (isset($_POST['delete_assignment'])) {

    $staff_id = $_POST['staff_id'];
    $assigned_date = $_POST['assigned_date'];

    // delete only if status = 1
    $delete = $pdo->prepare("
        DELETE FROM assignments
        WHERE staff_id = :staff_id 
        AND assigned_date = :assigned_date
        AND status = 1
    ");

    $delete->execute([
        'staff_id' => $staff_id,
        'assigned_date' => $assigned_date
    ]);

    header("Location: index.php");
    exit;
}
if (isset($_POST['update_applicants'])) {

    $staff_id = $_POST['staff_id'];
    $assigned_date = $_POST['assigned_date'];
    $job_id = $_POST['job_id'];
    $applicants_count = $_POST['applicants_count'];

    $update = $pdo->prepare("
        UPDATE assignments
        SET applicants_count = :applicants_count
        WHERE staff_id = :staff_id
        AND assigned_date = :assigned_date
        AND job_id = :job_id
    ");

    $update->execute([
        'applicants_count' => $applicants_count,
        'staff_id' => $staff_id,
        'assigned_date' => $assigned_date,
        'job_id' => $job_id
    ]);

    header("Location: index.php");
    exit;
}
$selected_date = $_POST['assigned_date'] ?? date("Y-m-d");
$staffStmt = $pdo->prepare("
    SELECT *
    FROM staff
    WHERE id NOT IN (
        SELECT staff_id
        FROM assignments
        WHERE assigned_date = :assigned_date
        AND status IN (1,2)
    )
    ORDER BY id ASC
");
$staffStmt->execute(['assigned_date' => $selected_date]);
$staffListDropdown = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

$selected_staff_id = isset($_POST['staff_id']) && $_POST['staff_id'] !== "" ? (int)$_POST['staff_id'] : null;


if ($selected_staff_id !== null) {
    $jobStmt = $pdo->prepare("
        SELECT *
        FROM jobs
        WHERE id NOT IN (
            SELECT job_id
            FROM assignments
            WHERE staff_id = :staff_id
            AND status IN (2,3)
            AND assigned_date >= CURRENT_DATE - INTERVAL '8 days'
        )
        ORDER BY title ASC
    ");
    $jobStmt->execute(['staff_id' => $selected_staff_id]);
} else {

    $jobStmt = $pdo->prepare("SELECT * FROM jobs ORDER BY title ASC");
    $jobStmt->execute();
}

$jobListDropdown = $jobStmt->fetchAll(PDO::FETCH_ASSOC);


/*
$jobStmt = $pdo->prepare("
    SELECT *
    FROM jobs
    WHERE id NOT IN (
        SELECT job_id
        FROM assignments
        WHERE staff_id = :staff_id
        AND status IN (2,3)
        AND assigned_date >= CURRENT_DATE - INTERVAL '8 days'
    )
    ORDER BY title ASC
");
$jobStmt->execute(['staff_id' => $selected_staff_id]);
$jobListDropdown = $jobStmt->fetchAll(PDO::FETCH_ASSOC);*/




?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Daily Job Assign Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- TOP NAVBAR -->
    <header class="top-navbar">
        <div class="logo">⚡ AdminPanel</div>
        <nav class="nav-links">
            <a href="<?= $base_url ?>">🏠 Home</a>
            <a href="<?= $base_url ?>jobs"> 📌 Add Job Roles</a>
            <a href="<?= $base_url ?>staff">👥 Add Staff</a>
            <a href="<?= $base_url ?>exportrecords?download=1">⚙ Settings</a>
        </nav>
        <div class="nav-right">
            <button id="darkToggle">🌙 Dark Mode</button>
            <div class="profile">👩 Admin</div>
        </div>
    </header>
    <?= $message; ?>
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Assign Job Form -->

        <div class="page-title">
            <h2>Daily Job Assignment</h2>
        </div>

        <div class="card fade-in">
            <h2>Assign Job</h2>
            <form method="POST" class="form-grid">


                <div>
                    <label>Select Date</label>
                    <input type="date" name="assigned_date" class="form-control" value="<?= $today ?>" required>
                </div>

                <div>
                    <label>Select Staff</label>
                    <select name="staff_id" class="form-control" onchange="this.form.submit()" required>
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staffListDropdown as $staff): ?>
                            <option value="<?= $staff['id'] ?>"
                                <?= (!empty($_POST['staff_id']) && $_POST['staff_id'] == $staff['id']) ? "selected" : "" ?>>
                                <?= htmlspecialchars($staff['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>

                <div>
                    <label>Select Jobrole</label>
                    <select name="job_id" class="form-control" required>
                        <option value="">-- Select Job --</option>
                        <?php foreach ($jobListDropdown  as $job): ?>
                            <option value="<?= $job['id'] ?>">
                                <?= htmlspecialchars($job['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <button type="submit" name="assign_job" class="btn btn-primary px-5 mt-2">
                    Assign Job
                </button>


            </form>
        </div>


        <!-- Pivot Table Report -->
        <div class="card fade-in">
            <h2>Assignment Report</h2>
            <div class="table-wrapper">
                <table id="reportTable">

                    <thead>
                        <tr>
                            <th>Date</th>
                            <?php foreach ($staffList as $staff): ?>
                                <th><?= htmlspecialchars($staff['name']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($dates as $d): ?>
                            <?php $date = $d['assigned_date']; ?>
                            <tr>
                                <td><b><?= date("m-d-Y", strtotime($date)) ?></b></td>

                                <?php foreach ($staffList as $staff): ?>
                                    <td style="
<?php
                                    $staffId = $staff['id'];

                                    if (isset($assignments[$date][$staffId])) {
                                        $status = $assignments[$date][$staffId]['status'];

                                        if ($status == 1) echo 'background-color:yellow;';
                                        if ($status == 2) echo 'background-color:lightgreen;';
                                        if ($status == 3) echo 'background-color:#ff9999;';
                                    }
?>
">
                                        <div class="cell-header">
                                            <div class="action-icons">

                                                <?php
                                                $jobId = null; // default
                                                $status = null;
                                                $jobTitle = "";
                                                $applicants = 0;
                                                if (isset($assignments[$date][$staffId])) {

                                                    $jobTitle = $assignments[$date][$staffId]['title'];
                                                    $status = (int) trim($assignments[$date][$staffId]['status']) ?? null;

                                                    // Status text
                                                    $statusText = "";
                                                    if ($status == 1) $statusText = "Ready to Post";
                                                    if ($status == 2) $statusText = "Posted";
                                                    if ($status == 3) $statusText = "Close";
                                                    if ($status !== null) {


                                                        // Copy Button
                                                        $jobId = $assignments[$date][$staffId]['job_id'] ?? null;


                                                        // Copy Button (hide if job is active posted anywhere)
                                                        //$jobTitle = $row['title'];
                                                        $jobDate  = $row['assigned_date'];
                                                        $isFutureJob = ($date >= date("Y-m-d"));

                                                        if ($isFutureJob && in_array($jobTitle, $activeJobTitles)) {
                                                            echo "<span class='status-badge'>Already Active</span>";
                                                        } else {
                                                            echo "<button class='btn btn-sm btn-success icon-btn icon-copy'
        onclick=\"copyText('" . htmlspecialchars($jobTitle, ENT_QUOTES) . "', '" . htmlspecialchars($staff['name'], ENT_QUOTES) . "')\">
        <i class='fas fa-copy'></i>
    </button>";
                                                            $phone = $staff["phone"];
                                                            if (!empty($phone)) {

                                                                $cleanPhone = preg_replace('/[^0-9]/', '', $phone); // remove spaces etc

                                                                $message = $jobTitle;

                                                                echo "<a target='_blank'
        class='btn btn-sm btn-success mt-1 icon-btn icon-wa'
        href='https://wa.me/" . $cleanPhone . "?text=" . urlencode($message) . "'>
        <i class='fab fa-whatsapp'></i>
    </a></div></div>";
                                                            }
                                                            echo "<span class='job-title'>" . htmlspecialchars($jobTitle) . "</span> ";
                                                            if ($status == 1) {
                                                                echo "<form method='POST' style='margin-top:5px;' class='remove-icon-form'>
            <input type='hidden' name='staff_id' value='$staffId'>
            <input type='hidden' name='assigned_date' value='$date'>
            <button type='submit' name='delete_assignment' 
                    class='remove-icon-btn'
                    onclick=\"return confirm('Are you sure you want to remove this assignment?');\">
                ✖
            </button>
          </form>";
                                                            }

                                                            //echo "</div>";

                                                            // Dropdown

                                                            echo "<form method='POST' style='margin-top:5px;'>
            <input type='hidden' name='staff_id' value='$staffId' class='status-form'>
            <input type='hidden' name='assigned_date' value='$date'>

            <select name='status' class='status-select form-select form-select-sm' onchange='this.form.submit()'>
                <option value='1' " . ($status == 1 ? "selected" : "") . ">Ready to Post</option>
                <option value='2' " . ($status == 2 ? "selected" : "") . ">Posted</option>
                <option value='3' " . ($status == 3 ? "selected" : "") . ">Close</option>
            </select>
          </form>";
                                                        }
                                                    }
                                                } else {
                                                    echo "-";
                                                }

                                                if ($status == 3) {
                                                    $applicants = 0;
                                                    if (isset($assignments[$date][$staffId]['applicants_count'])) {
                                                        $applicants = $assignments[$date][$staffId]['applicants_count'];
                                                    }

                                                    echo "<form method='POST' style='margin-top:5px;'>
        <input type='hidden' name='staff_id' value='$staffId'>
        <input type='hidden' name='assigned_date' value='$date'>
        <input type='hidden' name='job_id' value='$jobId'>

        <input type='number' name='applicants_count' value='$applicants'
               class='form-control form-control-sm'
               min='0' placeholder='Applicants'>

        <button type='submit' name='update_applicants'
                class='btn btn-sm btn-dark w-100 mt-1'>
            Update
        </button>
      </form>";
                                                }
                                                ?>

                                    </td>


                                <?php endforeach; ?>
                            </tr>



                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

    </div>

    <script>
        function copyText(jobTitle, staffName) {
            navigator.clipboard.writeText(jobTitle).then(() => {
                alert("Copied: " + staffName + " - " + jobTitle);
            });
        }
    </script>



</body>

</html>