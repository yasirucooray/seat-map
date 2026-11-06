<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KALABARA (කලාබර) - Admin QR Scanner & Seat Check-In</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- jsQR for universal cross-browser camera & image QR decoding -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <style>
        :root {
            --bg-dark: #07060c;
            --bg-card: rgba(18, 15, 28, 0.78);
            --bg-card-hover: rgba(28, 24, 42, 0.9);
            --gold-primary: #f5cf6d;
            --gold-gradient: linear-gradient(135deg, #ffe89a 0%, #d4af37 50%, #aa7c11 100%);
            --gold-glow: rgba(212, 175, 55, 0.28);
            --border-gold: rgba(212, 175, 55, 0.32);

            --ruby-red: #e63946;
            --ruby-glow: rgba(230, 57, 70, 0.25);
            --crimson-gradient: linear-gradient(135deg, #ff4d6d 0%, #c9184a 50%, #800f2f 100%);

            --emerald-green: #2ec4b6;
            --neon-green: #39ff14;
            --green-gradient: linear-gradient(135deg, #39ff14 0%, #10b981 55%, #047857 100%);
            --green-glow: rgba(57, 255, 20, 0.28);

            --royal-purple: #9d4edd;
            --purple-glow: rgba(157, 78, 221, 0.28);

            --cyan-blue: #00f2fe;
            --cyan-glow: rgba(0, 242, 254, 0.28);

            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --card-radius: 20px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            background-image:
                radial-gradient(circle at 15% 10%, rgba(212, 175, 55, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 85% 90%, rgba(46, 196, 182, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(157, 78, 221, 0.06) 0%, transparent 60%);
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* Top Navigation */
        .brand-nav {
            background: rgba(14, 11, 23, 0.92);
            border-bottom: 1px solid var(--border-gold);
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            z-index: 200;
        }

        .brand-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            font-family: 'Cinzel', serif;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .brand-badge {
            font-size: 0.72rem;
            color: #00f2fe;
            border: 1px solid rgba(0, 242, 254, 0.4);
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(0, 242, 254, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-back-dashboard {
            text-decoration: none;
            background: rgba(212, 175, 55, 0.14);
            border: 1px solid var(--gold-primary);
            color: var(--gold-primary);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }
        .btn-back-dashboard:hover {
            background: var(--gold-gradient);
            color: #07060c;
            box-shadow: 0 0 15px var(--gold-glow);
        }

        .main-wrapper {
            max-width: 1440px;
            margin: 0 auto;
            padding: 24px 20px 80px;
        }

        /* Live Check-In Metrics Grid */
        .checkin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 18px;
            padding: 18px 22px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(12px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        .stat-card.card-checked::before { background: linear-gradient(135deg, #39ff14, #2ec4b6); }
        .stat-card.card-countdown::before { background: linear-gradient(135deg, #00f2fe, #4facfe); }
        .stat-card.card-sections::before { background: var(--gold-gradient); }

        .stat-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-value {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
        }

        .stat-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Scanner + Result Split Grid */
        .scanner-top-grid {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 24px;
            margin-bottom: 28px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .scanner-top-grid {
                grid-template-columns: 1fr;
            }
        }

        .scanner-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: var(--card-radius);
            padding: 22px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(16px);
        }

        .panel-title {
            font-family: 'Cinzel', serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--gold-primary);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-subtitle {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            line-height: 1.4;
        }

        /* Camera Viewport */
        .camera-box {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: #050409;
            border: 2px dashed rgba(212, 175, 55, 0.45);
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .camera-box.active {
            border-style: solid;
            border-color: var(--neon-green);
            box-shadow: 0 0 25px rgba(57, 255, 20, 0.2);
        }

        #qr-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .scan-laser-frame {
            position: absolute;
            width: 68%;
            height: 68%;
            border: 2px solid rgba(57, 255, 20, 0.8);
            border-radius: 14px;
            box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.45);
            pointer-events: none;
            display: none;
        }

        .camera-box.active .scan-laser-frame {
            display: block;
        }

        .scan-laser-line {
            position: absolute;
            left: 6%;
            right: 6%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #39ff14, #00f2fe, #39ff14, transparent);
            box-shadow: 0 0 12px #39ff14;
            animation: scanLaserMove 2s infinite ease-in-out;
        }

        @keyframes scanLaserMove {
            0% { top: 8%; }
            50% { top: 90%; }
            100% { top: 8%; }
        }

        .camera-placeholder {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
        }

        .camera-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .btn-action {
            flex: 1;
            padding: 11px 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.86rem;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.25s ease;
        }

        .btn-camera-start {
            background: var(--gold-gradient);
            color: #07060c;
            box-shadow: 0 4px 15px var(--gold-glow);
        }
        .btn-camera-stop {
            background: var(--crimson-gradient);
            color: #fff;
            display: none;
        }
        .btn-upload-qr {
            background: rgba(0, 242, 254, 0.14);
            border: 1px solid rgba(0, 242, 254, 0.4);
            color: #00f2fe;
        }
        .btn-upload-qr:hover {
            background: rgba(0, 242, 254, 0.25);
        }

        /* Manual / Scanner Gun Input */
        .manual-lookup-form {
            display: flex;
            gap: 8px;
        }

        .manual-input {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-gold);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            font-size: 0.9rem;
            outline: none;
        }
        .manual-input:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 12px var(--gold-glow);
        }

        .btn-lookup {
            background: var(--gold-gradient);
            color: #07060c;
            border: none;
            border-radius: 10px;
            padding: 0 18px;
            font-weight: 800;
            font-size: 0.88rem;
            cursor: pointer;
            white-space: nowrap;
        }

        /* Decoded QR Payload Pill */
        .decoded-qr-banner {
            margin-top: 14px;
            background: rgba(0, 242, 254, 0.08);
            border: 1px solid rgba(0, 242, 254, 0.35);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.8rem;
            color: #e2e8f0;
            display: none;
            line-height: 1.5;
        }

        /* Guest Result & Check-In Panel */
        .result-panel {
            background: linear-gradient(135deg, rgba(26, 21, 42, 0.95) 0%, rgba(16, 13, 26, 0.98) 100%);
            border: 1.5px solid var(--border-gold);
            border-radius: var(--card-radius);
            padding: 24px 28px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.65);
            min-height: 360px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .result-panel.checked-in-border {
            border-color: var(--neon-green);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.7), 0 0 30px rgba(57, 255, 20, 0.2);
        }

        .empty-result-state {
            text-align: center;
            color: var(--text-muted);
            padding: 40px 20px;
        }

        .guest-result-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: center;
        }

        @media (max-width: 820px) {
            .guest-result-grid {
                grid-template-columns: 1fr;
            }
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }
        .status-pill.pending {
            background: rgba(245, 207, 109, 0.15);
            color: var(--gold-primary);
            border: 1px solid var(--gold-primary);
        }
        .status-pill.checked {
            background: rgba(57, 255, 20, 0.15);
            color: var(--neon-green);
            border: 1px solid var(--neon-green);
            box-shadow: 0 0 15px rgba(57, 255, 20, 0.25);
        }

        .guest-name-heading {
            font-size: 1.85rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .guest-meta-sub {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .location-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .loc-chip {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.14);
            padding: 10px 15px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .loc-chip.highlight {
            background: rgba(212, 175, 55, 0.15);
            border-color: var(--gold-primary);
            box-shadow: 0 0 15px var(--gold-glow);
        }

        .loc-chip.checked-highlight {
            background: rgba(57, 255, 20, 0.12);
            border-color: var(--neon-green);
        }

        .chip-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            font-weight: 700;
        }

        .chip-val {
            font-size: 1.02rem;
            font-weight: 800;
            color: #fff;
        }

        .chip-val.gold { color: var(--gold-primary); }
        .chip-val.green { color: var(--neon-green); }

        /* Big Check-In Button */
        .checkin-action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-top: 8px;
        }

        .btn-mark-checkin {
            background: var(--green-gradient);
            color: #04130a;
            border: none;
            border-radius: 14px;
            padding: 15px 28px;
            font-size: 1.05rem;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 25px var(--green-glow);
            transition: all 0.25s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .btn-mark-checkin:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(57, 255, 20, 0.5);
        }

        .btn-undo-checkin {
            background: rgba(230, 57, 70, 0.15);
            border: 1px solid rgba(230, 57, 70, 0.5);
            color: #ff8599;
            border-radius: 12px;
            padding: 12px 18px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-undo-checkin:hover {
            background: rgba(230, 57, 70, 0.3);
        }

        /* Individual Seat / Ticket Check-In Toggles (for multi-seat / multi-ticket orders) */
        .individual-items-box {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .item-toggle-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .item-checkin-pill {
            padding: 7px 12px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid rgba(212, 175, 55, 0.4);
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold-primary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .item-checkin-pill.is-checked {
            border-color: var(--neon-green);
            background: rgba(57, 255, 20, 0.16);
            color: var(--neon-green);
        }

        /* Inline Mini Table Ring Preview in Result Card */
        .inline-ring-card {
            background: rgba(10, 8, 18, 0.85);
            border: 1px solid var(--border-gold);
            border-radius: 18px;
            padding: 16px;
            text-align: center;
            width: 280px;
            flex-shrink: 0;
            margin: 0 auto;
        }

        .seats-container {
            position: relative;
            width: 250px;
            height: 250px;
            margin: 0 auto;
        }

        .modal-table-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.22) 0%, rgba(18, 15, 28, 0.96) 80%);
            border: 2px solid var(--gold-primary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Cinzel', serif;
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--gold-primary);
            box-shadow: 0 0 25px var(--gold-glow);
        }

        .seat-circle {
            position: absolute;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.82rem;
            transition: all 0.25s ease;
            line-height: 1;
        }

        .seat-circle.other-seat {
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.03);
            color: rgba(255, 255, 255, 0.35);
        }

        /* Guest's own seat (not checked in yet) */
        .seat-circle.guest-seat {
            border: 2px solid var(--gold-primary);
            background: var(--gold-gradient);
            color: #07060c;
            box-shadow: 0 0 18px var(--gold-glow);
            transform: scale(1.12);
        }

        /* Checked-In Seated User -> Gets Lock Icon! */
        .seat-circle.checked-in-seat {
            border: 2px solid var(--neon-green);
            background: linear-gradient(135deg, #39ff14 0%, #10b981 100%);
            color: #04130a;
            box-shadow: 0 0 20px var(--green-glow);
            transform: scale(1.15);
        }

        .seat-circle.other-checked-in-seat {
            border: 1.5px solid rgba(57, 255, 20, 0.6);
            background: rgba(57, 255, 20, 0.15);
            color: var(--neon-green);
        }

        .seat-lock-icon {
            font-size: 0.72rem;
            line-height: 1;
            margin-bottom: 1px;
        }

        /* 2D Venue Floor Plan Card */
        .arena-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            padding: 28px 22px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(16px);
        }

        .arena-header-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }

        .arena-header-title {
            font-family: 'Cinzel', serif;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--gold-primary);
            letter-spacing: 0.08em;
        }

        .arena-scroll-container {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 12px;
        }

        .arena-inner-canvas {
            min-width: 1120px;
        }

        .stage-container {
            text-align: center;
            margin-bottom: 28px;
        }

        .stage-banner {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(180deg, rgba(212, 175, 55, 0.14) 0%, rgba(18, 15, 28, 0.92) 100%);
            border: 2px solid var(--gold-primary);
            border-radius: 16px;
            padding: 12px 70px;
            box-shadow: 0 0 30px var(--gold-glow);
        }

        .stage-title {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 0.3em;
            color: var(--gold-primary);
        }

        .stage-subtitle {
            font-size: 0.72rem;
            letter-spacing: 0.2em;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .arena-layout {
            display: grid;
            grid-template-columns: 1fr 180px 1fr;
            gap: 20px;
            align-items: start;
        }

        .side-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .side-header {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.15em;
            padding-bottom: 8px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        .side-header.left { border-color: var(--gold-primary); color: var(--gold-primary); }
        .side-header.right { border-color: #ff758f; color: #ff758f; }

        .category-section {
            border: 1px solid rgba(212, 175, 55, 0.18);
            border-radius: 16px;
            padding: 16px;
            background: rgba(10, 8, 18, 0.55);
        }

        .category-section-title {
            font-size: 0.82rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .platinum-section-title { color: var(--gold-primary); }
        .gold-section-title { color: #ff758f; }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(9, 1fr);
            gap: 7px;
        }

        .table-btn {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.03);
            color: rgba(255, 255, 255, 0.35);
            font-weight: 700;
            font-size: 0.72rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
        }

        /* Highlighted Scanned Table */
        .table-btn.scanned-target-table {
            background: var(--gold-gradient) !important;
            color: #07060c !important;
            border: 2px solid #fff !important;
            font-weight: 900 !important;
            box-shadow: 0 0 25px var(--gold-primary), 0 0 45px rgba(212, 175, 55, 0.8) !important;
            transform: scale(1.22);
            z-index: 20;
            cursor: pointer;
            animation: pulseTargetTable 1.2s infinite alternate;
        }

        .table-btn.scanned-target-table.is-checked-in-table {
            background: var(--green-gradient) !important;
            color: #04130a !important;
            box-shadow: 0 0 25px var(--neon-green), 0 0 45px rgba(57, 255, 20, 0.8) !important;
        }

        @keyframes pulseTargetTable {
            0% { transform: scale(1.15); }
            100% { transform: scale(1.28); }
        }

        .standing-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .standing-box {
            background: rgba(157, 78, 221, 0.06);
            border: 1px dashed rgba(157, 78, 221, 0.4);
            border-radius: 16px;
            padding: 24px 10px;
            text-align: center;
            color: var(--text-muted);
        }

        .standing-box.scanned-target-standing {
            background: linear-gradient(135deg, rgba(157, 78, 221, 0.45), rgba(46, 196, 182, 0.35));
            border: 2px solid #fff;
            color: #fff;
            box-shadow: 0 0 30px var(--purple-glow);
            animation: pulseTargetTable 1.2s infinite alternate;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast-card {
            min-width: 300px;
            padding: 14px 18px;
            border-radius: 14px;
            background: rgba(18, 15, 28, 0.96);
            border: 1px solid var(--border-gold);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
            color: #fff;
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toast-success { border-left: 4px solid var(--neon-green); }
        .toast-error { border-left: 4px solid var(--ruby-red); }
    </style>
</head>
<body>

    <!-- Top Brand Navigation -->
    <header class="brand-nav">
        <div class="brand-left">
            <a href="{{ route('dashboard') }}" class="brand-logo">♠ KALABARA</a>
            <span class="brand-badge">📷 QR Check-In & Seat Scanner</span>
        </div>
        <div class="nav-actions">
            <a href="{{ route('dashboard') }}" class="btn-back-dashboard">
                <span>&larr;</span> Back to Admin Dashboard
            </a>
        </div>
    </header>

    <main class="main-wrapper">

        <!-- Live Check-In & Countdown Metrics Row -->
        <section class="checkin-stats-grid">
            <div class="stat-card card-checked">
                <div class="stat-label">
                    <span>CHECKED-IN GUESTS</span>
                    <span style="color: var(--neon-green);">🔒</span>
                </div>
                <div class="stat-value" style="color: var(--neon-green);">
                    <span id="stat-checked-in">{{ $stats['checked_in'] }}</span>
                    <span style="font-size: 1.05rem; color: var(--text-muted); font-weight: 500;">/ <span id="stat-total-sold">{{ $stats['total_sold'] }}</span></span>
                </div>
                <div class="stat-sub">
                    <span id="stat-percentage">{{ $stats['percentage'] }}</span>% Arrived & Checked In
                </div>
            </div>

            <div class="stat-card card-countdown">
                <div class="stat-label">
                    <span>REMAINING CHECK-IN COUNTDOWN</span>
                    <span style="color: #00f2fe;">⏳</span>
                </div>
                <div class="stat-value" style="color: #00f2fe;" id="stat-remaining">
                    {{ $stats['remaining'] }}
                </div>
                <div class="stat-sub">
                    Tickets Remaining to Check In
                </div>
            </div>

            <div class="stat-card card-sections">
                <div class="stat-label">
                    <span>CHECKED-IN BY SECTION</span>
                    <span style="color: var(--gold-primary);">♦</span>
                </div>
                <div style="font-size: 0.92rem; font-weight: 700; margin-top: 8px; line-height: 1.7;">
                    <span style="color: var(--gold-primary);">♠ Platinum: <strong id="stat-plat-in">{{ $stats['platinum_checked_in'] }}</strong> / <span id="stat-plat-sold">{{ $stats['platinum_sold'] }}</span></span><br>
                    <span style="color: #ff758f;">♥ Gold: <strong id="stat-gold-in">{{ $stats['gold_checked_in'] }}</strong> / <span id="stat-gold-sold">{{ $stats['gold_sold'] }}</span></span> &bull;
                    <span style="color: #c77dff;">♣ Standing: <strong id="stat-stand-in">{{ $stats['standing_checked_in'] }}</strong> / <span id="stat-stand-sold">{{ $stats['standing_sold'] }}</span></span>
                </div>
            </div>
        </section>

        <!-- Scanner + Guest Seat Location Result Split -->
        <section class="scanner-top-grid">

            <!-- Left Column: QR Scanner & Input -->
            <div class="scanner-panel">
                <h2 class="panel-title">📷 Scan Entry Ticket QR Code</h2>
                <p class="panel-subtitle">
                    Point camera at the customer's Entry Ticket QR code, upload a ticket image, or scan/enter Order No.
                </p>

                <!-- Camera Viewport -->
                <div class="camera-box" id="camera-box">
                    <video id="qr-video" playsinline muted></video>
                    <canvas id="qr-canvas" style="display: none;"></canvas>
                    <div class="scan-laser-frame">
                        <div class="scan-laser-line"></div>
                    </div>
                    <div class="camera-placeholder" id="camera-placeholder">
                        <div style="font-size: 2.5rem; margin-bottom: 8px;">📷</div>
                        <div style="font-weight: 700; color: #e2e8f0; margin-bottom: 4px;">Camera Scanner Ready</div>
                        <div style="font-size: 0.78rem;">Click "Start Camera" to scan ticket QR codes</div>
                    </div>
                </div>

                <!-- Camera & Image Upload Controls -->
                <div class="camera-controls">
                    <button type="button" class="btn-action btn-camera-start" id="btn-start-camera" onclick="startCameraScanner()">
                        📷 Start Camera
                    </button>
                    <button type="button" class="btn-action btn-camera-stop" id="btn-stop-camera" onclick="stopCameraScanner()">
                        ⏹ Stop Camera
                    </button>
                    <label class="btn-action btn-upload-qr" for="qr-file-input" style="cursor: pointer;">
                        🖼️ Scan Ticket Image
                    </label>
                    <input type="file" id="qr-file-input" accept="image/*" style="display: none;" onchange="handleQrImageUpload(event)">
                </div>

                <!-- Manual / USB Barcode Gun Input -->
                <form class="manual-lookup-form" onsubmit="handleManualSubmit(event)">
                    <input type="text" id="manual-qr-input" class="manual-input"
                           value="{{ $initialQuery }}"
                           placeholder="Order No or QR URL (e.g. ORDMTU76ZQFUYH58C)..."
                           autocomplete="off">
                    <button type="submit" class="btn-lookup">Locate &rarr;</button>
                </form>

                <!-- Decoded QR Order Info -->
                <div class="decoded-qr-banner" id="decoded-qr-banner"></div>
            </div>

            <!-- Right Column: Scanned Guest Details, Seat Location & Check-In Button -->
            <div class="result-panel" id="result-panel">
                <div class="empty-result-state" id="empty-result-state">
                    <div style="font-size: 3rem; margin-bottom: 10px; opacity: 0.5;">🎫</div>
                    <h3 style="font-size: 1.2rem; color: var(--gold-primary); margin-bottom: 6px;">Waiting for Ticket Scan...</h3>
                    <p style="font-size: 0.86rem; max-width: 420px; margin: 0 auto; line-height: 1.5;">
                        Scan a Kalabara Entry Ticket QR code or enter an Order Number to automatically extract the Order No, view the exact seat location, and mark the guest as Checked-In.
                    </p>
                </div>

                <!-- Populated dynamically when a ticket is scanned -->
                <div id="scanned-booking-container" style="display: none;"></div>
            </div>

        </section>

        <!-- 2D Venue Floor Plan Locator -->
        <section class="arena-card" id="arena-map-section">
            <div class="arena-header-strip">
                <div class="arena-header-title">📍 Live Seat Location Map &bull; Waters Edge</div>
                <div style="display: flex; align-items: center; gap: 16px; font-size: 0.8rem; flex-wrap: wrap;">
                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 14px; height: 14px; border-radius: 4px; background: var(--gold-gradient); display: inline-block;"></span>
                        <strong style="color: var(--gold-primary);">Scanned Guest Table</strong>
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 14px; height: 14px; border-radius: 4px; background: var(--green-gradient); display: inline-block;"></span>
                        <strong style="color: var(--neon-green);">🔒 Checked-In Seated User</strong>
                    </span>
                </div>
            </div>

            <div class="arena-scroll-container" id="arena-scroll-container">
                <div class="arena-inner-canvas">
                    <div class="stage-container">
                        <div class="stage-banner">
                            <div class="stage-title">♠ STAGE ♠</div>
                            <div class="stage-subtitle">KALABARA LIVE &bull; WATERS EDGE</div>
                        </div>
                    </div>

                    <div class="arena-layout">
                        <!-- Left Wing -->
                        <div class="side-container">
                            <div class="side-header left">♠ LEFT WING - L</div>
                            <div class="category-section">
                                <div class="category-section-title platinum-section-title">♠ PLATINUM (Tables 1 - 54)</div>
                                <div class="tables-grid" id="scanner-plat-left"></div>
                            </div>
                            <div class="category-section">
                                <div class="category-section-title gold-section-title">♥ GOLD (Tables 55 - 126)</div>
                                <div class="tables-grid" id="scanner-gold-left"></div>
                            </div>
                        </div>

                        <!-- Standing Column -->
                        <div class="standing-column" id="scanner-standing-col">
                            <div class="side-header" style="color: #c77dff; border-color: rgba(157, 78, 221, 0.3);">♣ STANDING</div>
                            <div class="standing-box" id="sstanding-left">
                                <div style="font-size: 1.4rem; color: #c77dff;">♣</div>
                                <h3 style="font-size: 0.9rem; color: #e0aaff; margin-top: 4px;">Left Standing</h3>
                            </div>
                            <div class="standing-box" id="sstanding-center">
                                <div style="font-size: 1.4rem; color: #c77dff;">♠</div>
                                <h3 style="font-size: 0.9rem; color: #e0aaff; margin-top: 4px;">Center Standing</h3>
                            </div>
                            <div class="standing-box" id="sstanding-right">
                                <div style="font-size: 1.4rem; color: #c77dff;">♥</div>
                                <h3 style="font-size: 0.9rem; color: #e0aaff; margin-top: 4px;">Right Standing</h3>
                            </div>
                        </div>

                        <!-- Right Wing -->
                        <div class="side-container">
                            <div class="side-header right">♥ RIGHT WING - R</div>
                            <div class="category-section">
                                <div class="category-section-title platinum-section-title">♠ PLATINUM (Tables 1 - 54)</div>
                                <div class="tables-grid" id="scanner-plat-right"></div>
                            </div>
                            <div class="category-section">
                                <div class="category-section-title gold-section-title">♥ GOLD (Tables 55 - 126)</div>
                                <div class="tables-grid" id="scanner-gold-right"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <div class="toast-container" id="toast-container"></div>

    <script>
        let currentBooking = null;
        let videoStream = null;
        let scanLoopActive = false;
        let lastScannedRaw = '';
        let lastScanTime = 0;

        const seatsAngles = [
            { id: 1, angle: 0 },
            { id: 2, angle: 45 },
            { id: 3, angle: 90 },
            { id: 4, angle: 135 },
            { id: 5, angle: 180 },
            { id: 6, angle: 225 },
            { id: 7, angle: 270 },
            { id: 8, angle: 315 }
        ];

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const card = document.createElement('div');
            card.className = `toast-card toast-${type}`;
            card.innerHTML = `<span>${type === 'success' ? '✓' : '⚠️'}</span><span>${escapeHtml(message)}</span>`;
            container.appendChild(card);
            setTimeout(() => card.remove(), 4000);
        }

        /**
         * Client-side QR Payload Parser:
         * Extracts Order No ("o") and Ticket Code ("t") from ticketz.lk JWT URLs, /seat/ URLs, JSON, or plain codes.
         */
        function parseQrPayloadClient(raw) {
            const clean = (raw || '').trim();
            let orderNo = null;
            let ticketCode = null;

            // 1. Check for JWT in URL (e.g. https://ticketz.lk/order/verify/eyJ...)
            const jwtMatch = clean.match(/(eyJ[A-Za-z0-9_-]+)\.[A-Za-z0-9_-]+/);
            if (jwtMatch) {
                try {
                    const base64Url = jwtMatch[1];
                    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                    const padded = base64 + '='.repeat((4 - (base64.length % 4)) % 4);
                    const jsonStr = atob(padded);
                    const payload = JSON.parse(jsonStr);
                    orderNo = payload.o || payload.order_no || payload.order || null;
                    ticketCode = payload.t || payload.ticket || null;
                } catch (e) {
                    console.warn('JWT decode warning:', e);
                }
            }

            // 2. Check for raw JSON
            if (!orderNo && !ticketCode && clean.startsWith('{')) {
                try {
                    const payload = JSON.parse(clean);
                    orderNo = payload.o || payload.order_no || payload.order || null;
                    ticketCode = payload.t || payload.ticket || null;
                } catch (e) {}
            }

            // 3. Check for /seat/{ticket} link
            if (!orderNo && !ticketCode) {
                const seatMatch = clean.match(/\/(?:find-)?seat\/([^\/?#]+)/i);
                if (seatMatch) {
                    orderNo = decodeURIComponent(seatMatch[1]);
                }
            }

            return {
                orderNo: orderNo || clean,
                ticketCode: ticketCode,
                raw: clean
            };
        }

        // Render 2D Floor Plan Grids
        function renderScannerGrids() {
            renderGridSection('platinum', 'left', 1, 54, 'scanner-plat-left');
            renderGridSection('platinum', 'right', 1, 54, 'scanner-plat-right');
            renderGridSection('gold', 'left', 55, 126, 'scanner-gold-left');
            renderGridSection('gold', 'right', 55, 126, 'scanner-gold-right');
        }

        function renderGridSection(category, side, start, end, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            container.innerHTML = '';

            for (let i = start; i <= end; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'table-btn';
                btn.id = `stable-${side}-${i}`;
                const label = side.toUpperCase().substring(0, 1) + i;

                const isTarget = currentBooking &&
                    currentBooking.category === category &&
                    currentBooking.side === side &&
                    parseInt(currentBooking.table_number, 10) === i;

                if (isTarget) {
                    btn.classList.add('scanned-target-table');
                    if (currentBooking.all_checked_in && currentBooking.is_seated) {
                        btn.classList.add('is-checked-in-table');
                    }
                    // Show lock icon on table button ONLY for checked-in seated users
                    const hasCheckedInSeat = currentBooking.is_seated && currentBooking.checked_in_tickets > 0;
                    btn.innerHTML = hasCheckedInSeat ? `<span style="font-size:0.65rem;">🔒</span><span>${label}</span>` : `<span>${label}</span>`;
                    btn.onclick = () => scrollToResultCard();
                } else {
                    btn.innerText = label;
                }

                container.appendChild(btn);
            }
        }

        function updateStandingHighlights() {
            ['left', 'center', 'right'].forEach(side => {
                const el = document.getElementById(`sstanding-${side}`);
                if (!el) return;
                const isTarget = currentBooking && currentBooking.category === 'standing' && currentBooking.side === side;
                el.classList.toggle('scanned-target-standing', Boolean(isTarget));
            });
        }

        function scrollToResultCard() {
            const panel = document.getElementById('result-panel');
            if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function locateOnArenaMap() {
            if (!currentBooking) return;
            let target = null;
            if (currentBooking.category === 'standing') {
                target = document.getElementById(`sstanding-${currentBooking.side}`);
            } else if (currentBooking.table_number) {
                target = document.getElementById(`stable-${currentBooking.side}-${currentBooking.table_number}`);
            }

            if (target) {
                const scrollContainer = document.getElementById('arena-scroll-container');
                if (scrollContainer) {
                    const desiredLeft = (target.offsetLeft + (target.clientWidth / 2)) - (scrollContainer.clientWidth / 2);
                    scrollContainer.scrollTo({ left: Math.max(0, desiredLeft), behavior: 'smooth' });
                }
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Lookup QR / Order No via Backend API
        async function lookupTicketCode(rawInput) {
            const clean = (rawInput || '').trim();
            if (!clean) return;

            const parsedClient = parseQrPayloadClient(clean);
            const banner = document.getElementById('decoded-qr-banner');
            if (banner) {
                banner.style.display = 'block';
                banner.innerHTML = `🔍 <strong>Extracted Order No:</strong> <span style="color:#00f2fe; font-family:monospace; font-weight:800;">${escapeHtml(parsedClient.orderNo)}</span>` +
                    (parsedClient.ticketCode ? `<br>🎫 <strong>QR Ticket ID:</strong> <span style="color:var(--gold-primary); font-family:monospace;">${escapeHtml(parsedClient.ticketCode)}</span>` : '');
            }

            const manualInput = document.getElementById('manual-qr-input');
            if (manualInput) manualInput.value = parsedClient.orderNo;

            try {
                const response = await fetch('/api/check-in/lookup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ qr_data: clean })
                });

                const data = await response.json();

                if (data.stats) {
                    updateCheckInStatsUI(data.stats);
                }

                if (!response.ok || !data.found) {
                    currentBooking = null;
                    renderNotFoundState(data.extracted_order_no || parsedClient.orderNo, data.extracted_ticket_code || parsedClient.ticketCode, data.message);
                    renderScannerGrids();
                    updateStandingHighlights();
                    return;
                }

                currentBooking = data.booking;
                renderScannedBookingUI(data.booking, data.extracted_order_no, data.extracted_ticket_code);
                renderScannerGrids();
                updateStandingHighlights();
            } catch (err) {
                console.error('Lookup error:', err);
                showToast('Error looking up ticket order number.', 'error');
            }
        }

        function renderNotFoundState(orderNo, ticketCode, message) {
            document.getElementById('empty-result-state').style.display = 'none';
            const resultPanel = document.getElementById('result-panel');
            resultPanel.classList.remove('checked-in-border');
            const container = document.getElementById('scanned-booking-container');
            container.style.display = 'block';
            container.innerHTML = `
                <div style="text-align: center; padding: 24px 12px;">
                    <div style="font-size: 2.6rem; margin-bottom: 10px;">⚠️</div>
                    <h3 style="font-size: 1.35rem; color: #ff8599; margin-bottom: 8px;">Order Not Found in Reservations</h3>
                    <p style="font-size: 0.92rem; color: var(--text-muted); margin-bottom: 14px;">
                        ${escapeHtml(message || 'No matching reservation found.')}
                    </p>
                    <div style="display: inline-block; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; padding: 10px 18px; text-align: left; font-size: 0.85rem;">
                        <div><strong>Order No:</strong> <span style="color: #00f2fe; font-family: monospace;">${escapeHtml(orderNo || 'N/A')}</span></div>
                        ${ticketCode ? `<div><strong>Ticket Ref:</strong> <span style="color: var(--gold-primary); font-family: monospace;">${escapeHtml(ticketCode)}</span></div>` : ''}
                    </div>
                </div>
            `;
        }

        function renderScannedBookingUI(booking, extractedOrder, extractedTicket) {
            document.getElementById('empty-result-state').style.display = 'none';
            const resultPanel = document.getElementById('result-panel');
            const container = document.getElementById('scanned-booking-container');
            container.style.display = 'block';

            if (booking.all_checked_in) {
                resultPanel.classList.add('checked-in-border');
            } else {
                resultPanel.classList.remove('checked-in-border');
            }

            const isSeated = booking.is_seated; // true for Platinum & Gold, false for Standing
            const lockPrefix = (isSeated && booking.all_checked_in) ? '🔒 ' : '';

            const statusBadgeHtml = booking.all_checked_in
                ? `<div class="status-pill checked">${isSeated ? '🔒' : '✓'} Checked In ${booking.checked_in_at ? '&bull; ' + escapeHtml(booking.checked_in_at) : ''}</div>`
                : (booking.checked_in_tickets > 0
                    ? `<div class="status-pill pending">${isSeated ? '🔒' : '✓'} Partially Checked In (${booking.checked_in_tickets} / ${booking.total_tickets})</div>`
                    : `<div class="status-pill pending">⏳ Ready for Check-In</div>`);

            let locationChipsHtml = `
                <div class="loc-chip">
                    <span class="chip-label">Category</span>
                    <span class="chip-val gold">${escapeHtml(booking.category.toUpperCase())}</span>
                </div>
            `;

            if (booking.category === 'standing') {
                locationChipsHtml += `
                    <div class="loc-chip highlight">
                        <span class="chip-label">Standing Zone</span>
                        <span class="chip-val gold">${escapeHtml(booking.side.toUpperCase())} STANDING</span>
                    </div>
                    <div class="loc-chip">
                        <span class="chip-label">Tickets in Order</span>
                        <span class="chip-val">${booking.total_tickets} ${booking.total_tickets === 1 ? 'Ticket' : 'Tickets'}</span>
                    </div>
                `;
            } else {
                const seatsLabel = booking.is_full_table
                    ? 'Full Table (All 8 Seats)'
                    : booking.seats.map(s => {
                        const isSeatChecked = booking.checked_in_seats && booking.checked_in_seats.includes(s);
                        // Lock icon ONLY for checked-in seated users
                        return (isSeatChecked ? '🔒 ' : '') + 'Seat ' + s;
                    }).join(', ');

                locationChipsHtml += `
                    <div class="loc-chip highlight">
                        <span class="chip-label">Wing & Table</span>
                        <span class="chip-val gold">${escapeHtml(booking.side.toUpperCase())} WING &bull; TABLE ${escapeHtml(booking.table_label)}</span>
                    </div>
                    <div class="loc-chip ${booking.all_checked_in ? 'checked-highlight' : 'highlight'}">
                        <span class="chip-label">Allocated Seat(s)</span>
                        <span class="chip-val ${booking.all_checked_in ? 'green' : 'gold'}">${escapeHtml(seatsLabel)}</span>
                    </div>
                `;
            }

            locationChipsHtml += `
                <div class="loc-chip">
                    <span class="chip-label">Order Check-In Countdown</span>
                    <span class="chip-val" style="color: #00f2fe;">
                        ${booking.checked_in_tickets} Checked In &bull; ${booking.remaining_tickets} Remaining
                    </span>
                </div>
            `;

            // Individual seat/ticket toggle pills if multiple items in one order
            let individualTogglesHtml = '';
            if (booking.items && booking.items.length > 1) {
                const pills = booking.items.map((item, idx) => {
                    const itemLabel = booking.is_seated
                        ? `Seat ${item.seat_number}`
                        : `Ticket #${idx + 1}`;
                    // Lock icon ONLY for checked-in seated users
                    const icon = item.is_checked_in ? (booking.is_seated ? '🔒' : '✓') : '○';
                    return `<button type="button" class="item-checkin-pill ${item.is_checked_in ? 'is-checked' : ''}"
                                onclick="markSingleItemCheckIn(${item.id}, ${!item.is_checked_in})"
                                title="Click to toggle check-in for ${itemLabel}">
                                <span>${icon}</span>
                                <span>${itemLabel}</span>
                            </button>`;
                }).join('');

                individualTogglesHtml = `
                    <div class="individual-items-box">
                        <div style="font-size: 0.76rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700;">
                            Individual ${booking.is_seated ? 'Seats' : 'Tickets'} in this Order (Click to check in individually):
                        </div>
                        <div class="item-toggle-list">${pills}</div>
                    </div>
                `;
            }

            // Right-side visual: 8-Seat Table Ring for Seated users (with Lock icons on checked-in seats)
            let rightVisualHtml = '';
            if (isSeated && booking.table_number) {
                rightVisualHtml = `
                    <div class="inline-ring-card">
                        <div style="font-size: 0.78rem; font-weight: 800; color: var(--gold-primary); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">
                            Table ${escapeHtml(booking.table_label)} Seat Ring
                        </div>
                        <div class="seats-container" id="inline-seats-ring">
                            <div class="modal-table-center">
                                <span>${escapeHtml(booking.table_label)}</span>
                                ${booking.all_checked_in ? '<span style="font-size:0.75rem; margin-top:2px;">🔒</span>' : ''}
                            </div>
                            ${[1,2,3,4,5,6,7,8].map(s => `<div class="seat-circle" id="inline-seat-${s}">${s}</div>`).join('')}
                        </div>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 6px;">
                            🔒 = Checked-In Seat
                        </div>
                    </div>
                `;
            }

            container.innerHTML = `
                <div class="guest-result-grid">
                    <div>
                        ${statusBadgeHtml}
                        <h2 class="guest-name-heading">${lockPrefix}${escapeHtml(booking.name)}</h2>
                        <div class="guest-meta-sub">
                            Order No: <strong style="color: #00f2fe; font-family: monospace;">${escapeHtml(booking.ticket_number)}</strong>
                            ${booking.email ? ` &bull; ${escapeHtml(booking.email)}` : ''}
                        </div>

                        <div class="location-chips">
                            ${locationChipsHtml}
                        </div>

                        <div class="checkin-action-row">
                            ${!booking.all_checked_in ? `
                                <button type="button" class="btn-mark-checkin" onclick="markOrderCheckIn('${escapeHtml(booking.ticket_number)}', true)">
                                    <span>${isSeated ? '🔒' : '✓'}</span>
                                    <span>Mark as Checked-In ${booking.total_tickets > 1 ? '(' + booking.remaining_tickets + ' Remaining)' : ''}</span>
                                </button>
                            ` : `
                                <div style="background: rgba(57, 255, 20, 0.14); border: 1.5px solid var(--neon-green); color: var(--neon-green); padding: 12px 20px; border-radius: 12px; font-weight: 800; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px;">
                                    <span>${isSeated ? '🔒' : '✓'}</span>
                                    <span>All Checked In (${booking.total_tickets} / ${booking.total_tickets})</span>
                                </div>
                            `}

                            ${booking.checked_in_tickets > 0 ? `
                                <button type="button" class="btn-undo-checkin" onclick="markOrderCheckIn('${escapeHtml(booking.ticket_number)}', false)">
                                    ↩ Undo Check-In
                                </button>
                            ` : ''}

                            <button type="button" class="btn-action" style="flex: 0 0 auto; background: rgba(212, 175, 55, 0.15); border: 1px solid var(--gold-primary); color: var(--gold-primary); padding: 12px 18px;" onclick="locateOnArenaMap()">
                                📍 Show on Floor Map
                            </button>
                        </div>

                        ${individualTogglesHtml}
                    </div>

                    ${rightVisualHtml}
                </div>
            `;

            if (isSeated && booking.table_number) {
                positionInlineRingSeats(booking);
            }
        }

        function positionInlineRingSeats(booking) {
            const radius = 88;
            const center = 125;
            seatsAngles.forEach(seat => {
                const el = document.getElementById(`inline-seat-${seat.id}`);
                if (!el) return;

                const rad = (seat.angle - 90) * (Math.PI / 180);
                const x = Math.round(radius * Math.cos(rad));
                const y = Math.round(radius * Math.sin(rad));
                el.style.left = `${center + x - 21}px`;
                el.style.top = `${center + y - 21}px`;

                const isGuestSeat = booking.is_full_table || (booking.seats && booking.seats.includes(seat.id));
                const isGuestCheckedIn = (booking.is_full_table && booking.all_checked_in) ||
                    (booking.checked_in_seats && booking.checked_in_seats.includes(seat.id));
                const isOtherTableSeatCheckedIn = booking.table_checked_in_seats && booking.table_checked_in_seats.includes(seat.id);

                if (isGuestSeat && isGuestCheckedIn) {
                    el.className = 'seat-circle checked-in-seat';
                    // Lock icon for checked-in seated user
                    el.innerHTML = `<span class="seat-lock-icon">🔒</span><span>${seat.id}</span>`;
                    el.title = `Seat ${seat.id} - Checked In`;
                } else if (isGuestSeat) {
                    el.className = 'seat-circle guest-seat';
                    el.innerHTML = `<span>${seat.id}</span>`;
                    el.title = `Seat ${seat.id} - Reserved for ${booking.name}`;
                } else if (isOtherTableSeatCheckedIn) {
                    el.className = 'seat-circle other-checked-in-seat';
                    el.innerHTML = `<span class="seat-lock-icon">🔒</span><span>${seat.id}</span>`;
                    el.title = `Seat ${seat.id} - Checked In (Another Guest)`;
                } else {
                    el.className = 'seat-circle other-seat';
                    el.innerHTML = `<span>${seat.id}</span>`;
                }
            });
        }

        // Mark Entire Order as Checked-In (or Undo)
        async function markOrderCheckIn(ticketNumber, status = true) {
            try {
                const response = await fetch('/api/check-in/mark', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        ticket_number: ticketNumber,
                        status: status
                    })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    showToast(data.message, 'success');
                    currentBooking = data.booking;
                    if (data.stats) updateCheckInStatsUI(data.stats);
                    renderScannedBookingUI(data.booking, data.booking.ticket_number, null);
                    renderScannerGrids();
                    updateStandingHighlights();
                } else {
                    showToast(data.message || 'Failed to update check-in status.', 'error');
                }
            } catch (err) {
                console.error('Check-in error:', err);
                showToast('Error updating check-in status.', 'error');
            }
        }

        // Mark Single Seat / Ticket in a Multi-Seat Order
        async function markSingleItemCheckIn(reservationId, status = true) {
            try {
                const response = await fetch('/api/check-in/mark', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        reservation_id: reservationId,
                        status: status
                    })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    showToast(data.message, 'success');
                    currentBooking = data.booking;
                    if (data.stats) updateCheckInStatsUI(data.stats);
                    renderScannedBookingUI(data.booking, data.booking.ticket_number, null);
                    renderScannerGrids();
                    updateStandingHighlights();
                } else {
                    showToast(data.message || 'Failed to update seat check-in.', 'error');
                }
            } catch (err) {
                console.error('Single item check-in error:', err);
                showToast('Error updating seat check-in.', 'error');
            }
        }

        function updateCheckInStatsUI(stats) {
            const setEl = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.innerText = val;
            };
            setEl('stat-checked-in', stats.checked_in);
            setEl('stat-total-sold', stats.total_sold);
            setEl('stat-remaining', stats.remaining);
            setEl('stat-percentage', stats.percentage);
            setEl('stat-plat-in', stats.platinum_checked_in);
            setEl('stat-plat-sold', stats.platinum_sold);
            setEl('stat-gold-in', stats.gold_checked_in);
            setEl('stat-gold-sold', stats.gold_sold);
            setEl('stat-stand-in', stats.standing_checked_in);
            setEl('stat-stand-sold', stats.standing_sold);
        }

        function handleManualSubmit(e) {
            e.preventDefault();
            const val = document.getElementById('manual-qr-input').value;
            lookupTicketCode(val);
        }

        // Live Camera QR Scanner
        async function startCameraScanner() {
            const video = document.getElementById('qr-video');
            const placeholder = document.getElementById('camera-placeholder');
            const cameraBox = document.getElementById('camera-box');
            const btnStart = document.getElementById('btn-start-camera');
            const btnStop = document.getElementById('btn-stop-camera');

            try {
                videoStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                video.srcObject = videoStream;
                await video.play();

                video.style.display = 'block';
                placeholder.style.display = 'none';
                cameraBox.classList.add('active');
                btnStart.style.display = 'none';
                btnStop.style.display = 'inline-flex';

                scanLoopActive = true;
                requestAnimationFrame(tickCameraFrame);
            } catch (err) {
                console.error('Camera access error:', err);
                showToast('Could not access camera. Please allow camera permission or use Image Upload / Manual Input.', 'error');
            }
        }

        function stopCameraScanner() {
            scanLoopActive = false;
            const video = document.getElementById('qr-video');
            const placeholder = document.getElementById('camera-placeholder');
            const cameraBox = document.getElementById('camera-box');
            const btnStart = document.getElementById('btn-start-camera');
            const btnStop = document.getElementById('btn-stop-camera');

            if (videoStream) {
                videoStream.getTracks().forEach(t => t.stop());
                videoStream = null;
            }
            video.style.display = 'none';
            placeholder.style.display = 'block';
            cameraBox.classList.remove('active');
            btnStart.style.display = 'inline-flex';
            btnStop.style.display = 'none';
        }

        async function tickCameraFrame() {
            if (!scanLoopActive) return;
            const video = document.getElementById('qr-video');
            const canvas = document.getElementById('qr-canvas');

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                let detectedText = null;

                // 1. Try jsQR if loaded
                if (typeof window.jsQR === 'function') {
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = window.jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: 'dontInvert'
                    });
                    if (code && code.data) {
                        detectedText = code.data;
                    }
                }

                // 2. Fallback to native BarcodeDetector
                if (!detectedText && 'BarcodeDetector' in window) {
                    try {
                        const detector = new BarcodeDetector({ formats: ['qr_code'] });
                        const barcodes = await detector.detect(canvas);
                        if (barcodes.length > 0 && barcodes[0].rawValue) {
                            detectedText = barcodes[0].rawValue;
                        }
                    } catch (e) {}
                }

                if (detectedText) {
                    const now = Date.now();
                    if (detectedText !== lastScannedRaw || (now - lastScanTime) > 3500) {
                        lastScannedRaw = detectedText;
                        lastScanTime = now;
                        showToast('QR Code Scanned!', 'success');
                        lookupTicketCode(detectedText);
                    }
                }
            }

            if (scanLoopActive) {
                requestAnimationFrame(tickCameraFrame);
            }
        }

        // Handle Ticket Image Upload QR Scanning
        async function handleQrImageUpload(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;

            const img = new Image();
            const url = URL.createObjectURL(file);

            img.onload = async () => {
                URL.revokeObjectURL(url);
                const canvas = document.getElementById('qr-canvas');
                const ctx = canvas.getContext('2d', { willReadFrequently: true });

                let detectedText = null;

                // Try full image and bottom-half cropped QR region (for full ticket PDFs/images)
                const regions = [
                    { sx: 0, sy: 0, sw: img.width, sh: img.height },
                    { sx: Math.floor(img.width * 0.2), sy: Math.floor(img.height * 0.55), sw: Math.floor(img.width * 0.6), sh: Math.floor(img.height * 0.42) }
                ];

                for (const reg of regions) {
                    canvas.width = reg.sw;
                    canvas.height = reg.sh;
                    ctx.drawImage(img, reg.sx, reg.sy, reg.sw, reg.sh, 0, 0, reg.sw, reg.sh);

                    if (typeof window.jsQR === 'function') {
                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const code = window.jsQR(imageData.data, imageData.width, imageData.height);
                        if (code && code.data) {
                            detectedText = code.data;
                            break;
                        }
                    }

                    if (!detectedText && 'BarcodeDetector' in window) {
                        try {
                            const detector = new BarcodeDetector({ formats: ['qr_code'] });
                            const barcodes = await detector.detect(canvas);
                            if (barcodes.length > 0 && barcodes[0].rawValue) {
                                detectedText = barcodes[0].rawValue;
                                break;
                            }
                        } catch (e) {}
                    }
                }

                event.target.value = '';

                if (detectedText) {
                    showToast('QR Code decoded from ticket image!', 'success');
                    lookupTicketCode(detectedText);
                } else {
                    showToast('Could not detect a QR code in that image. Try cropping closer to the QR code.', 'error');
                }
            };

            img.src = url;
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderScannerGrids();
            const initial = document.getElementById('manual-qr-input').value.trim();
            if (initial) {
                lookupTicketCode(initial);
            }
        });
    </script>
</body>
</html>

