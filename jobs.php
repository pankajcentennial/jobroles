<?php
include "db.php";

// Insert Job
if (isset($_POST['add_job'])) {
    $title = trim($_POST['title']);

    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO jobs (title) VALUES (:title)");
        $stmt->execute(['title' => $title]);

        header("Location: jobs.php");
        exit;
    }
}

// Update Job
if (isset($_POST['update_job'])) {
    $id = $_POST['id'];
    $title = trim($_POST['title']);

    if (!empty($title)) {
        $stmt = $pdo->prepare("UPDATE jobs SET title = :title WHERE id = :id");
        $stmt->execute([
            'title' => $title,
            'id' => $id
        ]);

        header("Location: jobs.php");
        exit;
    }
}

// Delete Job
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header("Location: jobs.php");
    exit;
}

// Fetch job list
$stmt = $pdo->prepare("SELECT * FROM jobs ORDER BY id ASC");
$stmt->execute();
$jobList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch job for edit
$editJob = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editJob = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Job Roles Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-4">

        <h2 class="text-center mb-4">💼 Job Roles Management</h2>
        <div class=""><a href="<?= $base_url ?>">Home</a></div>
        <div class=""><a href="<?= $base_url ?>jobs"> Add Job Roles</a></div>
        <div class=""><a href="<?= $base_url ?>staff">Add staff</a></div>

        <!-- Add / Update Form -->
        <div class="card shadow mb-4">
            <div class="card-header bg-dark text-white">
                <?= $editJob ? "Update Job Role" : "Add Job Role"; ?>
            </div>

            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">

                        <div class="col-md-8">
                            <input type="text" name="title" class="form-control"
                                placeholder="Enter job title"
                                value="<?= $editJob['title'] ?? '' ?>" required>
                        </div>

                        <div class="col-md-4">
                            <?php if ($editJob): ?>
                                <input type="hidden" name="id" value="<?= $editJob['id'] ?>">
                                <button type="submit" name="update_job" class="btn btn-warning w-100">
                                    Update Job
                                </button>
                            <?php else: ?>
                                <button type="submit" name="add_job" class="btn btn-primary w-100">
                                    Add Job
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                Job Roles List
            </div>

            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Job Title</th>
                            <th width="200">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($jobList) > 0): ?>
                            <?php foreach ($jobList as $job): ?>
                                <tr>
                                    <td><?= $job['id'] ?></td>
                                    <td><?= htmlspecialchars($job['title']) ?></td>
                                    <td>
                                        <a href="jobs.php?edit=<?= $job['id'] ?>" class="btn btn-sm btn-success">
                                            Edit
                                        </a>

                                        <a href="jobs.php?delete=<?= $job['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-danger fw-bold">
                                    No job roles found!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>
        </div>

    </div>
</body>

</html>