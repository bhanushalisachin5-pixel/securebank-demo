        <?php
        session_start();
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            header('Location: banklogin.html');
            exit;
        }

        $username = (string)($_SESSION['username'] ?? 'Valued Client');
        $userId = (int)($_SESSION['user_id'] ?? 0);

        require_once 'db_config.php';

        $balance = 0.00;
        $accountType = 'Standard Member';
        $income = 0.00;
        $expenses = 0.00;
        $goal = 0.00;
        $accountNumber = '0000000000';
        $transactions = [];

        try {

            $sql = "SELECT balance, `account type`, monthly_income, monthly_expenses, savings_goal, account_number
                    FROM user_name
                    WHERE id = ?";

            $stmt = mysqli_prepare($con, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $userId);
                mysqli_stmt_execute($stmt);

                if (function_exists('mysqli_stmt_get_result')) {
                    $result = mysqli_stmt_get_result($stmt);
                    $row = mysqli_fetch_assoc($result);
                } else {
                    mysqli_stmt_store_result($stmt);
                    mysqli_stmt_bind_result($stmt, $r_bal, $r_type, $r_inc, $r_exp, $r_goal, $r_acc);
                    $row = mysqli_stmt_fetch($stmt) ? [
                        'balance' => $r_bal,
                        'account type' => $r_type,
                        'monthly_income' => $r_inc,
                        'monthly_expenses' => $r_exp,
                        'savings_goal' => $r_goal,
                        'account_number' => $r_acc
                    ] : null;
                }

                if ($row) {
                    $balance = (float)($row['balance'] ?? 0);
                    $accountType = (string)($row['account type'] ?? 'Standard Member');
                    $income = (float)($row['monthly_income'] ?? 0);
                    $expenses = (float)($row['monthly_expenses'] ?? 0);
                    $goal = (float)($row['savings_goal'] ?? 0);
                    $accountNumber = (string)($row['account_number'] ?? '0000000000');
                }

                mysqli_stmt_close($stmt);
            }

            
            $txSql = "SELECT created_at, description, transaction_type, amount, status
                    FROM transactions
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                    LIMIT 5";

            $txStmt = mysqli_prepare($con, $txSql);
            if ($txStmt) {
                mysqli_stmt_bind_param($txStmt, "i", $userId);
                mysqli_stmt_execute($txStmt);

                if (function_exists('mysqli_stmt_get_result')) {
                    $txResult = mysqli_stmt_get_result($txStmt);
                    while ($txRow = mysqli_fetch_assoc($txResult)) {
                        $transactions[] = $txRow;
                    }
                } else {
                    mysqli_stmt_store_result($txStmt);
                    mysqli_stmt_bind_result($txStmt, $date, $desc, $type, $amt, $stat);
                    while (mysqli_stmt_fetch($txStmt)) {
                        $transactions[] = [
                            'created_at' => $date,
                            'description' => $desc,
                            'transaction_type' => $type,
                            'amount' => $amt,
                            'status' => $stat
                        ];
                    }
                }

                mysqli_stmt_close($txStmt);
            }

            mysqli_close($con);
        } catch (Throwable $e) {
        }

        include 'dashboard.html';
        ?>
