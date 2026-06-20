        <?php

        define('DB_HOST', 'localhost');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_NAME', 'bank');

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 

        try {
            $con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (!$con) {
                throw new Exception("Connection failed: " . mysqli_connect_error());
            }
            mysqli_set_charset($con, 'utf8mb4');
        } catch (Exception $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            header('Location: signup.html?error=system');
            exit;
        }