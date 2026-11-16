<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KALABARA (කලාබර) • Customer Seat Locator</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07060c;
            --bg-card: rgba(18, 15, 28, 0.78);
            --gold-primary: #f5cf6d;
            --gold-gradient: linear-gradient(135deg, #ffe89a 0%, #d4af37 50%, #aa7c11 100%);
            --gold-glow: rgba(212, 175, 55, 0.3);
            --border-gold: rgba(212, 175, 55, 0.3);
            --ruby-red: #e63946;
            --crimson-gradient: linear-gradient(135deg, #ff4d6d 0%, #c9184a 50%, #800f2f 100%);
            --royal-purple: #9d4edd;
            --purple-glow: rgba(157, 78, 221, 0.3);
            --neon-green: #39ff14;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            background-image:
                radial-gradient(circle at 50% 0%, rgba(201, 24, 74, 0.18) 0%, transparent 60%),
                radial-gradient(circle at 10% 90%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(128, 15, 47, 0.12) 0%, transparent 50%);
            background-attachment: fixed;
        }
        .brand-nav {
            background: rgba(14, 11, 23, 0.88);
            border-bottom: 1px solid var(--border-gold);
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .brand-logo {
            font-family: 'Cinzel', serif;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .brand-badge {
            font-size: 0.72rem;
            color: var(--gold-primary);
            border: 1px solid var(--border-gold);
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(212, 175, 55, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
        }
        .main-wrapper {
            max-width: 1040px;
            margin: 0 auto;
            padding: 24px 20px 80px;
        }
        .customer-hero-card {
            background: linear-gradient(135deg, rgba(28, 22, 45, 0.95) 0%, rgba(18, 14, 28, 0.98) 100%);
            border: 1.5px solid var(--gold-primary);
            border-radius: 20px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.7), 0 0 30px var(--gold-glow);
        }
        .lookup-box {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .lookup-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .lookup-input {
            flex-grow: 1;
            background: rgba(7, 5, 13, 0.9);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 12px;
            padding: 12px 18px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
        }
        .lookup-btn {
            background: var(--crimson-gradient);
            color: #fff;
            font-weight: 700;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
        }
        .arena-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
        }
        .stage-container {
            text-align: center;
            margin-bottom: 28px;
        }
        .stage-banner {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(180deg, rgba(212, 175, 55, 0.12) 0%, rgba(18, 15, 28, 0.9) 100%);
            border: 2px solid var(--gold-primary);
            border-radius: 16px;
            padding: 14px 70px;
            box-shadow: 0 0 35px var(--gold-glow);
        }
        .stage-title {
            font-family: 'Cinzel', serif;
            font-size: 1.65rem;
            font-weight: 900;
            letter-spacing: 0.3em;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stage-subtitle {
            font-size: 0.72rem;
            letter-spacing: 0.2em;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .category-section {
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 18px;
            padding: 20px;
            background: rgba(10, 8, 18, 0.5);
            margin-bottom: 22px;
        }
        .category-section-title {
            font-size: 0.9rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }
        .platinum-section-title { color: var(--gold-primary); }
        .gold-section-title { color: #ff758f; }

        .v2-col-headers, .v2-grid-row {
            display: grid;
            grid-template-columns: 42px repeat(10, 1fr);
            gap: 8px;
            margin-bottom: 8px;
            align-items: center;
        }
        .v2-axis-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.14);
            border: 1px solid var(--gold-primary);
            color: var(--gold-primary);
            font-family: 'Cinzel', serif;
            font-size: 0.78rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .table-btn {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
        }
        .table-btn.my-table {
            border: 2px solid var(--gold-primary);
            background: var(--gold-gradient);
            color: #07060c;
            font-weight: 900;
            box-shadow: 0 0 22px var(--gold-glow);
            transform: scale(1.12);
            z-index: 5;
        }
        .v2-bottom-layout {
            display: grid;
            grid-template-columns: 1fr 220px 1fr;
            gap: 18px;
        }
        .standing-box {
            background: rgba(157, 78, 221, 0.06);
            border: 1px dashed var(--royal-purple);
            border-radius: 18px;
            padding: 22px 14px;
            text-align: center;
        }
        .standing-box.my-standing {
            border: 2px solid var(--gold-primary);
            background: rgba(212, 175, 55, 0.16);
            box-shadow: 0 0 25px var(--gold-glow);
        }
        .foh-entrance-card {
            border: 1px solid var(--border-gold);
            border-radius: 18px;
            background: rgba(10, 8, 18, 0.7);
            display: flex;
            flex-direction: column;
            text-align: center;
        }
        .foh-block {
            padding: 14px;
            font-family: 'Cinzel', serif;
            font-weight: 800;
            color: var(--gold-primary);
            border-bottom: 1px solid var(--border-gold);
        }
        .entrance-block {
            padding: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            font-size: 0.82rem;
        }
    </style>
</head>
<body>
    <nav class="brand-nav">
        <div class="brand-logo">♠ KALABARA (කලාබර)</div>
        <div class="brand-badge">Seat Locator</div>
    </nav>

    <div class="main-wrapper">
        <div class="lookup-box">
            <form class="lookup-form" method="GET" action="{{ route('v2.customer.seat') }}">
                <input type="text" name="ticket" class="lookup-input" value="{{ $ticketQuery }}" placeholder="Enter your Ticket / Order Number (e.g. ORD-...)">
                <button type="submit" class="lookup-btn">Find My Seat</button>
            </form>
        </div>

        @if ($customerInfo)
            <div class="customer-hero-card">
                <div style="font-size:0.78rem;color:var(--gold-primary);text-transform:uppercase;letter-spacing:0.12em;font-weight:700;margin-bottom:6px;">
                    ♠ Confirmed Reservation
                </div>
                <div style="font-size:1.8rem;font-weight:800;margin-bottom:14px;">{{ $customerInfo['name'] }}</div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <div style="background:rgba(212,175,55,0.15);border:1px solid var(--gold-primary);padding:8px 14px;border-radius:12px;">
                        <span style="font-size:0.7rem;color:var(--text-muted);display:block;text-transform:uppercase;">Section</span>
                        <strong style="color:var(--gold-primary);">{{ strtoupper($customerInfo['category']) }}</strong>
                    </div>
                    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.14);padding:8px 14px;border-radius:12px;">
                        <span style="font-size:0.7rem;color:var(--text-muted);display:block;text-transform:uppercase;">Table &amp; Seat</span>
                        <strong>
                            @if ($customerInfo['category'] === 'standing')
                                {{ strtoupper($customerInfo['side']) }} STANDING AREA ({{ $customerInfo['seat_count'] }} Ticket(s))
                            @elseif ($customerInfo['is_full_table'])
                                Table {{ $customerInfo['table_number'] }} (Full Table - All 8 Seats)
                            @else
                                Table {{ $customerInfo['table_number'] }} • Seat(s) {{ implode(', ', $customerInfo['seats']) }}
                            @endif
                        </strong>
                    </div>
                    <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.14);padding:8px 14px;border-radius:12px;">
                        <span style="font-size:0.7rem;color:var(--text-muted);display:block;text-transform:uppercase;">Order / Ticket No</span>
                        <strong>{{ $customerInfo['ticket_number'] }}</strong>
                    </div>
                </div>
            </div>
        @endif

        @php
            $myTable = ($customerInfo && !empty($customerInfo['table_number'])) ? (int)$customerInfo['table_number'] : null;
        @endphp

        <div class="arena-card">
            <div class="stage-container">
                <div class="stage-banner">
                    <div class="stage-title">♠ STAGE (66 FT) ♠</div>
                    <div class="stage-subtitle">KALABARA LIVE • WATERS EDGE</div>
                </div>
            </div>

            <!-- VIP Section: Rows 1 - 5 (Tables 1 - 50) -->
            <div class="category-section">
                <div class="category-section-title platinum-section-title">
                    ♠ VIP (Tables 1 - 50 • Rows 1 - 5)
                </div>
                <div class="v2-col-headers">
                    <div></div>
                    @for ($c = 1; $c <= 10; $c++)
                        <div class="v2-axis-badge">{{ $c }}</div>
                    @endfor
                </div>
                @for ($r = 1; $r <= 5; $r++)
                    <div class="v2-grid-row">
                        <div class="v2-axis-badge">{{ $r }}</div>
                        @for ($c = 1; $c <= 10; $c++)
                            @php $tNum = (($r - 1) * 10) + $c; @endphp
                            <div class="table-btn {{ $myTable === $tNum ? 'my-table' : '' }}">
                                {{ $tNum }}
                            </div>
                        @endfor
                    </div>
                @endfor
            </div>

            <!-- Gold Section: Rows 6 - 13 (Tables 51 - 130) -->
            <div class="category-section">
                <div class="category-section-title gold-section-title">
                    ♥ GOLD (Tables 51 - 130 • Rows 6 - 13)
                </div>
                <div class="v2-col-headers">
                    <div></div>
                    @for ($c = 1; $c <= 10; $c++)
                        <div class="v2-axis-badge">{{ $c }}</div>
                    @endfor
                </div>
                @for ($r = 6; $r <= 13; $r++)
                    <div class="v2-grid-row">
                        <div class="v2-axis-badge">{{ $r }}</div>
                        @for ($c = 1; $c <= 10; $c++)
                            @php $tNum = (($r - 1) * 10) + $c; @endphp
                            <div class="table-btn {{ $myTable === $tNum ? 'my-table' : '' }}">
                                {{ $tNum }}
                            </div>
                        @endfor
                    </div>
                @endfor
            </div>

            <!-- Bottom Section: Left Standing | FOH + Entrance | Right Standing -->
            <div class="v2-bottom-layout">
                <div class="standing-box {{ ($customerInfo && $customerInfo['category'] === 'standing' && $customerInfo['side'] === 'left') ? 'my-standing' : '' }}">
                    <div style="font-size:1.5rem;color:#c77dff;margin-bottom:6px;">♣</div>
                    <h3 style="color:#e0aaff;font-size:0.95rem;text-transform:uppercase;">Left Standing Area</h3>
                </div>
                <div class="foh-entrance-card">
                    <div class="foh-block">FOH</div>
                    <div class="entrance-block"><span style="color:var(--gold-primary);">⬆</span> ENTRANCE</div>
                </div>
                <div class="standing-box {{ ($customerInfo && $customerInfo['category'] === 'standing' && $customerInfo['side'] === 'right') ? 'my-standing' : '' }}">
                    <div style="font-size:1.5rem;color:#c77dff;margin-bottom:6px;">♥</div>
                    <h3 style="color:#e0aaff;font-size:0.95rem;text-transform:uppercase;">Right Standing Area</h3>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
