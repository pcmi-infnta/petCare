<?php
session_start();
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    $query = "SELECT user_id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $updateQuery = "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sss", $token, $expiry, $email);
        $updateStmt->execute();
        
        $success_message = "Password reset instructions have been sent to your email.";
    } else {
        $error_message = "Email not found in our records.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Pet Care System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
    min-height: 100vh;
    background-image: url('../uploads/background.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    max-width: 400px;
    width: 90%;
    background: rgba(255, 255, 255, 0.95);
    padding: 2rem;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    position: relative;
    backdrop-filter: blur(8px);
}

        .paw-print {
            width: 60px;
            height: 60px;
            background-color: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -50px auto 20px;
        }

        .paw-print::before {
            content: "🔑";
            font-size: 2rem;
            color: white;
        }

        h1 {
            text-align: center;
            color: #1F2937;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        h2 {
            text-align: center;
            color: #4B5563;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: normal;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #8B5CF6;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background-color: #F59E0B;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #D97706;
        }

        .footer {
            text-align: center;
            margin-top: 1rem;
        }

        .footer a {
            color: #8B5CF6;
            text-decoration: none;
        }

        .success-message {
            color: #059669;
            background-color: #D1FAE5;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .error-message {
            color: #DC2626;
            background-color: #FEE2E2;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .pet-decoration {
            position: absolute;
            bottom: -40px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            filter: grayscale(0.5);
        }
        .copy-btn {
            background: #8B5CF6;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }
        .copy-btn:hover {
            background: #7C3AED;
        }
        #newPassword {
            background: #E5E7EB;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div class="container">
        <div class="paw-print"></div>
        <h1>Password Reset</h1>
        <h2>Enter your email to receive a new password</h2>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                    <button class="copy-btn" onclick="copyPassword()">Copy</button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <button type="submit" class="btn">Generate New Password</button>
            
            <div class="footer">
                <p><a href="login.php">Back to Login</a></p>
                <p style="margin-top: 0.5rem;">Don't have an account? <a href="signUP.php">Sign Up</a></p>
            </div>
        </form>
        <div class="pet-decoration">🐕</div>
    </div>

    <script>
    function copyPassword() {
        const passwordText = document.getElementById('newPassword').textContent;
        navigator.clipboard.writeText(passwordText).then(() => {
            alert('Password copied to clipboard!');
        });
    }
    </script>
</body>
</html>
