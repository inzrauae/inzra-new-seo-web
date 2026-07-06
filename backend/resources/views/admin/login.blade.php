<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inzra Admin — Login</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f2e8;
            --panel: #fffdf8;
            --ink: #1f2328;
            --muted: #667085;
            --line: #e9dfc9;
            --accent: #ba4a00;
            --accent-soft: #fff1e8;
            --error-bg: #fff0f0;
            --error-border: #f5c6c6;
            --error-text: #9b1c1c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, #fff6d8 0, transparent 26%),
                linear-gradient(180deg, #fdf8ef 0%, var(--bg) 100%);
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(96, 64, 24, 0.10);
            padding: 40px 36px 36px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }

        .logo-mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .logo-text {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .kicker {
            margin: 0 0 6px;
            font-size: 0.82rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent);
        }

        h1 {
            margin: 0 0 6px;
            font-size: 1.6rem;
            line-height: 1.1;
        }

        .lede {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .error-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            border-radius: 10px;
            color: var(--error-text);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .error-icon {
            flex-shrink: 0;
            margin-top: 1px;
        }

        label {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
            color: var(--ink);
        }

        input[type="text"],
        input[type="password"] {
            display: block;
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fffef9;
            font-family: inherit;
            font-size: 1rem;
            color: var(--ink);
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #f0a070;
            box-shadow: 0 0 0 3px rgba(186, 74, 0, 0.12);
        }

        .field {
            margin-bottom: 22px;
        }

        button[type="submit"] {
            width: 100%;
            padding: 13px 20px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }

        button[type="submit"]:hover {
            background: #9e3d00;
        }

        button[type="submit"]:active {
            transform: scale(0.98);
        }

        .footer-note {
            margin-top: 20px;
            text-align: center;
            font-size: 0.82rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <div class="logo-mark">In</div>
            <span class="logo-text">Inzra</span>
        </div>

        <p class="kicker">Order Management</p>
        <h1>Admin Login</h1>
        <p class="lede">Enter your admin password to access the order dashboard.</p>

        @if (session('login_error'))
            <div class="error-box">
                <svg class="error-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="7.25" stroke="#9b1c1c" stroke-width="1.5"/>
                    <path d="M8 4.5v4M8 10.5v1" stroke="#9b1c1c" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                {{ session('login_error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="field">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    autocomplete="username"
                    autofocus
                    required
                    placeholder="Enter admin username"
                    value="{{ old('username') }}"
                >
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    placeholder="Enter admin password"
                >
            </div>
            <button type="submit">Sign In</button>
        </form>

        <p class="footer-note">Credentials are set via <strong>ADMIN_USERNAME</strong> and <strong>ADMIN_PASSWORD_HASH</strong> in <code>.env</code>.</p>
    </div>
</body>
</html>
