        <?php

        session_start();

        require_once 'db_config.php';

        $userName = trim($_POST['userName'] ?? '');
        $fullName = trim($_POST['fullName'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        if (empty($userName) || empty($fullName) || empty($password) || empty($confirmPassword)) {

            header("Location: signup.html?error=missing");
            exit;
        }

        if ($password !== $confirmPassword) {

            header("Location: signup.html?error=mismatch");
            exit;
        }




        $checkSql = "SELECT id
                    FROM user_name
                    WHERE userName = ?
                    LIMIT 1";

        $checkStmt = mysqli_prepare($con, $checkSql);

        if (!$checkStmt) {

            header("Location: signup.html?error=system");
            exit;
        }

        mysqli_stmt_bind_param($checkStmt, "s", $userName);

        mysqli_stmt_execute($checkStmt);

        $result = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($result)) {

            mysqli_stmt_close($checkStmt);

            header("Location: signup.html?error=exists");

            exit;
        }

        mysqli_stmt_close($checkStmt);



        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);



        $balance = 0.00;

        $accountType = "saving";



        $insertSql = "INSERT INTO user_name
        (userName, full_name, password, balance, `account type`)
        VALUES (?, ?, ?, ?, ?)";


        $insertStmt = mysqli_prepare($con, $insertSql);

        if (!$insertStmt) {

            die(mysqli_error($con));
        }


        mysqli_stmt_bind_param(
            $insertStmt,
            "sssds",
            $userName,
            $fullName,
            $hashedPassword,
            $balance,
            $accountType
        );


        if (!mysqli_stmt_execute($insertStmt)) {

            die(mysqli_stmt_error($insertStmt));
        }


        $newUserId = mysqli_insert_id($con);


        /* Create Session */

        $_SESSION['user_id'] = $newUserId;

        $_SESSION['username'] = $userName;


        mysqli_stmt_close($insertStmt);

        mysqli_close($con);




        header("Location: banklogin.html?success=setup");

        exit;

        ?>