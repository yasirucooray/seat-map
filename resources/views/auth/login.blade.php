<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KALABARA (කලාබර) - Admin Portal Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07060c;
            --card-bg: rgba(18, 15, 28, 0.85);
            --gold-primary: #f5cf6d;
            --gold-gradient: linear-gradient(135deg, #ffe89a 0%, #d4af37 50%, #aa7c11 100%);
            --gold-glow: rgba(212, 175, 55, 0.25);
            --border-gold: rgba(212, 175, 55, 0.35);
            
            --ruby-red: #e63946;
            --ruby-glow: rgba(230, 57, 70, 0.25);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(212, 175, 55, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(201, 24, 74, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(157, 78, 221, 0.06) 0%, transparent 60%);
            color: var(--text-main);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Suit Watermark overlay */
        body::before {
            content: "♠ ♥ ♣ ♦";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-12deg);
            font-size: 22vw;
            letter-spacing: 4vw;
            color: rgba(255, 255, 255, 0.015);
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
        }

        .login-container {
            position: relative;
            z-index: 1;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-gold);
            border-radius: 28px;
            padding: 44px 40px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 35px var(--gold-glow);
            text-align: center;
            animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(24px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-emblem {
            width: 64px;
            height: 72px;
            margin: 0 auto 20px auto;
            background: linear-gradient(145deg, #251f38, #110e1c);
            border: 2px solid var(--gold-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--gold-primary);
            box-shadow: 0 0 25px var(--gold-glow);
            transform: rotate(-5deg);
            transition: transform 0.4s ease;
        }

        .login-container:hover .card-emblem {
            transform: rotate(0deg) scale(1.05);
        }

        h1 {
            font-family: 'Cinzel', serif;
            font-size: 2.2rem;
            font-weight: 900;
            margin: 0 0 6px 0;
            letter-spacing: 0.12em;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px var(--gold-glow);
        }

        p.subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0 0 32px 0;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
            color: var(--text-muted);
        }

        input {
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-gold);
            border-radius: 12px;
            padding: 14px 16px;
            font-family: inherit;
            color: white;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--gold-primary);
            box-shadow: 0 0 16px var(--gold-glow);
            background: rgba(255, 255, 255, 0.08);
        }

        .error-message {
            color: var(--ruby-red);
            font-size: 0.82rem;
            margin-top: 8px;
            font-weight: 500;
        }

        button {
            width: 100%;
            background: var(--gold-gradient);
            border: none;
            border-radius: 12px;
            padding: 16px;
            color: #07060c;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 12px;
            box-shadow: 0 4px 18px var(--gold-glow);
            letter-spacing: 0.05em;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.5);
        }

        button:active {
            transform: translateY(0);
        }

        .event-tag {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid rgba(212, 175, 55, 0.15);
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .event-tag span {
            color: var(--gold-primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card-emblem">♠</div>
        <h1>කලාබර</h1>
        <p class="subtitle">Kalabara Concert • Admin Portal</p>
        <form method="POST" action="/login">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@kalabara.com">
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit">♠ Sign In to Portal ♠</button>
        </form>
        <div class="event-tag">
            <span>WATERS EDGE</span> • 20 NOV • 6:30 PM
        </div>
    </div>
</body>
</html>
