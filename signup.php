<?php
session_start();
try {
    require_once 'db_config.php';
} catch (Exception $e) {
    header('Location: signup.html?error=system');
    exit;
}
$userName = trim($_POST['userName'] ?? '');
$fullName = trim($_POST['fullName'] ?? 'New User');
$email = trim($_POST['email'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
if ($userName === '' || $password === '' || $email === '') {
    header('Location: signup.html?error=missing');
    exit;
}

if ($password !== $confirmPassword) {
    header('Location: signup.html?error=mismatch');
    exit;
}
try {
$checkSql = 'SELECT id FROM user_name WHERE userName = ? OR email = ? LIMIT 1';
$checkStmt = mysqli_prepare($con, $checkSql);
if (!$checkStmt) {
    header('Location: signup.html?error=system');
    exit;
}
mysqli_stmt_bind_param($checkStmt, 'ss', $userName, $email);
mysqli_stmt_execute($checkStmt);
if (!function_exists('mysqli_stmt_get_result')) {
    mysqli_stmt_store_result($checkStmt);
    $exists = mysqli_stmt_num_rows($checkStmt) > 0;
} else {
    $checkRes = mysqli_stmt_get_result($checkStmt);
    $exists = ($checkRes && mysqli_fetch_assoc($checkRes));
}
if ($exists) {
    mysqli_stmt_close($checkStmt);
    header('Location: signup.html?error=exists');
    exit;
}
mysqli_stmt_close($checkStmt);
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$initialBalance = 0.00; 
$accountType = 'Standard Member';
$accountNumber = 'SB' . rand(10000000, 99999999);
$insertSql = 'INSERT INTO user_name (userName, full_name, email, mobile, password, balance, `account type`, account_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
$insertStmt = mysqli_prepare($con, $insertSql);
if (!$insertStmt) {
    header('Location: signup.html?error=system');
    exit;
}
mysqli_stmt_bind_param($insertStmt, 'sssssdss', $userName, $fullName, $email, $mobile, $hashedPassword, $initialBalance, $accountType, $accountNumber);
if (mysqli_stmt_execute($insertStmt)) {
    $newUserId = mysqli_insert_id($con);
    $txSql = "INSERT INTO transactions (user_id, description, transaction_type, amount, status) VALUES (?, 'Welcome Bonus Deposit', 'Credit', ?, 'Completed')";
    $txStmt = mysqli_prepare($con, $txSql);
    if ($txStmt) {
        mysqli_stmt_bind_param($txStmt, 'id', $newUserId, $initialBalance);
        mysqli_stmt_execute($txStmt);
        mysqli_stmt_close($txStmt);
    }
    mysqli_stmt_close($insertStmt);
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['username'] = $userName;
    mysqli_close($con);
    header('Location: setup_profile.html');
    exit;
}
mysqli_stmt_close($insertStmt);
mysqli_close($con);
} catch (Exception $e) {
    header('Location: signup.html?error=system');
    exit;
}
header('Location: signup.html?error=system');
exit;
?>
