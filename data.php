        <?php
        session_start();
        require_once "db_config.php";
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            header("Location: banklogin.html");
            exit;
        }
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        if (empty($username) || empty($password)) {
            header("Location: banklogin.html?error=missing");
            exit;
        }
        try {
            $sql = "SELECT id, userName, password
                    FROM user_name
                    WHERE userName = ?
                    LIMIT 1";
            $stmt = mysqli_prepare($con, $sql);
            if (!$stmt) {
                throw new Exception("Statement Error");
            }
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            if ($row) {
                if (password_verify($password, $row["password"])) {
                    session_regenerate_id(true);
                    $_SESSION["user_id"] = $row["id"];
                    $_SESSION["username"] = $row["userName"];
                    $updateSql = "UPDATE user_name
                                SET last_login = NOW()
                                WHERE id = ?";
                    $updateStmt = mysqli_prepare($con, $updateSql);
                    if ($updateStmt) {
                        mysqli_stmt_bind_param($updateStmt, "i", $row["id"]);
                        mysqli_stmt_execute($updateStmt);
                        mysqli_stmt_close($updateStmt);
                    }
                    mysqli_close($con);
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
        } catch (Exception $e) {
            header("Location: banklogin.html?error=system");
            exit;
        }
        mysqli_close($con);
        ?>

