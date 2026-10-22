<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KALABARA (කලාබර) - Customer Seat Locator</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07060c;
            --bg-card: rgba(18, 15, 28, 0.7);
            --bg-card-hover: rgba(28, 24, 42, 0.85);
            --gold-primary: #f5cf6d;
            --gold-gradient: linear-gradient(135deg, #ffe89a 0%, #d4af37 50%, #aa7c11 100%);
            --gold-glow: rgba(212, 175, 55, 0.25);
            --border-gold: rgba(212, 175, 55, 0.3);
            
            --ruby-red: #e63946;
            --ruby-glow: rgba(230, 57, 70, 0.25);
            --crimson-gradient: linear-gradient(135deg, #ff4d6d 0%, #c9184a 50%, #800f2f 100%);
            
            --royal-purple: #7b2cbf;
            --purple-glow: rgba(123, 44, 191, 0.3);
            --neon-green: #39ff14;
            --green-glow: rgba(57, 255, 20, 0.2);
            
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --card-radius: 20px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(201, 24, 74, 0.18) 0%, transparent 60%),
                radial-gradient(circle at 10% 90%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(128, 15, 47, 0.12) 0%, transparent 50%);
            background-attachment: fixed;
        }

        /* Top Brand Navigation */
        .brand-nav {
            background: rgba(14, 11, 23, 0.85);
            border-bottom: 1px solid var(--border-gold);
            padding: 16px 24px;
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
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Main Container */
        .main-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 20px 80px;
            width: 100%;
            flex-grow: 1;
        }

        /* Customer Ticket Hero Card */
        .customer-hero-card {
            background: linear-gradient(135deg, rgba(28, 22, 45, 0.95) 0%, rgba(18, 14, 28, 0.98) 100%);
            border: 1.5px solid var(--gold-primary);
            border-radius: var(--card-radius);
            padding: 24px 28px;
            margin-bottom: 28px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.7), 0 0 30px var(--gold-glow);
            position: relative;
            overflow: hidden;
        }

        .customer-hero-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, var(--gold-glow) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .hero-welcome-badge {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gold-primary);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .hero-guest-name {
            font-size: 1.9rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 14px;
            letter-spacing: -0.01em;
        }

        .hero-chips-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .hero-chip {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.14);
            padding: 8px 14px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .hero-chip.highlight {
            background: rgba(212, 175, 55, 0.15);
            border-color: var(--gold-primary);
            box-shadow: 0 0 15px var(--gold-glow);
        }

        .chip-label {
            font-size: 0.68rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
        }

        .chip-val {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }

        .chip-val.gold {
            color: var(--gold-primary);
        }

        .btn-center-seat {
            background: var(--gold-gradient);
            color: #07060c;
            font-size: 0.95rem;
            font-weight: 800;
            border: none;
            padding: 14px 26px;
            border-radius: 30px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 20px var(--gold-glow);
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .btn-center-seat:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.45);
        }

        /* Lookup Search Box for Guests visiting directly */
        .lookup-box {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: var(--card-radius);
            padding: 24px;
            margin-bottom: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .lookup-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 14px;
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

        .lookup-input:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 15px var(--gold-glow);
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

        /* -----------------------------------------------------------------
           EXACT ARENA SEATING DESIGN MATCHING ADMIN DASHBOARD
           ----------------------------------------------------------------- */
        .arena-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: var(--card-radius);
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(12px);
        }

        .arena-header-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .arena-header-title {
            font-family: 'Cinzel', serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.05em;
        }

        /* Stage Design */
        .stage-container {
            width: 100%;
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
        }

        .stage-banner {
            background: radial-gradient(ellipse at center, rgba(230, 57, 70, 0.25) 0%, rgba(14, 11, 23, 0.8) 70%);
            border: 2px solid var(--ruby-red);
            box-shadow: 0 0 35px var(--ruby-glow), inset 0 0 20px rgba(230, 57, 70, 0.2);
            border-radius: 12px;
            padding: 16px 60px;
            text-align: center;
            position: relative;
            width: 80%;
            max-width: 600px;
        }

        .stage-title {
            font-family: 'Cinzel', serif;
            font-size: 1.6rem;
            font-weight: 900;
            letter-spacing: 0.3em;
            color: #fff;
            text-shadow: 0 0 15px var(--ruby-glow);
        }

        .stage-subtitle {
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            color: var(--text-muted);
            margin-top: 4px;
            text-transform: uppercase;
        }

        /* Arena Layout Grid */
        .arena-layout {
            display: grid;
            grid-template-columns: 1fr 140px 1fr;
            gap: 24px;
            margin-top: 20px;
        }

        .side-container {
            background: rgba(14, 11, 23, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 18px;
            padding: 20px;
        }

        .side-header {
            font-family: 'Cinzel', serif;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .side-header.left {
            color: var(--gold-primary);
            border-bottom-color: var(--border-gold);
        }
        .side-header.right {
            color: #ff758f;
            border-bottom-color: rgba(255, 117, 143, 0.3);
        }

        .category-section {
            margin-bottom: 24px;
        }

        .category-section-title {
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .platinum-section-title { color: var(--gold-primary); }
        .gold-section-title { color: #ff758f; }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(46px, 1fr));
            gap: 8px;
        }

        /* -------------------------------------------------------------
           LOCKED TABLES VS CUSTOMER TABLE
           All other tables are locked and generic (no reserved colors)
           ------------------------------------------------------------- */
        .table-btn {
            width: 100%;
            height: 42px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.8rem;
            font-family: 'Outfit', sans-serif;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.02);
            color: rgba(255, 255, 255, 0.3);
            cursor: not-allowed;
            pointer-events: none;
            user-select: none;
            transition: all 0.2s ease;
            position: relative;
        }

        /* CUSTOMER HIGHLIGHTED TABLE (Active & Clickable) */
        .table-btn.customer-my-table {
            border: 2px solid var(--gold-primary) !important;
            background: var(--gold-gradient) !important;
            color: #07060c !important;
            font-weight: 900 !important;
            box-shadow: 0 0 25px var(--gold-glow), 0 0 45px rgba(212, 175, 55, 0.5) !important;
            transform: scale(1.18) !important;
            z-index: 15 !important;
            cursor: pointer !important;
            pointer-events: auto !important;
            animation: customerTablePulse 1.4s infinite alternate !important;
        }

        @keyframes customerTablePulse {
            0% { transform: scale(1.15); box-shadow: 0 0 20px var(--gold-glow); }
            100% { transform: scale(1.25); box-shadow: 0 0 35px var(--gold-glow), 0 0 60px rgba(212, 175, 55, 0.6); }
        }

        .customer-pin-badge {
            position: absolute;
            top: -24px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--gold-gradient);
            color: #07060c;
            font-size: 0.65rem;
            font-weight: 900;
            padding: 2px 7px;
            border-radius: 6px;
            white-space: nowrap;
            box-shadow: 0 0 12px var(--gold-glow);
            letter-spacing: 0.05em;
            pointer-events: none;
        }

        /* Standing Middle Column */
        .standing-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
            justify-content: space-between;
            height: 100%;
        }

        .standing-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 24px 14px;
            text-align: center;
            cursor: not-allowed;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            opacity: 0.5;
        }

        .standing-box .suit-icon {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.3);
            margin-bottom: 6px;
        }

        .standing-box h3 {
            margin: 0 0 6px 0;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 700;
        }

        /* CUSTOMER STANDING ZONE ACTIVE */
        .standing-box.customer-my-standing {
            border: 2px solid var(--gold-primary) !important;
            background: rgba(212, 175, 55, 0.18) !important;
            box-shadow: 0 0 30px var(--gold-glow) !important;
            opacity: 1 !important;
            cursor: pointer !important;
            pointer-events: auto !important;
            animation: customerTablePulse 1.4s infinite alternate !important;
        }
        .standing-box.customer-my-standing .suit-icon { color: var(--gold-primary); }
        .standing-box.customer-my-standing h3 { color: var(--gold-primary); }

        /* -------------------------------------------------------------
           SEATING RING MODAL - EXACT SAME 105px RADIUS DESIGN AS ADMIN
           ------------------------------------------------------------- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(4, 3, 8, 0.88);
            backdrop-filter: blur(12px);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: #110e1c;
            border: 1px solid var(--border-gold);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 30px var(--gold-glow);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 28px;
            position: relative;
            transform: scale(0.92);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 12px;
        }

        .modal-title {
            font-family: 'Cinzel', serif;
            font-size: 1.25rem;
            color: var(--gold-primary);
            font-weight: 800;
        }

        .close-modal {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.8rem;
            cursor: pointer;
            line-height: 1;
        }
        .close-modal:hover { color: var(--ruby-red); }

        /* 8 Seats Table Layout (Exact Admin Dimensions) */
        .seats-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto 20px auto;
            background: rgba(255, 255, 255, 0.02);
            border: 2px dashed rgba(212, 175, 55, 0.2);
            border-radius: 50%;
        }

        .modal-table-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 76px;
            height: 76px;
            background: radial-gradient(circle, #251f38 0%, #110e1c 100%);
            border: 2px solid var(--gold-primary);
            box-shadow: 0 0 20px var(--gold-glow);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: 'Cinzel', serif;
            font-weight: 800;
            color: var(--gold-primary);
            font-size: 1.15rem;
            z-index: 10;
        }

        .seat-circle {
            position: absolute;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.85rem;
            font-weight: 700;
            box-sizing: border-box;
            user-select: none;
            transition: all 0.2s ease;
        }

        /* CUSTOMER OWN SEATS (Exact Admin .selected style) */
        .seat-circle.my-seat {
            border: 2px solid var(--gold-primary) !important;
            background: var(--gold-gradient) !important;
            color: #07060c !important;
            font-weight: 900 !important;
            box-shadow: 0 0 22px var(--gold-glow) !important;
            transform: scale(1.15) !important;
            animation: seatPulse 1.2s infinite alternate;
        }

        @keyframes seatPulse {
            0% { transform: scale(1.1); box-shadow: 0 0 15px var(--gold-glow); }
            100% { transform: scale(1.22); box-shadow: 0 0 28px rgba(212, 175, 55, 0.7); }
        }

        /* LOCKED / OTHER SEATS ON SAME TABLE (Neutral, generic) */
        .seat-circle.locked-seat {
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.03);
            color: rgba(255, 255, 255, 0.25);
            cursor: not-allowed;
            pointer-events: none;
        }

        .seat-hint-box {
            background: rgba(212, 175, 55, 0.08);
            border: 1px solid var(--border-gold);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            font-size: 0.88rem;
            color: #fff;
            line-height: 1.5;
        }

        @media (max-width: 900px) {
            .arena-layout {
                grid-template-columns: 1fr;
            }
            .standing-column {
                flex-direction: row;
            }
            .standing-box {
                flex: 1;
            }
        }
    </style>
