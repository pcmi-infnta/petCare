<?php
session_start();
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
   $fullname = htmlspecialchars($_POST['fullname'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_POST['role'], ENT_QUOTES, 'UTF-8');

    if ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else if (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long";
    } else {
        $checkQuery = "SELECT user_id FROM users WHERE email = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows == 0) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $conn->begin_transaction();
            
            try {
                $userQuery = "INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)";
                $userStmt = $conn->prepare($userQuery);
                $userStmt->bind_param("sss", $email, $password_hash, $role);
                $userStmt->execute();
                $userId = $conn->insert_id;
                
                $names = explode(" ", $fullname, 2);
                $firstName = $names[0];
                $lastName = isset($names[1]) ? $names[1] : '';
                
                $profileQuery = "INSERT INTO user_profiles (user_id, first_name, last_name) VALUES (?, ?, ?)";
                $profileStmt = $conn->prepare($profileQuery);
                $profileStmt->bind_param("iss", $userId, $firstName, $lastName);
                $profileStmt->execute();
                
                $conn->commit();
                
                $_SESSION['signup_success'] = "Registration successful! Please login.";
                header('Location: login.php');
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Registration failed. Please try again.";
            }
        } else {
            $error_message = "Email already exists";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Authentication System</title>
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
            content: "🐾";
            font-size: 2rem;
            color: white;
        }

        h1 {
            text-align: center;
            color: #1F2937;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
            position: relative;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
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
            transition: all 0.2s;
        }

        .btn:hover {
            background-color: #D97706;
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .footer {
            text-align: center;
            margin-top: 1rem;
        }

        .footer a {
            color: #8B5CF6;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer a:hover {
            color: #7C3AED;
        }

        .password-requirements {
            font-size: 0.8rem;
            color: #6B7280;
            margin-top: 0.25rem;
        }

        .pet-decoration {
            position: absolute;
            bottom: -40px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            filter: grayscale(0.5);
        }

        .view {
            display: none;
        }

        .view.active {
            display: block;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6B7280;
            cursor: pointer;
            font-size: 1rem;
        }

        .error-message {
            color: #DC2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }

        .success-message {
            color: #059669;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }

        .animated {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="container">
        <div class="paw-print"></div>
        <h1>Sign Up</h1>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="text" name="fullname" placeholder="Full Name" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
                <button type="button" class="toggle-password">👁️</button>
                <div class="password-requirements">Must contain at least 6 characters</div>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="button" class="toggle-password">👁️</button>
            </div>
            <div class="form-group">
    <select name="role" required style="width: 100%; padding: 0.75rem 1rem; border: 2px solid #E5E7EB; border-radius: 10px; font-size: 1rem; transition: border-color 0.2s; background-color: white;">
        <option value="user">User</option>
        <option value="admin">Admin</option>
    </select>
</div>

            <?php if (isset($error_message)): ?>
                <div class="error-message" style="color: red; margin-bottom: 10px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn">Sign Up</button>
            <div class="footer">
                <p>Already have an account? <a href="login.php">Log In</a></p>
            </div>
        </form>
        <div class="pet-decoration">🐶</div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = '🔒';
                } else {
                    input.type = 'password';
                    this.textContent = '👁️';
                }
            });
        });
    </script>

</body>
</html>