<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SLAppFlow Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #17191e;
            color: #f1f1f1;
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: #232632;
            border-radius: 18px;
            padding: 2.5rem 2.2rem 2rem 2.2rem;
            box-shadow: 0 4px 22px rgba(0,0,0,0.20);
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 340px;
        }
        .logo {
            width: 100px;
            margin-bottom: 1.3rem;
        }
        h2 {
            margin-top: 0;
            margin-bottom: 1.7rem;
            font-size: 1.45rem;
            letter-spacing: 1px;
            font-weight: 600;
            color: #69e6b3;
        }
        .login-field {
            width: 100%;
            margin-bottom: 1.1rem;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 0.7rem 0.8rem;
            box-sizing: border-box;
            border: none;
            border-radius: 8px;
            background: #1c1f25;
            color: #fff;
            font-size: 1rem;
            margin-top: 0.4rem;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: 2px solid #69e6b3;
        }
        .login-btn {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #69e6b3 0%, #4386fc 100%);
            color: #17191e;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 1.2rem;
            transition: background 0.18s;
        }
        .login-btn:hover {
            background: linear-gradient(90deg, #43fcb8 0%, #4386fc 100%);
        }
        .info {
            font-size: 0.97rem;
            color: #cccccc;
            margin-top: 0.2rem;
            background: #232940;
            padding: 0.95rem 1.1rem;
            border-radius: 8px;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <form class="login-container" method="post" action="">
        <img src="webcontrol/slappflow_logo.png" alt="SLAppFlow Logo" class="logo">
        <h2>SLAppFlow Login</h2>
        <div class="login-field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" autocomplete="username" required>
        </div>
        <div class="login-field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="login-btn">Log In</button>
        <?php if (isset($login_error)): ?>
            <div class="info" style="color:#ff7575;background:#31282b;">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>
        <div class="info">
            Your username is your Second Life name, and the password is the one you created specifically for SLAppFlow.<br>
            <b>This is <u>not</u> your Second Life account password</b>
            (although you may have chosen to use the same one, which is <u>not recommended</u> for security reasons).<br><br>

            To subscribe, please join the kiosk located inworld at 
            <a href="http://maps.secondlife.com/secondlife/HomeOfTheProdigies/118/10/24"
            target="_blank" rel="noopener">
            The Home of The Prodigies
            </a>
        </div>
    </form>
</body>
</html>