</head>
<body>

    <!-- Top Brand Bar -->
    <header class="brand-nav">
        <div class="brand-logo">
            <span>♠</span> KALABARA
        </div>
        <div class="brand-badge">Seat Locator</div>
    </header>

    <main class="main-wrapper">

        @if($customerInfo)
            <!-- Customer Ticket Confirmation Hero Card -->
            <section class="customer-hero-card" id="customer-hero">
                <div class="hero-flex">
                    <div>
                        <div class="hero-welcome-badge">
                            <span>✓</span> Booking Confirmed &bull; Kalabara Live
                        </div>
                        <h1 class="hero-guest-name">{{ $customerInfo['name'] }}</h1>
                        
                        <div class="hero-chips-row">
                            <div class="hero-chip">
                                <span class="chip-label">Section</span>
                                <span class="chip-val gold">{{ strtoupper($customerInfo['category']) }}</span>
                            </div>

                            @if($customerInfo['category'] === 'standing')
                                <div class="hero-chip highlight">
                                    <span class="chip-label">Zone</span>
                                    <span class="chip-val gold">Standing Area ({{ ucfirst($customerInfo['side']) }})</span>
                                </div>
                            @else
                                <div class="hero-chip highlight">
                                    <span class="chip-label">Table</span>
                                    <span class="chip-val gold">
                                        {{ ucfirst($customerInfo['side']) }} Wing &bull; Table {{ strtoupper(substr($customerInfo['side'], 0, 1)) }}{{ $customerInfo['table_number'] }}
                                    </span>
                                </div>

                                <div class="hero-chip highlight">
                                    <span class="chip-label">Your Reserved Seats</span>
                                    <span class="chip-val gold">
                                        @if($customerInfo['is_full_table'])
                                            Full Table (All 8 Seats)
                                        @elseif(count($customerInfo['seats']) > 0)
                                            Seat {{ implode(', Seat ', $customerInfo['seats']) }}
                                        @else
                                            Reserved
                                        @endif
                                    </span>
                                </div>
                            @endif

                            <div class="hero-chip">
                                <span class="chip-label">Ticket Code</span>
                                <span class="chip-val" style="font-family: monospace;">{{ $customerInfo['ticket_number'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="button" class="btn-center-seat" onclick="locateCustomerSeat()">
                            <span>📍</span> Locate My Table on Map
                        </button>
                    </div>
                </div>
            </section>
        @else
            <!-- Search Box if visiting without a ticket in URL -->
            <section class="lookup-box">
                <h2 style="font-size: 1.2rem; color: var(--gold-primary); margin-bottom: 6px;">Locate Your Kalabara Seat</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    Enter your Ticket Code or Order Number (from your SMS or email) to view your table.
                </p>
                <form method="GET" action="{{ route('customer.seat') }}" class="lookup-form">
                    <input type="text" name="ticket" value="{{ $ticketQuery }}" class="lookup-input" placeholder="Enter your Ticket Code (e.g. KAL-1001, ORDMQ...)..." required />
                    <button type="submit" class="lookup-btn">Find My Seat &rarr;</button>
                </form>

                @if(!empty($ticketQuery))
                    <div style="margin-top: 14px; color: #ff8599; font-size: 0.88rem;">
                        ⚠️ Ticket code "{{ $ticketQuery }}" was not found. Please verify your ticket code and try again.
                    </div>
                @endif
            </section>
        @endif

        <!-- Main Arena Booking Card (Exact Same Layout as Admin) -->
        <section class="arena-card">
            <div class="arena-header-strip">
                <div class="arena-header-title">Venue Floor Plan &bull; Waters Edge</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                    @if($customerInfo)
                        <span style="display: inline-block; width: 14px; height: 14px; background: var(--gold-gradient); border-radius: 4px; box-shadow: 0 0 10px var(--gold-glow);"></span>
                        <strong style="color: var(--gold-primary);">Your Table / Seats</strong>
                    @endif
                </div>
            </div>

            <!-- Stage Banner (Exact Admin Style) -->
            <div class="stage-container">
                <div class="stage-banner">
                    <div class="stage-title">♠ STAGE ♠</div>
                    <div class="stage-subtitle">KALABARA LIVE &bull; WATERS EDGE</div>
                </div>
            </div>

            <!-- 2D Arena Floor Plan -->
            <div class="arena-layout">
                <!-- Left Wing -->
                <div class="side-container">
                    <div class="side-header left">♠ LEFT WING - L</div>

                    <!-- Platinum 1 - 54 -->
                    <div class="category-section">
                        <div class="category-section-title platinum-section-title">
                            <span>♠</span> PLATINUM (Tables 1 - 54)
                        </div>
                        <div class="tables-grid" id="customer-plat-left"></div>
                    </div>

                    <!-- Gold 55 - 126 -->
                    <div class="category-section">
                        <div class="category-section-title gold-section-title">
                            <span>♥</span> GOLD (Tables 55 - 126)
                        </div>
                        <div class="tables-grid" id="customer-gold-left"></div>
                    </div>
                </div>

                <!-- Standing Column (Middle) -->
                <div class="standing-column">
                    <div class="side-header" style="color: #c77dff; border-color: rgba(157, 78, 221, 0.3);">♣ STANDING</div>

                    <div class="standing-box {{ ($customerInfo && $customerInfo['category'] === 'standing' && $customerInfo['side'] === 'left') ? 'customer-my-standing' : '' }}" 
                         id="cstanding-left" onclick="openCustomerStanding('Left Standing')">
                        <div class="suit-icon">♣</div>
                        <h3>Left Standing</h3>
                    </div>

                    <div class="standing-box {{ ($customerInfo && $customerInfo['category'] === 'standing' && $customerInfo['side'] === 'center') ? 'customer-my-standing' : '' }}" 
                         id="cstanding-center" onclick="openCustomerStanding('Center Standing')">
                        <div class="suit-icon">♠</div>
                        <h3>Center Standing</h3>
                    </div>

                    <div class="standing-box {{ ($customerInfo && $customerInfo['category'] === 'standing' && $customerInfo['side'] === 'right') ? 'customer-my-standing' : '' }}" 
                         id="cstanding-right" onclick="openCustomerStanding('Right Standing')">
                        <div class="suit-icon">♥</div>
                        <h3>Right Standing</h3>
                    </div>
                </div>

                <!-- Right Wing -->
                <div class="side-container">
                    <div class="side-header right">♥ RIGHT WING - R</div>

                    <!-- Platinum 1 - 54 -->
                    <div class="category-section">
                        <div class="category-section-title platinum-section-title">
                            <span>♠</span> PLATINUM (Tables 1 - 54)
                        </div>
                        <div class="tables-grid" id="customer-plat-right"></div>
                    </div>

                    <!-- Gold 55 - 126 -->
                    <div class="category-section">
                        <div class="category-section-title gold-section-title">
                            <span>♥</span> GOLD (Tables 55 - 126)
                        </div>
                        <div class="tables-grid" id="customer-gold-right"></div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Customer Seating Details Ring Modal (Exact Admin Dimensions & Trigonometry) -->
    <div class="modal-overlay" id="customer-seating-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="cust-modal-title">Table Detail</h3>
                <button type="button" class="close-modal" onclick="closeCustomerModal()">&times;</button>
            </div>

            <div class="seats-container">
                <div class="modal-table-center" id="cust-modal-center-id">L1</div>
                <!-- 8 Seats positioned absolutely using exact admin trigonometry -->
                <div class="seat-circle" id="seat-1">1</div>
                <div class="seat-circle" id="seat-2">2</div>
                <div class="seat-circle" id="seat-3">3</div>
                <div class="seat-circle" id="seat-4">4</div>
                <div class="seat-circle" id="seat-5">5</div>
                <div class="seat-circle" id="seat-6">6</div>
                <div class="seat-circle" id="seat-7">7</div>
                <div class="seat-circle" id="seat-8">8</div>
            </div>

            <div class="seat-hint-box" id="cust-modal-hint-box">
                <!-- Injected via JS -->
            </div>
        </div>
    </div>

    <!-- Client-Side Script -->
    <script>
        // Only the customer's own booking data is known
        const myBooking = @json($customerInfo);

        // Exact Admin Trigonometric Seat Positioning (Radius 105px, Center 150px)
        const radius = 105;
        const seatsData = [
            { id: 1, angle: 0 },
            { id: 2, angle: 45 },
            { id: 3, angle: 90 },
            { id: 4, angle: 135 },
            { id: 5, angle: 180 },
            { id: 6, angle: 225 },
            { id: 7, angle: 270 },
            { id: 8, angle: 315 }
        ];

        seatsData.forEach(seat => {
            const el = document.getElementById(`seat-${seat.id}`);
            if (el) {
                const rad = (seat.angle - 90) * (Math.PI / 180);
                const x = Math.round(radius * Math.cos(rad));
                const y = Math.round(radius * Math.sin(rad));
                // Center coordinates relative to 300px box (150, 150) - half seat size (22)
                el.style.left = `${150 + x - 22}px`;
                el.style.top = `${150 + y - 22}px`;
            }
        });

        // Render Grids: All other tables are LOCKED and NEUTRAL (no reserved colors)
        function renderGrids() {
            renderSection('platinum', 'left', 1, 54, 'customer-plat-left');
            renderSection('platinum', 'right', 1, 54, 'customer-plat-right');
            renderSection('gold', 'left', 55, 126, 'customer-gold-left');
            renderSection('gold', 'right', 55, 126, 'customer-gold-right');
        }

        function renderSection(category, side, start, end, elementId) {
            const container = document.getElementById(elementId);
            if (!container) return;
            container.innerHTML = '';

            for (let i = start; i <= end; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'table-btn';
                btn.id = `ctable-${side}-${i}`;

                const label = side.toUpperCase().substring(0, 1) + i;
                btn.innerText = label;

                // Is this the customer's own table?
                const isMyTable = myBooking && 
                    myBooking.category === category && 
                    myBooking.side === side && 
                    myBooking.table_number === i;

                if (isMyTable) {
                    btn.classList.add('customer-my-table');

                    const pin = document.createElement('span');
                    pin.className = 'customer-pin-badge';
                    pin.innerText = 'YOUR TABLE';
                    btn.appendChild(pin);

                    // Only the customer's own table is clickable!
                    btn.onclick = () => openCustomerTableModal(category, side, i);
                } else {
                    // All other tables are LOCKED and neutral (no colors, no clicks)
                    btn.title = "Reserved Area";
                }

                container.appendChild(btn);
            }
        }

        function openCustomerTableModal(category, side, tableNumber) {
            const modal = document.getElementById('customer-seating-modal');
            const titleEl = document.getElementById('cust-modal-title');
            const centerEl = document.getElementById('cust-modal-center-id');
            const hintBox = document.getElementById('cust-modal-hint-box');

            const suitSymbol = category === 'platinum' ? '♠' : '♥';
            const tableCode = side.toUpperCase().substring(0, 1) + tableNumber;
            
            titleEl.innerText = `${suitSymbol} ${category.toUpperCase()} - Table ${tableCode}`;
            centerEl.innerText = tableCode;

            // Render 8 Seats: Highlight only customer's seats
            for (let s = 1; s <= 8; s++) {
                const seatEl = document.getElementById(`seat-${s}`);
                seatEl.className = 'seat-circle';

                const isMySeat = myBooking.is_full_table || (myBooking.seats && myBooking.seats.includes(s));

                if (isMySeat) {
                    seatEl.classList.add('my-seat');
                    seatEl.title = `Your Seat ${s}`;
                } else {
                    seatEl.classList.add('locked-seat');
                    seatEl.title = 'Other Seat';
                }
            }

            // Summary text
            if (myBooking.is_full_table) {
                hintBox.innerHTML = `
                    <div style="font-weight: 800; color: var(--gold-primary); font-size: 1rem; margin-bottom: 4px;">
                        ★ Full Table Reserved (All 8 Seats)
                    </div>
                    <div>Guest: <strong>${myBooking.name}</strong> &bull; Ticket: <span style="font-family: monospace;">${myBooking.ticket_number}</span></div>
                `;
            } else {
                const seatsList = myBooking.seats && myBooking.seats.length > 0 ? myBooking.seats.join(', Seat ') : 'Reserved';
                hintBox.innerHTML = `
                    <div style="font-weight: 800; color: var(--gold-primary); font-size: 1rem; margin-bottom: 4px;">
                        ★ Your Reserved Seats: Seat ${seatsList}
                    </div>
                    <div>Guest: <strong>${myBooking.name}</strong> &bull; Ticket: <span style="font-family: monospace;">${myBooking.ticket_number}</span></div>
                `;
            }

            modal.classList.add('active');
        }

        function closeCustomerModal() {
            document.getElementById('customer-seating-modal').classList.remove('active');
        }

        function openCustomerStanding(zoneName) {
            if (myBooking && myBooking.category === 'standing') {
                alert(`★ Your booking is in ${zoneName}!\nGuest: ${myBooking.name}\nTicket: ${myBooking.ticket_number}\nPlease show your ticket code at the Standing Entrance.`);
            }
        }

        function locateCustomerSeat() {
            if (!myBooking) return;

            let target = null;
            if (myBooking.category === 'standing') {
                target = document.getElementById(`cstanding-${myBooking.side}`);
            } else if (myBooking.table_number) {
                target = document.getElementById(`ctable-${myBooking.side}-${myBooking.table_number}`);
            }

            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderGrids();

            // Auto-scroll to customer's table on page load
            if (myBooking) {
                setTimeout(() => {
                    locateCustomerSeat();
                }, 350);
            }
        });
    </script>
</body>
</html>
