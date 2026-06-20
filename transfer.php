            <?php
            /**
             * SecureBank - Fund Transfer Processor
             * Handles real-time balance updates and transaction logging
             */
            session_start();
            require_once 'db_config.php';

            header('Content-Type: application/json');

            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Session expired.']);
                exit;
            }

            $senderId = $_SESSION['user_id'];
            $recipientInput = trim($_POST['recipient'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $description = trim($_POST['description'] ?? 'Fund Transfer');

            if ($amount <= 0 || empty($recipientInput)) {
                echo json_encode(['success' => false, 'message' => 'Invalid amount or recipient info.']);
                exit;
            }

            // 1. Find Recipient (by account number OR username)
            $findSql = "SELECT id, account_number, userName FROM user_name WHERE (account_number = ? OR userName = ?) AND id != ? LIMIT 1";
            $stmt = mysqli_prepare($con, $findSql);
            mysqli_stmt_bind_param($stmt, "ssi", $recipientInput, $recipientInput, $senderId);
            mysqli_stmt_execute($stmt);

            if (function_exists('mysqli_stmt_get_result')) {
                $res = mysqli_stmt_get_result($stmt);
                $receiver = mysqli_fetch_assoc($res);
            } else {
                mysqli_stmt_store_result($stmt);
                mysqli_stmt_bind_result($stmt, $r_id, $r_acc, $r_name);
                $receiver = mysqli_stmt_fetch($stmt) ? ['id' => $r_id, 'account_number' => $r_acc, 'userName' => $r_name] : null;
            }
            mysqli_stmt_close($stmt);

            if (!$receiver) {
                echo json_encode(['success' => false, 'message' => 'Recipient not found.']);
                exit;
            }

            $receiverId = $receiver['id'];
            $receiverAcc = $receiver['account_number'];

            // 2. Execute Transfer with ACID consistency
            mysqli_begin_transaction($con);

            try {
                // Lock sender record for update
                $lockSql = "SELECT balance, account_number FROM user_name WHERE id = ? FOR UPDATE";
                $lockStmt = mysqli_prepare($con, $lockSql);
                mysqli_stmt_bind_param($lockStmt, "i", $senderId);
                mysqli_stmt_execute($lockStmt);
                
                if (function_exists('mysqli_stmt_get_result')) {
                    $sender = mysqli_fetch_assoc(mysqli_stmt_get_result($lockStmt));
                } else {
                    mysqli_stmt_store_result($lockStmt);
                    mysqli_stmt_bind_result($lockStmt, $s_bal, $s_acc);
                    $sender = mysqli_stmt_fetch($lockStmt) ? ['balance' => $s_bal, 'account_number' => $s_acc] : null;
                }

                if ($sender['balance'] < $amount) {
                    throw new Exception("Insufficient balance for this transfer.");
                }
                $senderAcc = $sender['account_number'];

                // 3. Update Balances using Prepared Statements for security and error checking
                $updSender = mysqli_prepare($con, "UPDATE user_name SET balance = balance - ? WHERE id = ?");
                mysqli_stmt_bind_param($updSender, "di", $amount, $senderId);
                if (!mysqli_stmt_execute($updSender)) {
                    throw new Exception("Failed to deduct from sender balance: " . mysqli_stmt_error($updSender));
                }
                mysqli_stmt_close($updSender);

                $updReceiver = mysqli_prepare($con, "UPDATE user_name SET balance = balance + ? WHERE id = ?");
                mysqli_stmt_bind_param($updReceiver, "di", $amount, $receiverId);
                if (!mysqli_stmt_execute($updReceiver)) {
                    throw new Exception("Failed to credit receiver balance: " . mysqli_stmt_error($updReceiver));
                }
                mysqli_stmt_close($updReceiver);

                // 4. Log Debit for Sender
                $logSql = "INSERT INTO transactions (user_id, transaction_type, amount, receiver_account, description, status) VALUES (?, ?, ?, ?, ?, 'Completed')";
                $logStmt = mysqli_prepare($con, $logSql);
                if (!$logStmt) throw new Exception("Transaction logging failed: " . mysqli_error($con));
                
                $typeD = 'Debit';
                mysqli_stmt_bind_param($logStmt, "isdss", $senderId, $typeD, $amount, $receiverAcc, $description);
                if (!mysqli_stmt_execute($logStmt)) throw new Exception("Failed to log sender debit.");

                // Log Credit for Receiver
                $typeC = 'Credit';
                $creditDesc = "Received from " . $_SESSION['username'];
                mysqli_stmt_bind_param($logStmt, "isdss", $receiverId, $typeC, $amount, $senderAcc, $creditDesc);
                if (!mysqli_stmt_execute($logStmt)) throw new Exception("Failed to log receiver credit.");

                mysqli_commit($con);
                echo json_encode(['success' => true, 'message' => "Successfully sent $" . number_format($amount, 2) . " to " . $receiver['userName']]);

            } catch (Exception $e) {
                mysqli_rollback($con);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }

            mysqli_close($con);