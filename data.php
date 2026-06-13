<?php
if (session_status() === PHP_SESSION_NONE) {
    if (PHP_VERSION_ID >= 70300) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    }
    session_start();
}
require_once 'db_config.php';
if (!$con || mysqli_connect_errno()) {
    header("Location: banklogin.html?error=system");
    exit;
}
$usernameOrEmail = trim($_POST["username"] ?? '');
$password = $_POST["password"] ?? '';
if (empty($usernameOrEmail) || empty($password)) {
    header("Location: banklogin.html?error=missing");
    exit;
}
try {
    $sql = "SELECT id, userName, password FROM user_name WHERE userName = ? OR email = ? LIMIT 1";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) throw new mysqli_sql_exception("Statement preparation failed.");
    mysqli_stmt_bind_param($stmt, "ss", $usernameOrEmail, $usernameOrEmail);
    if (!mysqli_stmt_execute($stmt)) {
        header("Location: banklogin.html?error=system");
        exit;
    }
    if (function_exists('mysqli_stmt_get_result')) {
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
    } else {
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $r_id, $r_user, $r_pass);
        if (mysqli_stmt_fetch($stmt)) {
            $row = ['id' => $r_id, 'userName' => $r_user, 'password' => $r_pass];
        } else {
            $row = null;
        }
    }
    if ($row) {
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['userName'];
            $upd = mysqli_prepare($con, "UPDATE user_name SET last_login = NOW() WHERE id = ?");
            if ($upd) {
                mysqli_stmt_bind_param($upd, "i", $row['id']);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);
            }
            header("Location: dashboard.php");
            exit;
        } else {
            header("Location: banklogin.html?error=invalid");
            exit;
        }
    } else {
        header("Location: banklogin.html?error=notfound");
        exit;
    }
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    header("Location: banklogin.html?error=system");
    exit;
}
mysqli_close($con);
?>