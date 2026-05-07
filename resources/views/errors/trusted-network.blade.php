<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Restricted</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            color: #f8fafc;
            background-color: #15202b;
            background-image:
                linear-gradient(135deg, rgba(15, 23, 42, 0.72), rgba(21, 32, 43, 0.82)),
                url('/images/background.png');
            background-size: cover;
            background-position: center;
        }

        .wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 32px;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: rgba(26, 35, 50, 0.94);
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(2, 6, 23, 0.45);
            backdrop-filter: blur(10px);
            padding: 36px;
            text-align: center;
        }

        .logo-link {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
        }

        .logo-link img {
            width: 200px;
            max-width: 100%;
            height: auto;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(248, 113, 113, 0.12);
            color: #fca5a5;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            justify-content: center;
        }

        .eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #f87171;
        }

        h1 {
            margin: 20px 0 14px;
            font-size: 34px;
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        p {
            margin: 0;
            line-height: 1.7;
            font-size: 16px;
            color: #cbd5e1;
        }

        .helper-text {
            margin-top: 22px;
            padding-top: 22px;
            border-top: 1px solid rgba(148, 163, 184, 0.18);
            font-size: 14px;
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .wrapper {
                justify-content: center;
                padding: 20px;
            }

            .card {
                padding: 28px 22px;
            }

            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <a class="logo-link" href="/">
                <img src="/images/logo.png" alt="Logo">
            </a>
            <span class="eyebrow">Network restricted</span>
            <h1>Access restricted</h1>
            <p>{{ $message }}</p>
            <p class="helper-text">
                If you are working remotely, connect to the company VPN or contact an administrator to confirm that your network is approved.
            </p>
        </div>
    </div>
</body>
</html>
