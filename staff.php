<?php
include "db.php";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/";
// Insert Staff
if (isset($_POST['add_staff'])) {
    $name = trim($_POST['name']);
    $name  = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO staff (name, phone) VALUES (:name, :phone)");
        $stmt->execute([
            'name'  => $name,
            'phone' => $phone
        ]);
        header("Location: staff.php");
        exit;
    }
}

// Update Staff
if (isset($_POST['update_staff'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE staff SET name = :name, phone = :phone WHERE id = :id");
        $stmt->execute([
            'name'  => $name,
            'phone' => $phone,
            'id'    => $id
        ]);
        header("Location: staff.php");
        exit;
    }
}

// Delete Staff
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM staff WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header("Location: staff.php");
    exit;
}

// Fetch staff list
$stmt = $pdo->prepare("SELECT * FROM staff ORDER BY id ASC");
$stmt->execute();
$staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch staff for edit
$editStaff = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editStaff = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Staff Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-4">

        <h2 class="text-center mb-4">👨‍💼 Staff Management</h2>
        <div class=""><a href="<?= $base_url ?>">Home</a></div>
        <div class=""><a href="<?= $base_url ?>jobs"> Add Job Roles</a></div>
        <div class=""><a href="<?= $base_url ?>staff">Add staff</a></div>

        <!-- Add / Update Form -->
        <div class="card shadow mb-4">
            <div class="card-header bg-dark text-white">
                <?= $editStaff ? "Update Staff" : "Add Staff"; ?>
            </div>

            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <input type="text" name="name" class="form-control"
                                placeholder="Enter staff name"
                                value="<?= $editStaff['name'] ?? '' ?>" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="phone" class="form-control"
                                placeholder="Enter phone number"
                                value="<?= $editStaff['phone'] ?? '' ?>" required>
                        </div>

                        <div class=" col-md-4">
                            <?php if ($editStaff): ?>
                                <input type="hidden" name="id" value="<?= $editStaff['id'] ?>">
                                <button type="submit" name="update_staff" class="btn btn-warning w-100">
                                    Update Staff
                                </button>
                            <?php else: ?>
                                <button type="submit" name="add_staff" class="btn btn-primary w-100">
                                    Add Staff
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <!-- Staff Table -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                Staff List
            </div>

            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Staff Name</th>
                            <th>Phone</th>
                            <th width="200">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($staffList) > 0): ?>
                            <?php foreach ($staffList as $staff): ?>
                                <tr>
                                    <td><?= $staff['id'] ?></td>
                                    <td><?= htmlspecialchars($staff['name']) ?></td>
                                    <td><?= htmlspecialchars((string)($staff['phone'] ?? '')) ?></td>
                                    <td>
                                        <a href="staff.php?edit=<?= $staff['id'] ?>" class="btn btn-sm btn-success">
                                            Edit
                                        </a>

                                        <a href="staff.php?delete=<?= $staff['id'] ?>"
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
                                    No staff found!
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