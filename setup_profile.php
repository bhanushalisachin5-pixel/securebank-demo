<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: banklogin.html');
    exit;
}

$userId = $_SESSION['user_id'];
$balance = (float)($_POST['total_balance'] ?? 0);
$income = (float)($_POST['monthly_income'] ?? 0);
$expenses = (float)($_POST['monthly_expenses'] ?? 0);
$goal = (float)($_POST['savings_goal'] ?? 0);

$sql = "UPDATE user_name SET balance = ?, monthly_income = ?, monthly_expenses = ?, savings_goal = ? WHERE id = ?";
$stmt = mysqli_prepare($con, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ddddi", $balance, $income, $expenses, $goal, $userId);
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) >= 0) {
            // Clear the setup session and redirect to login with a success message
            session_unset();
            session_destroy();
            header('Location: banklogin.html?success=setup');
            exit;
        }
    }
    // If execution fails, capture the error for debugging
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($con);
header('Location: setup_profile.html?error=system');
exit;
?>