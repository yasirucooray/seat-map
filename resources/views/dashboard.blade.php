<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KALABARA (කලාබර) - Live Concert Seat Reservation</title>
    <!-- 100% Self-Hosted & Local: Zero Third-Party / CDN dependencies -->
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

            --emerald-green: #2ec4b6;
            --neon-green: #39ff14;
            --green-glow: rgba(57, 255, 20, 0.25);

            --royal-purple: #9d4edd;
            --purple-glow: rgba(157, 78, 221, 0.25);

            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-gold: #f3d478;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(212, 175, 55, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(201, 24, 74, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(157, 78, 221, 0.05) 0%, transparent 60%);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Suit Watermark overlay */
        body::before {
            content: "♠ ♥ ♣ ♦ ♠ ♥ ♣ ♦";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            font-size: 14vw;
            letter-spacing: 2vw;
            color: rgba(255, 255, 255, 0.012);
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }
        .toast-card {
            pointer-events: auto;
            min-width: 320px;
            padding: 16px 20px;
            border-radius: 16px;
            background: rgba(18, 15, 28, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-gold);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--text-main);
            transform: translateX(120%);
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            opacity: 0;
        }
        .toast-card.show {
            transform: translateX(0);
            opacity: 1;
        }
        .toast-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: bold;
            flex-shrink: 0;
        }
        .toast-success {
            border-left: 4px solid var(--neon-green);
        }
        .toast-success .toast-icon {
            background: rgba(57, 255, 20, 0.15);
            color: var(--neon-green);
            border: 1px solid rgba(57, 255, 20, 0.4);
        }
        .toast-error {
            border-left: 4px solid var(--ruby-red);
        }
        .toast-error .toast-icon {
            background: rgba(230, 57, 70, 0.15);
            color: var(--ruby-red);
            border: 1px solid rgba(230, 57, 70, 0.4);
        }
        .toast-message {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Loader Overlay */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(7, 6, 12, 0.85);
            backdrop-filter: blur(12px);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .loader-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .loader-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(212, 175, 55, 0.15);
            border-top-color: var(--gold-primary);
            border-bottom-color: var(--ruby-red);
            border-radius: 50%;
            animation: spin 1s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
            margin-bottom: 20px;
            box-shadow: 0 0 20px var(--gold-glow);
        }
        .loader-text {
            color: var(--gold-primary);
            font-family: 'Cinzel', serif;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            font-size: 0.95rem;
            animation: pulse 1.5s infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        /* Header Bar */
        header {
            background: rgba(12, 10, 20, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-gold);
            padding: 14px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.6);
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-logo-card {
            width: 42px;
            height: 48px;
            background: linear-gradient(145deg, #1f1b2e, #0d0b14);
            border: 1px solid var(--gold-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: var(--gold-primary);
            box-shadow: 0 0 15px var(--gold-glow);
            transform: rotate(-4deg);
        }

        .brand-title-group h1 {
            font-family: 'Cinzel', serif;
            font-size: 1.9rem;
            font-weight: 900;
            margin: 0;
            letter-spacing: 0.12em;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px var(--gold-glow);
            line-height: 1;
        }

        .brand-title-group .sub-title {
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .event-info-pills {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-pill {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.82rem;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .info-pill.gold-accent {
            border-color: var(--gold-primary);
            color: var(--gold-primary);
            background: rgba(212, 175, 55, 0.08);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .admin-badge {
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--gold-primary);
            color: var(--gold-primary);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 0 12px var(--gold-glow);
        }

        .logout-btn {
            background: transparent;
            border: 1px solid rgba(230, 57, 70, 0.4);
            color: #f8fafc;
            padding: 7px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: var(--ruby-red);
            border-color: var(--ruby-red);
            box-shadow: 0 0 15px var(--ruby-glow);
        }

        /* Container Layout */
        .dashboard-container {
            max-width: 1440px;
            margin: 30px auto;
            padding: 0 24px;
            position: relative;
            z-index: 1;
        }

        /* Metrics Row */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
            backdrop-filter: blur(12px);
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.6), 0 0 20px var(--gold-glow);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .card-total::before { background: linear-gradient(135deg, #00f2fe 0%, #4facfe 50%, #2ec4b6 100%); }
        .card-platinum::before { background: var(--gold-gradient); }
        .card-gold::before { background: var(--crimson-gradient); }
        .card-standing::before { background: linear-gradient(135deg, #9d4edd, #2ec4b6); }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .metric-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted);
        }

        .metric-suit-icon {
            font-size: 1.4rem;
        }
        .card-total .metric-suit-icon { color: #00f2fe; text-shadow: 0 0 12px rgba(0, 242, 254, 0.5); }
        .card-platinum .metric-suit-icon { color: var(--gold-primary); text-shadow: 0 0 10px var(--gold-glow); }
        .card-gold .metric-suit-icon { color: var(--ruby-red); text-shadow: 0 0 10px var(--ruby-glow); }
        .card-standing .metric-suit-icon { color: var(--royal-purple); text-shadow: 0 0 10px var(--purple-glow); }

        .metric-value {
            font-family: 'Cinzel', serif;
            font-size: 2.3rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        .metric-sub {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .progress-bar-container {
            background: rgba(255, 255, 255, 0.05);
            height: 7px;
            border-radius: 4px;
            margin-top: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            width: 0%;
            transition: width 1s ease-in-out;
        }

        .card-total .progress-bar { background: linear-gradient(135deg, #00f2fe, #2ec4b6); box-shadow: 0 0 10px rgba(0, 242, 254, 0.4); }
        .card-platinum .progress-bar { background: var(--gold-gradient); box-shadow: 0 0 10px var(--gold-glow); }
        .card-gold .progress-bar { background: var(--crimson-gradient); box-shadow: 0 0 10px var(--ruby-glow); }

        /* Arena Card Container */
        .arena-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            margin-bottom: 40px;
            backdrop-filter: blur(16px);
        }

        .stage-container {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
        }

        .stage-banner {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, rgba(212, 175, 55, 0.12) 0%, rgba(18, 15, 28, 0.9) 100%);
            border: 2px solid var(--gold-primary);
            border-radius: 16px;
            padding: 14px 80px;
            box-shadow: 0 0 35px var(--gold-glow), inset 0 0 15px rgba(212, 175, 55, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stage-title {
            font-family: 'Cinzel', serif;
            font-size: 1.7rem;
            font-weight: 900;
            letter-spacing: 0.35em;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
        }

        .stage-subtitle {
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            color: var(--text-muted);
            margin-top: 4px;
            text-transform: uppercase;
        }

        /* Arena Layout Grid */
        .arena-layout {
            display: grid;
            grid-template-columns: 1fr 200px 1fr;
            gap: 24px;
            align-items: start;
        }

        .side-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .side-header {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .side-header.left {
            border-color: var(--gold-primary);
            color: var(--gold-primary);
            text-shadow: 0 0 10px var(--gold-glow);
        }
        .side-header.right {
            border-color: #ff758f;
            color: #ff758f;
            text-shadow: 0 0 10px var(--ruby-glow);
        }

        .category-section {
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 18px;
            padding: 20px;
            background: rgba(10, 8, 18, 0.5);
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .category-section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .platinum-section-title { color: var(--gold-primary); }
        .gold-section-title { color: #ff758f; }

        /* Tables Grid */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(9, 1fr);
            gap: 8px;
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
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.25 ease;
            position: relative;
            overflow: hidden;
        }

        /* Color States for Tables */
        .table-btn.available {
            border-color: rgba(57, 255, 20, 0.3);
            background: rgba(57, 255, 20, 0.03);
            color: #8ade80;
        }
        .table-btn.available:hover {
            background: rgba(57, 255, 20, 0.12);
            border-color: var(--neon-green);
            box-shadow: 0 0 12px var(--green-glow);
            transform: scale(1.05);
        }

        .table-btn.partially-reserved {
            border-color: rgba(245, 207, 109, 0.5);
            background: rgba(245, 207, 109, 0.08);
            color: var(--gold-primary);
        }
        .table-btn.partially-reserved:hover {
            background: rgba(245, 207, 109, 0.2);
            box-shadow: 0 0 12px var(--gold-glow);
            transform: scale(1.05);
        }

        .table-btn.fully-reserved {
            border-color: rgba(230, 57, 70, 0.6);
            background: rgba(230, 57, 70, 0.12);
            color: var(--ruby-red);
        }
        .table-btn.fully-reserved:hover {
            background: rgba(230, 57, 70, 0.25);
            box-shadow: 0 0 12px var(--ruby-glow);
            transform: scale(1.05);
        }

        /* Standing Areas */
        .standing-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
            justify-content: space-between;
            height: 100%;
        }

        .standing-box {
            background: rgba(157, 78, 221, 0.06);
            border: 1px dashed var(--royal-purple);
            border-radius: 18px;
            padding: 24px 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
        }

        .standing-box:hover {
            background: rgba(157, 78, 221, 0.16);
            box-shadow: 0 0 25px var(--purple-glow);
            border-style: solid;
            border-color: #c77dff;
            transform: translateY(-2px);
        }

        .standing-box .suit-icon {
            font-size: 1.5rem;
            color: #c77dff;
            margin-bottom: 6px;
        }

        .standing-box h3 {
            margin: 0 0 6px 0;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #e0aaff;
            font-weight: 700;
        }

        .standing-box p {
            margin: 0;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* Modals and Forms */
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
            max-width: 480px;
            padding: 30px;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.15);
            padding-bottom: 12px;
        }

        .modal-title {
            font-family: 'Cinzel', serif;
            font-size: 1.3rem;
            font-weight: 800;
            margin: 0;
            color: var(--gold-primary);
            letter-spacing: 0.08em;
        }

        .close-modal {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.8rem;
            cursor: pointer;
            transition: color 0.2s ease;
            line-height: 1;
        }

        .close-modal:hover {
            color: var(--ruby-red);
        }

        /* 8 Seats Table Layout */
        .seats-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto 24px auto;
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
            cursor: pointer;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .seat-circle.available {
            border: 1px solid var(--neon-green);
            background: rgba(57, 255, 20, 0.05);
            color: var(--neon-green);
        }

        .seat-circle.available:hover {
            background: var(--neon-green);
            color: #07060c;
            box-shadow: 0 0 15px var(--neon-green);
            transform: scale(1.1);
        }

        .seat-circle.reserved {
            border: 1px solid var(--ruby-red);
            background: rgba(230, 57, 70, 0.12);
            color: var(--ruby-red);
        }

        .seat-circle.reserved:hover {
            box-shadow: 0 0 15px var(--ruby-glow);
            transform: scale(1.05);
        }

        .seat-circle.selected {
            border: 2px solid var(--gold-primary);
            background: var(--gold-gradient);
            color: #07060c;
            box-shadow: 0 0 20px var(--gold-glow);
            transform: scale(1.15);
        }

        .btn-book-selected {
            background: var(--gold-gradient) !important;
            box-shadow: 0 4px 15px var(--gold-glow) !important;
            color: #07060c !important;
            font-weight: 800 !important;
            margin-bottom: 14px;
            display: none;
        }

        .btn-book-selected:hover {
            box-shadow: 0 6px 22px rgba(212, 175, 55, 0.5) !important;
        }

        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
            color: var(--text-muted);
        }

        .form-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 10px;
            padding: 12px 14px;
            color: white;
            font-family: inherit;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--gold-primary);
            box-shadow: 0 0 12px var(--gold-glow);
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-primary {
            width: 100%;
            background: var(--gold-gradient);
            color: #07060c;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 4px 15px var(--gold-glow);
            transition: all 0.3s ease;
            letter-spacing: 0.05em;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(212, 175, 55, 0.45);
        }

        .btn-full-table {
            background: var(--crimson-gradient) !important;
            box-shadow: 0 4px 15px var(--ruby-glow) !important;
            color: #ffffff !important;
            margin-bottom: 14px;
        }

        .btn-full-table:hover {
            box-shadow: 0 6px 20px rgba(230, 57, 70, 0.5) !important;
        }

        /* Tooltip details for reserved seat */
        .reservation-detail-pop {
            background: rgba(25, 20, 38, 0.95);
            border: 1px solid var(--border-gold);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.82rem;
            color: #cbd5e1;
            margin-top: 15px;
            display: none;
            line-height: 1.6;
        }

        /* Recent Bookings Section */
        .recent-section {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 50px;
            backdrop-filter: blur(16px);
        }

        .recent-section h2 {
            font-family: 'Cinzel', serif;
            font-size: 1.3rem;
            font-weight: 800;
            margin: 0 0 20px 0;
            letter-spacing: 0.08em;
            color: var(--gold-primary);
        }

        .recent-table-container {
            overflow-x: auto;
        }

        table.recent-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        table.recent-table th, table.recent-table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.9rem;
        }

        table.recent-table th {
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
        }

        table.recent-table td {
            color: #e2e8f0;
        }

        .category-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .badge-platinum { background: rgba(212, 175, 55, 0.12); color: var(--gold-primary); border: 1px solid rgba(212, 175, 55, 0.3); }
        .badge-gold { background: rgba(230, 57, 70, 0.12); color: #ff758f; border: 1px solid rgba(230, 57, 70, 0.3); }
        .badge-standing { background: rgba(157, 78, 221, 0.12); color: #c77dff; border: 1px solid rgba(157, 78, 221, 0.3); }

        /* =========================================================
           ARENA TOOLBAR & 2D / 3D VIEW SWITCHER
           ========================================================= */
        .arena-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
            padding: 16px 20px;
            background: rgba(14, 11, 23, 0.7);
            border: 1px solid var(--border-gold);
            border-radius: 18px;
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            flex-wrap: wrap;
        }

        /* =========================================================
           UNIVERSAL ARENA SEARCH BOX
           ========================================================= */
        .arena-search-box {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-grow: 1;
            max-width: 580px;
        }

        .search-input-group {
            position: relative;
            flex-grow: 1;
            display: flex;
            align-items: center;
        }

        .search-glass-icon {
            position: absolute;
            left: 14px;
            font-size: 0.95rem;
            color: var(--text-muted);
            pointer-events: none;
        }

        .seat-search-input {
            width: 100%;
            background: rgba(7, 5, 13, 0.8);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 24px;
            padding: 10px 38px 10px 40px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            color: #fff;
            transition: all 0.25s ease;
            outline: none;
        }

        .seat-search-input:focus {
            border-color: #00f2fe;
            box-shadow: 0 0 18px rgba(0, 242, 254, 0.35);
            background: rgba(10, 8, 18, 0.95);
        }

        .search-clear-btn {
            position: absolute;
            right: 12px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.25rem;
            line-height: 1;
            cursor: pointer;
            padding: 2px 6px;
            transition: color 0.2s ease;
        }

        .search-clear-btn:hover {
            color: #ff4d6d;
        }

        .search-status-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .search-match-count {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .search-nav-btns {
            display: inline-flex;
            align-items: center;
            background: rgba(0, 242, 254, 0.1);
            border: 1px solid rgba(0, 242, 254, 0.3);
            border-radius: 20px;
            padding: 2px 6px;
            gap: 4px;
        }

        .search-step-btn {
            background: transparent;
            border: none;
            color: #00f2fe;
            font-size: 1.1rem;
            font-weight: bold;
            line-height: 1;
            cursor: pointer;
            padding: 0 4px;
            transition: transform 0.15s ease;
        }

        .search-step-btn:hover {
            transform: scale(1.25);
        }

        .search-step-index {
            font-size: 0.75rem;
            color: #00f2fe;
            font-weight: 700;
            padding: 0 4px;
        }

        /* =========================================================
           SEARCH RESULTS DROPDOWN PANEL
           ========================================================= */
        .search-dropdown-panel {
            background: rgba(18, 14, 28, 0.97);
            border: 1px solid #00f2fe;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.7), 0 0 25px rgba(0, 242, 254, 0.25);
            border-radius: 18px;
            margin-bottom: 24px;
            padding: 16px;
            max-height: 280px;
            overflow-y: auto;
            backdrop-filter: blur(16px);
            animation: fadeInDown 0.25s ease;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .search-dropdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            margin-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.85rem;
            font-weight: 700;
            color: #00f2fe;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .search-dropdown-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
            line-height: 1;
        }

        .search-dropdown-close:hover {
            color: #ff4d6d;
        }

        .search-dropdown-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 10px;
        }

        .search-dropdown-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(0, 242, 254, 0.2);
            border-radius: 12px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-dropdown-item:hover, .search-dropdown-item.active {
            background: rgba(0, 242, 254, 0.12);
            border-color: #00f2fe;
            box-shadow: 0 0 15px rgba(0, 242, 254, 0.3);
            transform: translateY(-2px);
        }

        .search-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .search-item-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: #fff;
        }

        .search-item-badge {
            background: rgba(0, 242, 254, 0.15);
            color: #00f2fe;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 8px;
            border: 1px solid rgba(0, 242, 254, 0.35);
        }

        .search-item-sub {
            display: flex;
            flex-direction: column;
            gap: 3px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .search-view-btn {
            background: rgba(0, 242, 254, 0.2);
            border: 1px solid #00f2fe;
            color: #fff;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            margin-top: 6px;
            align-self: flex-start;
            transition: background 0.2s;
        }
        .search-view-btn:hover {
            background: #00f2fe;
            color: #07060c;
        }

        /* =========================================================
           SEARCH HIGHLIGHTING IN 2D & 3D
           ========================================================= */
        .table-btn.search-matched-table {
            border-color: #00f2fe !important;
            background: rgba(0, 242, 254, 0.28) !important;
            color: #ffffff !important;
            box-shadow: 0 0 20px #00f2fe, inset 0 0 12px #00f2fe !important;
            transform: scale(1.18) !important;
            z-index: 15;
            animation: searchGlowPulse 1.2s infinite alternate !important;
        }

        @keyframes searchGlowPulse {
            0% {
                box-shadow: 0 0 12px rgba(0, 242, 254, 0.6), inset 0 0 8px rgba(0, 242, 254, 0.4);
                border-color: #00f2fe;
            }
            100% {
                box-shadow: 0 0 28px rgba(0, 242, 254, 1), 0 0 45px rgba(0, 242, 254, 0.6), inset 0 0 16px rgba(0, 242, 254, 0.8);
                border-color: #ffffff;
            }
        }

        .standing-box.search-matched-standing {
            border-color: #00f2fe !important;
            border-style: solid !important;
            background: rgba(0, 242, 254, 0.2) !important;
            box-shadow: 0 0 30px rgba(0, 242, 254, 0.6) !important;
            animation: searchGlowPulse 1.2s infinite alternate !important;
        }

        .seat-circle.search-matched-seat {
            border-color: #00f2fe !important;
            background: radial-gradient(circle, #00f2fe 0%, #005f73 100%) !important;
            color: #07060c !important;
            font-weight: 900 !important;
            box-shadow: 0 0 20px #00f2fe, 0 0 35px #00f2fe !important;
            animation: searchGlowPulse 1.2s infinite alternate !important;
        }

        .modal-search-match-banner {
            background: rgba(0, 242, 254, 0.12);
            border: 1px solid rgba(0, 242, 254, 0.4);
            border-radius: 12px;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 0.82rem;
            color: #00f2fe;
            text-align: center;
        }

        /* Responsive Layout Adjustments */
        @media (max-width: 1240px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
        }

        @media (max-width: 1180px) {
            .arena-layout {
                grid-template-columns: 1fr;
                gap: 36px;
            }
            .standing-column {
                flex-direction: row;
                height: auto;
                gap: 15px;
            }
            .standing-box {
                padding: 20px 10px;
            }
        }

        @media (max-width: 860px) {
            header {
                padding: 16px 20px;
                flex-direction: column;
                gap: 14px;
                text-align: center;
            }
            .header-brand {
                flex-direction: column;
                gap: 8px;
            }
            .event-info-pills {
                flex-wrap: wrap;
                justify-content: center;
            }
            .metrics-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .arena-card {
                padding: 24px 16px;
            }
            .stage-banner {
                padding: 10px 30px;
            }
            .stage-title {
                font-size: 1.3rem;
                letter-spacing: 0.2em;
            }
            .tables-grid {
                grid-template-columns: repeat(6, 1fr);
                gap: 6px;
            }
            .recent-section {
                padding: 20px 12px;
            }
            table.recent-table th, table.recent-table td {
                padding: 10px 8px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .standing-column {
                flex-direction: column;
            }
            .tables-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            .modal-content {
                padding: 20px 16px;
                width: 92%;
            }
            .seats-container {
                transform: scale(0.85);
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Header -->
    <header>
        <div class="header-brand">
            <div class="brand-logo-card">♠</div>
            <div class="brand-title-group">
                <h1>කලාබර</h1>
                <div class="sub-title">
                    <span>KALABARA CONCERT</span>
                    <span>•</span>
                    <span>WATERS EDGE</span>
                </div>
            </div>
        </div>

        <div class="event-info-pills">
            <div class="info-pill gold-accent">
                📅 <strong>20 NOV</strong> (6:30 PM)
            </div>
            <div class="info-pill">
                📍 <strong>WATERS EDGE</strong>
            </div>
            <div class="info-pill">
                📞 <strong>077 336 6324</strong>
            </div>
            <div class="info-pill" style="border-color: rgba(0, 242, 254, 0.4); color: #00f2fe; background: rgba(0, 242, 254, 0.08);">
                🎟️ <strong><span id="header-total-sold">{{ $stats['total']['sold'] }}</span> TOTAL SOLD</strong>
            </div>
        </div>

        <div class="header-actions">
            <span class="admin-badge">♠ ADMIN CONTROL</span>
            <form action="/logout" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="logout-btn">Log Out</button>
            </form>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Metrics Row -->
        <div class="metrics-grid">
            <!-- Total Tickets Sold -->
            <div class="metric-card card-total">
                <div class="metric-header">
                    <div class="metric-title">TOTAL TICKETS SOLD</div>
                    <div class="metric-suit-icon">♦</div>
                </div>
                <div class="metric-value" id="stats-total-sold">{{ $stats['total']['sold'] }}</div>
                <div class="metric-sub" id="stats-total-sub">
                    <span style="color: var(--gold-primary);">♠ {{ $stats['platinum']['sold'] }}</span> &bull; 
                    <span style="color: #ff758f;">♥ {{ $stats['gold']['sold'] }}</span> &bull; 
                    <span style="color: #c77dff;">♣ {{ $stats['standing']['sold'] }}</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="stats-total-progress" style="width: {{ $stats['total']['percentage'] }}%"></div>
                </div>
            </div>

            <!-- Platinum -->
            <div class="metric-card card-platinum">
                <div class="metric-header">
                    <div class="metric-title">PLATINUM SUITE</div>
                    <div class="metric-suit-icon">♠</div>
                </div>
                <div class="metric-value" id="stats-platinum-sold">{{ $stats['platinum']['sold'] }} <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);">/ {{ $stats['platinum']['capacity'] }}</span></div>
                <div class="metric-sub"><span id="stats-platinum-pct">{{ $stats['platinum']['percentage'] }}</span>% Reserved</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="stats-platinum-progress" style="width: {{ $stats['platinum']['percentage'] }}%"></div>
                </div>
            </div>

            <!-- Gold -->
            <div class="metric-card card-gold">
                <div class="metric-header">
                    <div class="metric-title">GOLD SUITE</div>
                    <div class="metric-suit-icon">♥</div>
                </div>
                <div class="metric-value" id="stats-gold-sold">{{ $stats['gold']['sold'] }} <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);">/ {{ $stats['gold']['capacity'] }}</span></div>
                <div class="metric-sub"><span id="stats-gold-pct">{{ $stats['gold']['percentage'] }}</span>% Reserved</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="stats-gold-progress" style="width: {{ $stats['gold']['percentage'] }}%"></div>
                </div>
            </div>

            <!-- Standing -->
            <div class="metric-card card-standing">
                <div class="metric-header">
                    <div class="metric-title">STANDING ARENA</div>
                    <div class="metric-suit-icon">♣</div>
                </div>
                <div class="metric-value" id="stats-standing-sold">{{ $stats['standing']['sold'] }}</div>
                <div class="metric-sub">Open Standing Area</div>
            </div>
        </div>

        <!-- Main Arena Booking Card -->
        <div class="arena-card" id="arena-main-card">
            <!-- Arena Command Toolbar: Real-Time Universal Search -->
            <div class="arena-toolbar">
                <div class="arena-search-box">
                    <div class="search-input-group">
                        <span class="search-glass-icon">🔍</span>
                        <input type="text" id="seat-search-input" class="seat-search-input" 
                               placeholder="Search guest name, email, order no (e.g. Prabath, ORDMQ...)..." 
                               oninput="handleSeatSearch(this.value)" autocomplete="off" />
                        <button type="button" class="search-clear-btn" id="search-clear-btn" onclick="clearSeatSearch()" title="Clear search" style="display:none;">&times;</button>
                    </div>

                    <div class="search-status-bar" id="search-status-bar" style="display:none;">
                        <span class="search-match-count" id="search-match-count">0 found</span>
                        <div class="search-nav-btns" id="search-nav-btns">
                            <button type="button" class="search-step-btn" onclick="stepSearchMatch(-1)" title="Previous match">&lsaquo;</button>
                            <span class="search-step-index" id="search-step-index">1/1</span>
                            <button type="button" class="search-step-btn" onclick="stepSearchMatch(1)" title="Next match">&rsaquo;</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Results Dropdown Tray -->
            <div class="search-dropdown-panel" id="search-dropdown-panel" style="display:none;">
                <div class="search-dropdown-header">
                    <span class="dropdown-title" id="search-dropdown-title">Matching Reservations</span>
                    <button type="button" class="search-dropdown-close" onclick="closeSearchDropdown()">&times;</button>
                </div>
                <div class="search-dropdown-list" id="search-dropdown-list">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- 2D Floor Plan Container -->
            <div id="arena-2d-container" class="arena-view-container active">
                <div class="stage-container">
                    <div class="stage-banner">
                        <div class="stage-title">♠ STAGE ♠</div>
                        <div class="stage-subtitle">KALABARA LIVE • WATERS EDGE</div>
                    </div>
                </div>

                <div class="arena-layout">
                    <!-- Left Side Container (L) -->
                    <div class="side-container">
                        <div class="side-header left">♠ LEFT WING - L</div>

                        <!-- Platinum Section -->
                        <div class="category-section">
                            <div class="category-section-title platinum-section-title">
                                <span>♠</span> PLATINUM (Tables 1 - 54)
                            </div>
                            <div class="tables-grid" id="platinum-left-grid">
                                <!-- Populated via JS -->
                            </div>
                        </div>

                        <!-- Gold Section -->
                        <div class="category-section">
                            <div class="category-section-title gold-section-title">
                                <span>♥</span> GOLD (Tables 55 - 126)
                            </div>
                            <div class="tables-grid" id="gold-left-grid">
                                <!-- Populated via JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Standing Column (Middle) -->
                    <div class="standing-column">
                        <div class="side-header" style="color: #c77dff; border-color: rgba(157, 78, 221, 0.3);">♣ STANDING</div>
                        <div class="standing-box" id="standing-box-left" onclick="openStandingBooking('left')">
                            <div class="suit-icon">♣</div>
                            <h3>Left Standing</h3>
                            <p>Click to reserve</p>
                        </div>
                        <div class="standing-box" id="standing-box-center" onclick="openStandingBooking('center')">
                            <div class="suit-icon">♠</div>
                            <h3>Center Standing</h3>
                            <p>Click to reserve</p>
                        </div>
                        <div class="standing-box" id="standing-box-right" onclick="openStandingBooking('right')">
                            <div class="suit-icon">♥</div>
                            <h3>Right Standing</h3>
                            <p>Click to reserve</p>
                        </div>
                    </div>

                    <!-- Right Side Container (R) -->
                    <div class="side-container">
                        <div class="side-header right">♥ RIGHT WING - R</div>

                        <!-- Platinum Section -->
                        <div class="category-section">
                            <div class="category-section-title platinum-section-title">
                                <span>♠</span> PLATINUM (Tables 1 - 54)
                            </div>
                            <div class="tables-grid" id="platinum-right-grid">
                                <!-- Populated via JS -->
                            </div>
                        </div>

                        <!-- Gold Section -->
                        <div class="category-section">
                            <div class="category-section-title gold-section-title">
                                <span>♥</span> GOLD (Tables 55 - 126)
                            </div>
                            <div class="tables-grid" id="gold-right-grid">
                                <!-- Populated via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reservations Table -->
        <div class="recent-section">
            <h2>Recent Kalabara Reservations</h2>
            <div class="recent-table-container">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Category</th>
                            <th>Side</th>
                            <th>Table</th>
                            <th>Seat / Option</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Ticket No</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="recent-bookings-list">
                    @if (isset($recentBookings) && count($recentBookings) > 0)
                        @foreach ($recentBookings as $booking)
                            <tr>
                                <td>{{ $booking->created_at->format('Y-m-d H:i') }}</td>
                                <td><span class="category-badge badge-{{ $booking->category }}">{{ $booking->category }}</span></td>
                                <td>{{ $booking->side ? ucfirst($booking->side) : 'Center' }}</td>
                                <td>{{ $booking->table_number ? (strtoupper(substr($booking->side, 0, 1)) . $booking->table_number) : 'N/A' }}</td>
                                <td>{{ $booking->is_full_table ? 'Full Table' : ($booking->seat_number ? 'Seat '.$booking->seat_number : 'N/A') }}</td>
                                <td>{{ $booking->name }}</td>
                                <td>{{ $booking->email }}</td>
                                <td>{{ $booking->ticket_number }}</td>
                                <td>
                                    <button onclick="copyCustomerSeatUrl('{{ $booking->ticket_number }}')" class="btn-primary" style="padding: 4px 8px; font-size: 0.75rem; background: var(--gold-gradient); color: #110e1c; font-weight: 700; margin-right: 6px;" title="Copy Customer Anonymous Seat Locator Link">🔗 Copy Link</button>
                                    <button onclick="confirmDeleteBooking('{{ $booking->id }}')" class="logout-btn" style="padding: 4px 10px; font-size: 0.8rem;">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted);">No reservations found.</td>
                        </tr>
                    @endif
                </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Seating Details Ring Modal -->
    <div class="modal-overlay" id="seating-modal">
        <div class="modal-content" style="max-width: 420px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-table-title">Table Detail</h3>
                <button class="close-modal" onclick="closeModal('seating-modal')">&times;</button>
            </div>
            
            <div class="modal-search-match-banner" id="modal-search-match-banner" style="display: none;"></div>
            
            <div class="seats-container">
                <div class="modal-table-center" id="modal-table-center-id">L1</div>
                <!-- 8 Seats positioned absolutely using trigonometry -->
                <div class="seat-circle" id="seat-1" data-seat="1">1</div>
                <div class="seat-circle" id="seat-2" data-seat="2">2</div>
                <div class="seat-circle" id="seat-3" data-seat="3">3</div>
                <div class="seat-circle" id="seat-4" data-seat="4">4</div>
                <div class="seat-circle" id="seat-5" data-seat="5">5</div>
                <div class="seat-circle" id="seat-6" data-seat="6">6</div>
                <div class="seat-circle" id="seat-7" data-seat="7">7</div>
                <div class="seat-circle" id="seat-8" data-seat="8">8</div>
            </div>

            <p id="seat-select-hint" style="text-align: center; font-size: 0.82rem; color: var(--text-muted); margin: 0 0 16px 0;">Select available seats to book together.</p>

            <button class="btn-primary btn-book-selected" id="btn-book-selected" onclick="openMultiSeatBooking()">Book Selected Seats (0)</button>

            <button class="btn-primary btn-full-table" id="btn-reserve-full" onclick="initiateFullTableBooking()">Reserve Full Table</button>
            
            <div class="reservation-detail-pop" id="seat-detail-bubble">
                <strong>Reserved by:</strong> <span id="bubble-name" style="color: var(--gold-primary);"></span><br>
                <strong>Email:</strong> <span id="bubble-email"></span><br>
                <strong>Ticket No:</strong> <span id="bubble-ticket" style="color: var(--gold-primary);"></span><br><br>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button id="btn-copy-seat-url" class="btn-primary" style="background: var(--gold-gradient); color: #110e1c; font-weight: 800; padding: 8px 12px; font-size: 0.85rem;" onclick="copyActiveSeatUrl()">🔗 Copy Customer Link</button>
                    <button id="btn-delete-seat" class="btn-primary" style="background: var(--crimson-gradient); box-shadow: 0 4px 15px var(--ruby-glow); padding: 8px 12px; font-size: 0.85rem; color: #fff;" onclick="deleteActiveSeatBooking()">Delete Ticket</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Input Form Modal -->
    <div class="modal-overlay" id="booking-form-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="booking-title">Create Reservation</h3>
                <button class="close-modal" onclick="closeModal('booking-form-modal')">&times;</button>
            </div>
            <form id="booking-submit-form" onsubmit="submitBooking(event)">
                <input type="hidden" id="form-category">
                <input type="hidden" id="form-side">
                <input type="hidden" id="form-table-number">
                <input type="hidden" id="form-seat-number">
                <input type="hidden" id="form-seat-numbers">
                <input type="hidden" id="form-is-full-table" value="false">

                <div id="booking-summary-text" style="background: rgba(212, 175, 55, 0.06); padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; color: var(--gold-primary); border: 1px solid var(--border-gold);">
                    <!-- Dynamic summary details -->
                </div>

                <div class="form-group">
                    <label for="form-name">Guest Full Name</label>
                    <input type="text" id="form-name" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label for="form-email">Email Address</label>
                    <input type="email" id="form-email" required placeholder="john@example.com">
                </div>
                <div class="form-group">
                    <label for="form-ticket">Ticket Number</label>
                    <input type="text" id="form-ticket" required placeholder="KAL-1001">
                </div>
                <div class="form-group" id="ticket-count-group" style="display: none;">
                    <label for="form-ticket-count">Number of Tickets</label>
                    <input type="number" id="form-ticket-count" min="1" value="1" placeholder="1">
                </div>

                <button type="submit" class="btn-primary">Confirm Kalabara Reservation</button>
            </form>
        </div>
    </div>

    <!-- Success Confirmation Modal -->
    <div class="modal-overlay" id="success-modal">
        <div class="modal-content" style="max-width: 420px; text-align: center; border-color: var(--border-gold);">
            <div style="margin: 15px auto 10px; width: 64px; height: 64px; border-radius: 50%; background: rgba(212, 175, 55, 0.12); border: 2px solid var(--gold-primary); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; color: var(--gold-primary); box-shadow: 0 0 25px var(--gold-glow);">
                ♠
            </div>
            <h3 class="modal-title" style="margin-bottom: 20px; font-size: 1.5rem; background: var(--gold-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Booking Confirmed!</h3>
            
            <div style="background: rgba(255, 255, 255, 0.03); padding: 16px; border-radius: 12px; margin-bottom: 24px; text-align: left; font-size: 0.9rem; border: 1px solid var(--border-gold); line-height: 1.7; color: var(--text-main);">
                <strong>Guest:</strong> <span id="success-guest-name" style="color: var(--gold-primary);"></span><br>
                <strong>Section:</strong> <span id="success-category" style="color: var(--gold-primary);"></span><br>
                <strong>Location:</strong> <span id="success-details" style="color: var(--gold-primary);"></span><br>
                <strong>Ticket No:</strong> <span id="success-ticket" style="color: var(--gold-primary);"></span>
            </div>

            <div style="background: rgba(0, 242, 254, 0.08); border: 1px solid rgba(0, 242, 254, 0.35); border-radius: 12px; padding: 12px; margin-bottom: 20px; text-align: left;">
                <label style="font-size: 0.75rem; font-weight: 700; color: #00f2fe; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 6px;">
                    🔗 Customer Anonymous Seat Locator URL:
                </label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="success-customer-url" readonly style="flex: 1; background: rgba(7, 5, 13, 0.9); border: 1px solid rgba(0, 242, 254, 0.4); border-radius: 8px; padding: 8px 12px; font-size: 0.8rem; color: #fff; font-family: monospace;" />
                    <button type="button" class="btn-primary" style="padding: 8px 14px; font-size: 0.8rem; background: var(--gold-gradient); color: #110e1c; font-weight: 800; border: none; border-radius: 8px; cursor: pointer; white-space: nowrap;" onclick="copySuccessCustomerUrl()">
                        Copy Link
                    </button>
                </div>
                <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 6px;">
                    * Send this link to the customer. They will only see their own seat with zero other guests or sales data.
                </small>
            </div>

            <button class="btn-primary" onclick="closeModal('success-modal')">Done</button>
        </div>
    </div>

    <script>
        // Trigonometric seat positioning (radius 105px)
        const radius = 105;
        const seats = [
            { id: 1, angle: 0 },
            { id: 2, angle: 45 },
            { id: 3, angle: 90 },
            { id: 4, angle: 135 },
            { id: 5, angle: 180 },
            { id: 6, angle: 225 },
            { id: 7, angle: 270 },
            { id: 8, angle: 315 }
        ];

        seats.forEach(seat => {
            const el = document.getElementById(`seat-${seat.id}`);
            const rad = (seat.angle - 90) * (Math.PI / 180);
            const x = Math.round(radius * Math.cos(rad));
            const y = Math.round(radius * Math.sin(rad));
            
            // Adjust coordinates relative to center (150, 150) - half seat size (22)
            el.style.left = `${150 + x - 22}px`;
            el.style.top = `${150 + y - 22}px`;
        });

        // Global State
        let reservationsList = [];
        window.reservationsList = reservationsList;
        let activeCategory = '';
        let activeSide = '';
        let activeTable = null;
        let activeBooking = null;
        let selectedSeats = [];

        // Fetch current bookings and render grids
        async function fetchBookingsAndRender() {
            try {
                const response = await fetch('/api/bookings');
                if (response.ok) {
                    const data = await response.json();
                    reservationsList = data.reservations || [];
                    window.reservationsList = reservationsList;
                } else {
                    console.error("Bookings API returned status:", response.status);
                }
            } catch (err) {
                console.error("Error fetching bookings:", err);
            } finally {
                renderGrids();
                updateStats();
            }
        }

        // Update statistics
        function updateStats() {
            // Platinum Stats
            const platFull = reservationsList.filter(r => r.category === 'platinum' && r.is_full_table).length;
            const platInd = reservationsList.filter(r => r.category === 'platinum' && !r.is_full_table).length;
            const platSold = (platFull * 8) + platInd;
            const platCap = 108 * 8;
            const platPct = platCap > 0 ? ((platSold / platCap) * 100).toFixed(1) : 0;

            const elPlatSold = document.getElementById('stats-platinum-sold');
            if (elPlatSold) elPlatSold.innerHTML = `${platSold} <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);">/ ${platCap}</span>`;
            const elPlatPct = document.getElementById('stats-platinum-pct');
            if (elPlatPct) elPlatPct.innerText = platPct;
            const elPlatProg = document.getElementById('stats-platinum-progress');
            if (elPlatProg) elPlatProg.style.width = `${platPct}%`;

            // Gold Stats
            const goldFull = reservationsList.filter(r => r.category === 'gold' && r.is_full_table).length;
            const goldInd = reservationsList.filter(r => r.category === 'gold' && !r.is_full_table).length;
            const goldSold = (goldFull * 8) + goldInd;
            const goldCap = 144 * 8;
            const goldPct = goldCap > 0 ? ((goldSold / goldCap) * 100).toFixed(1) : 0;

            const elGoldSold = document.getElementById('stats-gold-sold');
            if (elGoldSold) elGoldSold.innerHTML = `${goldSold} <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);">/ ${goldCap}</span>`;
            const elGoldPct = document.getElementById('stats-gold-pct');
            if (elGoldPct) elGoldPct.innerText = goldPct;
            const elGoldProg = document.getElementById('stats-gold-progress');
            if (elGoldProg) elGoldProg.style.width = `${goldPct}%`;

            // Standing Stats
            const standingSold = reservationsList.filter(r => r.category === 'standing').length;
            const elStandingSold = document.getElementById('stats-standing-sold');
            if (elStandingSold) elStandingSold.innerText = standingSold;

            // Total Tickets Sold Stats
            const totalSold = platSold + goldSold + standingSold;
            const totalSeatedCap = platCap + goldCap;
            const totalSeatedSold = platSold + goldSold;
            const totalSeatedPct = totalSeatedCap > 0 ? ((totalSeatedSold / totalSeatedCap) * 100).toFixed(1) : 0;

            const elTotalSold = document.getElementById('stats-total-sold');
            if (elTotalSold) elTotalSold.innerText = totalSold;

            const elTotalSub = document.getElementById('stats-total-sub');
            if (elTotalSub) {
                elTotalSub.innerHTML = `<span style="color: var(--gold-primary);">♠ ${platSold}</span> &bull; <span style="color: #ff758f;">♥ ${goldSold}</span> &bull; <span style="color: #c77dff;">♣ ${standingSold}</span>`;
            }

            const elTotalProg = document.getElementById('stats-total-progress');
            if (elTotalProg) elTotalProg.style.width = `${totalSeatedPct}%`;

            const elHeaderTotal = document.getElementById('header-total-sold');
            if (elHeaderTotal) elHeaderTotal.innerText = totalSold;
        }

        // Render Table Grids
        function renderGrids() {
            renderSectionGrid('platinum', 'left', 1, 54, 'platinum-left-grid');
            renderSectionGrid('platinum', 'right', 1, 54, 'platinum-right-grid');
            renderSectionGrid('gold', 'left', 55, 126, 'gold-left-grid');
            renderSectionGrid('gold', 'right', 55, 126, 'gold-right-grid');

            if (window.apply2DSearchHighlights) {
                window.apply2DSearchHighlights();
            }
        }

        function renderSectionGrid(category, side, start, end, elementId) {
            const container = document.getElementById(elementId);
            if (!container) return;
            container.innerHTML = '';

            for (let i = start; i <= end; i++) {
                const btn = document.createElement('button');
                btn.className = 'table-btn';
                btn.id = `table-btn-${side}-${i}`;
                btn.setAttribute('data-category', category);
                btn.setAttribute('data-side', side);
                btn.setAttribute('data-table', i);

                const label = side.toUpperCase().substring(0,1) + i;
                btn.innerText = label;
                btn.setAttribute('data-label', label);

                const bookingsForTable = reservationsList.filter(r => 
                    r.category && r.category.toLowerCase() === category.toLowerCase() && 
                    r.side && r.side.toLowerCase() === side.toLowerCase() && 
                    parseInt(r.table_number, 10) === parseInt(i, 10)
                );
                const isFull = bookingsForTable.some(r => r.is_full_table);
                const bookedSeatsCount = bookingsForTable.length;

                if (isFull || bookedSeatsCount >= 8) {
                    btn.classList.add('fully-reserved');
                } else if (bookedSeatsCount > 0) {
                    btn.classList.add('partially-reserved');
                } else {
                    btn.classList.add('available');
                }

                btn.onclick = () => openSeatingModal(category, side, i);
                container.appendChild(btn);
            }
        }

        function escapeModalHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Seating Modal Setup
        function openSeatingModal(category, side, tableNumber) {
            activeCategory = category;
            activeSide = side;
            activeTable = tableNumber;
            selectedSeats = [];

            const suitSymbol = category === 'platinum' ? '♠' : '♥';
            const title = `${suitSymbol} ${category.toUpperCase()} - Table ${side.toUpperCase().substring(0,1)}${tableNumber}`;
            document.getElementById('modal-table-title').innerText = title;
            document.getElementById('modal-table-center-id').innerText = side.toUpperCase().substring(0,1) + tableNumber;

            const bookings = reservationsList.filter(r => 
                r.category && r.category.toLowerCase() === category.toLowerCase() && 
                r.side && r.side.toLowerCase() === side.toLowerCase() && 
                parseInt(r.table_number, 10) === parseInt(tableNumber, 10)
            );
            const isFullTableBooked = bookings.some(r => r.is_full_table);

            const bubble = document.getElementById('seat-detail-bubble');
            bubble.style.display = 'none';

            const reserveFullBtn = document.getElementById('btn-reserve-full');
            const hint = document.getElementById('seat-select-hint');

            // Search Match Banner in Modal
            const matchBanner = document.getElementById('modal-search-match-banner');
            const tableMatches = window.getCurrentSearchMatchesForTable ? window.getCurrentSearchMatchesForTable(category, side, tableNumber) : [];

            if (tableMatches.length > 0) {
                const firstMatch = tableMatches[0];
                const res = firstMatch.reservation;
                if (res) {
                    const seatPart = res.is_full_table ? 'Full Table (8 Seats)' : (res.seat_number ? `Seat ${res.seat_number}` : '');
                    matchBanner.innerHTML = `🔍 <strong>Search Match:</strong> ${escapeModalHtml(res.name)} ${seatPart ? '• ' + seatPart : ''} • Order: <span style="color:#fff; font-family:monospace;">${escapeModalHtml(res.ticket_number)}</span>`;
                } else {
                    matchBanner.innerHTML = `🔍 <strong>Search Match:</strong> Table ${side.toUpperCase().substring(0,1)}${tableNumber}`;
                }
                matchBanner.style.display = 'block';
            } else {
                if (matchBanner) matchBanner.style.display = 'none';
            }

            if (isFullTableBooked) {
                const fullBooking = bookings.find(r => r.is_full_table);
                reserveFullBtn.style.display = 'none';
                hint.style.display = 'none';

                for (let s = 1; s <= 8; s++) {
                    const seatEl = document.getElementById(`seat-${s}`);
                    seatEl.className = 'seat-circle reserved';
                    if (tableMatches.length > 0) {
                        seatEl.classList.add('search-matched-seat');
                    }
                    seatEl.onclick = () => showBubble(fullBooking);
                }
            } else {
                reserveFullBtn.style.display = bookings.length === 0 ? 'block' : 'none';
                hint.style.display = 'block';

                for (let s = 1; s <= 8; s++) {
                    const seatEl = document.getElementById(`seat-${s}`);
                    const seatBooking = bookings.find(r => parseInt(r.seat_number, 10) === s);

                    if (seatBooking) {
                        seatEl.className = 'seat-circle reserved';
                        const isMatchedSeat = tableMatches.some(m => m.seatNumber === s || (m.reservation && parseInt(m.reservation.seat_number, 10) === s));
                        if (isMatchedSeat) {
                            seatEl.classList.add('search-matched-seat');
                        }
                        seatEl.onclick = () => showBubble(seatBooking);
                    } else {
                        seatEl.className = 'seat-circle available';
                        seatEl.onclick = () => toggleSeatSelection(s);
                    }
                }
            }

            // Automatically open details bubble if search match is on this table
            if (tableMatches.length > 0) {
                const matchedBooking = bookings.find(b => tableMatches.some(m => m.reservation && m.reservation.id === b.id)) || (isFullTableBooked ? bookings.find(r => r.is_full_table) : null);
                if (matchedBooking) {
                    showBubble(matchedBooking);
                }
            }

            updateSelectedSeatsButton();
            openModal('seating-modal');
        }

        window.openSeatingModal = openSeatingModal;
        window.openStandingBooking = openStandingBooking;

        function toggleSeatSelection(seatNumber) {
            const seatEl = document.getElementById(`seat-${seatNumber}`);
            const index = selectedSeats.indexOf(seatNumber);

            if (index === -1) {
                selectedSeats.push(seatNumber);
                seatEl.classList.add('selected');
            } else {
                selectedSeats.splice(index, 1);
                seatEl.classList.remove('selected');
            }

            updateSelectedSeatsButton();
        }

        function updateSelectedSeatsButton() {
            const btn = document.getElementById('btn-book-selected');
            const count = selectedSeats.length;
            btn.innerText = `Book Selected Seats (${count})`;
            btn.style.display = count > 0 ? 'block' : 'none';
        }

        function showBubble(booking) {
            activeBooking = booking;
            const bubble = document.getElementById('seat-detail-bubble');
            document.getElementById('bubble-name').innerText = booking.name;
            document.getElementById('bubble-email').innerText = booking.email;
            document.getElementById('bubble-ticket').innerText = booking.ticket_number;
            bubble.style.display = 'block';
        }

        function openMultiSeatBooking() {
            if (selectedSeats.length === 0) return;
            closeModal('seating-modal');

            const seatsSorted = [...selectedSeats].sort((a, b) => a - b);
            const label = activeSide.toUpperCase().substring(0,1) + activeTable;
            const seatWord = seatsSorted.length === 1 ? 'Seat' : 'Seats';

            document.getElementById('booking-title').innerText = seatsSorted.length === 1 ? 'Seat Booking' : 'Multi-Seat Booking';
            document.getElementById('booking-summary-text').innerText = `Section: ${activeCategory.toUpperCase()} | Table: ${label} | ${seatWord}: ${seatsSorted.join(', ')}`;

            document.getElementById('form-category').value = activeCategory;
            document.getElementById('form-side').value = activeSide;
            document.getElementById('form-table-number').value = activeTable;
            document.getElementById('form-seat-number').value = '';
            document.getElementById('form-seat-numbers').value = JSON.stringify(seatsSorted);
            document.getElementById('form-is-full-table').value = "false";

            document.getElementById('ticket-count-group').style.display = 'none';

            clearFormFields();
            openModal('booking-form-modal');
        }

        function initiateFullTableBooking() {
            closeModal('seating-modal');

            const label = activeSide.toUpperCase().substring(0,1) + activeTable;
            document.getElementById('booking-title').innerText = 'Full Table Booking';
            document.getElementById('booking-summary-text').innerText = `Section: ${activeCategory.toUpperCase()} | Table: ${label} (All 8 Seats)`;
            
            document.getElementById('form-category').value = activeCategory;
            document.getElementById('form-side').value = activeSide;
            document.getElementById('form-table-number').value = activeTable;
            document.getElementById('form-seat-number').value = '';
            document.getElementById('form-seat-numbers').value = '';
            document.getElementById('form-is-full-table').value = "true";

            document.getElementById('ticket-count-group').style.display = 'none';

            clearFormFields();
            openModal('booking-form-modal');
        }

        function openStandingBooking(side) {
            document.getElementById('booking-title').innerText = 'Standing Area Booking';
            document.getElementById('booking-summary-text').innerText = `Section: STANDING | Area: ${side.toUpperCase()} Standing`;
            
            document.getElementById('form-category').value = 'standing';
            document.getElementById('form-side').value = side;
            document.getElementById('form-table-number').value = '';
            document.getElementById('form-seat-number').value = '';
            document.getElementById('form-seat-numbers').value = '';
            document.getElementById('form-is-full-table').value = "false";

            document.getElementById('ticket-count-group').style.display = 'block';
            document.getElementById('form-ticket-count').value = '1';

            clearFormFields();
            openModal('booking-form-modal');
        }

        function clearFormFields() {
            document.getElementById('form-name').value = '';
            document.getElementById('form-email').value = '';
            document.getElementById('form-ticket').value = '';
        }

        async function submitBooking(event) {
            event.preventDefault();

            const category = document.getElementById('form-category').value;
            const seatNumbersRaw = document.getElementById('form-seat-numbers').value;
            const payload = {
                category: category,
                side: document.getElementById('form-side').value,
                table_number: document.getElementById('form-table-number').value,
                seat_number: document.getElementById('form-seat-number').value,
                seat_numbers: seatNumbersRaw ? JSON.parse(seatNumbersRaw) : [],
                is_full_table: document.getElementById('form-is-full-table').value,
                name: document.getElementById('form-name').value,
                email: document.getElementById('form-email').value,
                ticket_number: document.getElementById('form-ticket').value,
                ticket_count: category === 'standing' ? parseInt(document.getElementById('form-ticket-count').value || 1, 10) : 1
            };

            showLoader('Creating Kalabara reservation...');

            try {
                const response = await fetch('/api/bookings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    closeModal('booking-form-modal');
                    
                    document.getElementById('success-guest-name').innerText = data.reservation.name;
                    document.getElementById('success-category').innerText = data.reservation.category.toUpperCase();
                    
                    const reservations = data.reservations || [data.reservation];
                    
                    if (reservations.length > 1) {
                        const firstTicket = reservations[0].ticket_number;
                        const lastTicket = reservations[reservations.length - 1].ticket_number;
                        document.getElementById('success-ticket').innerText = `${firstTicket} to ${lastTicket} (${reservations.length} Tickets)`;
                    } else {
                        document.getElementById('success-ticket').innerText = data.reservation.ticket_number;
                    }
                    
                    let detailsText = '';
                    if (data.reservation.category === 'standing') {
                        detailsText = `${data.reservation.side.toUpperCase()} Standing` + (reservations.length > 1 ? ` (${reservations.length} Tickets)` : '');
                    } else if (data.reservation.is_full_table) {
                        detailsText = `Table ${data.reservation.side.toUpperCase().substring(0,1)}${data.reservation.table_number} (Full Table)`;
                    } else {
                        const tableLabel = `${data.reservation.side.toUpperCase().substring(0,1)}${data.reservation.table_number}`;
                        const seatNums = reservations.map(r => r.seat_number).sort((a, b) => a - b);
                        const seatWord = seatNums.length === 1 ? 'Seat' : 'Seats';
                        detailsText = `Table ${tableLabel} (${seatWord} ${seatNums.join(', ')})`;
                    }
                    document.getElementById('success-details').innerText = detailsText;
                    
                    const primaryTicket = data.reservation.ticket_number;
                    const customerUrl = `${window.location.origin}/seat/${encodeURIComponent(primaryTicket)}`;
                    const urlInput = document.getElementById('success-customer-url');
                    if (urlInput) urlInput.value = customerUrl;
                    
                    openModal('success-modal');
                    
                    await fetchBookingsAndRender();
                    reloadRecentBookingsTable();
                } else {
                    showToast("Booking failed: " + (data.message || "Unknown error"), 'error');
                }
            } catch (err) {
                console.error("Booking post error:", err);
                showToast("An error occurred during submission.", 'error');
            } finally {
                hideLoader();
            }
        }

        async function reloadRecentBookingsTable() {
            try {
                const response = await fetch('/api/bookings');
                const data = await response.json();
                const container = document.getElementById('recent-bookings-list');
                if (!container) return;

                if (!data.reservations || data.reservations.length === 0) {
                    container.innerHTML = '<tr><td colspan="9" style="text-align: center; color: var(--text-muted);">No reservations found.</td></tr>';
                    return;
                }

                const sorted = data.reservations.sort((a,b) => b.id - a.id).slice(0, 10);
                
                container.innerHTML = '';
                sorted.forEach(b => {
                    const row = document.createElement('tr');
                    const date = b.created_at ? new Date(b.created_at).toISOString().replace('T', ' ').substring(0, 16) : 'N/A';
                    const tableLabel = b.table_number ? (b.side.toUpperCase().substring(0,1) + b.table_number) : 'N/A';
                    const seatLabel = b.is_full_table ? 'Full Table' : (b.seat_number ? 'Seat ' + b.seat_number : 'N/A');

                    row.innerHTML = `
                        <td>${date}</td>
                        <td><span class="category-badge badge-${b.category}">${b.category}</span></td>
                        <td>${b.side ? b.side.charAt(0).toUpperCase() + b.side.slice(1) : 'Center'}</td>
                        <td>${tableLabel}</td>
                        <td>${seatLabel}</td>
                        <td>${b.name}</td>
                        <td>${b.email}</td>
                        <td>${b.ticket_number}</td>
                        <td>
                            <button onclick="copyCustomerSeatUrl('${encodeURIComponent(b.ticket_number)}')" class="btn-primary" style="padding: 4px 8px; font-size: 0.75rem; background: var(--gold-gradient); color: #110e1c; font-weight: 700; margin-right: 6px;" title="Copy Customer Anonymous Seat Locator Link">🔗 Copy Link</button>
                            <button onclick="confirmDeleteBooking('${b.id}')" class="logout-btn" style="padding: 4px 10px; font-size: 0.8rem;">Delete</button>
                        </td>
                    `;
                    container.appendChild(row);
                });
            } catch (err) {
                console.error("Error reloading recent bookings:", err);
            }
        }

        async function deleteActiveSeatBooking() {
            if (!activeBooking || !activeBooking.id) return;
            if (confirm(`Are you sure you want to cancel the reservation for ${activeBooking.name} (Ticket: ${activeBooking.ticket_number})?`)) {
                await executeDelete(activeBooking.id);
                closeModal('seating-modal');
            }
        }

        async function confirmDeleteBooking(id) {
            const booking = reservationsList.find(r => r.id == id);
            if (!booking) return;
            if (confirm(`Are you sure you want to cancel the reservation for ${booking.name} (Ticket: ${booking.ticket_number})?`)) {
                await executeDelete(id);
            }
        }

        async function executeDelete(id) {
            showLoader('Cancelling reservation...');
            try {
                const response = await fetch(`/api/bookings/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    showToast(data.message, 'success');
                    await fetchBookingsAndRender();
                    reloadRecentBookingsTable();
                } else {
                    showToast("Deletion failed: " + (data.message || "Unknown error"), 'error');
                }
            } catch (err) {
                console.error("Delete error:", err);
                showToast("An error occurred while deleting the booking.", 'error');
            } finally {
                hideLoader();
            }
        }

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function showLoader(message = 'Processing...') {
            document.getElementById('loading-text').innerText = message;
            document.getElementById('loading-overlay').classList.add('active');
        }

        function hideLoader() {
            document.getElementById('loading-overlay').classList.remove('active');
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const card = document.createElement('div');
            card.className = `toast-card toast-${type}`;
            
            const iconChar = type === 'success' ? '✓' : '✗';
            
            card.innerHTML = `
                <div class="toast-icon">${iconChar}</div>
                <div class="toast-message">${message}</div>
            `;
            
            container.appendChild(card);
            
            setTimeout(() => card.classList.add('show'), 10);
            
            setTimeout(() => {
                card.classList.remove('show');
                setTimeout(() => card.remove(), 500);
            }, 4000);
        }

        function copyCustomerSeatUrl(ticketNumber) {
            const cleanTicket = decodeURIComponent(ticketNumber);
            const url = `${window.location.origin}/seat/${encodeURIComponent(cleanTicket)}`;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast("Customer seat link copied to clipboard!", "success");
                }).catch(() => {
                    prompt("Copy customer seat locator link:", url);
                });
            } else {
                prompt("Copy customer seat locator link:", url);
            }
        }
        window.copyCustomerSeatUrl = copyCustomerSeatUrl;

        function copyActiveSeatUrl() {
            if (!activeBooking || !activeBooking.ticket_number) {
                showToast("No active booking selected.", "error");
                return;
            }
            copyCustomerSeatUrl(activeBooking.ticket_number);
        }

        function copySuccessCustomerUrl() {
            const input = document.getElementById('success-customer-url');
            if (!input || !input.value) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(() => {
                    showToast("Customer seat link copied to clipboard!", "success");
                }).catch(() => {
                    prompt("Copy customer seat locator link:", input.value);
                });
            } else {
                prompt("Copy customer seat locator link:", input.value);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderGrids();
            fetchBookingsAndRender();
            reloadRecentBookingsTable();
        });
        window.addEventListener('load', () => {
            renderGrids();
            fetchBookingsAndRender();
        });
    </script>

    <!-- Loader Overlay -->
    <div class="loader-overlay" id="loading-overlay">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loading-text">Processing...</div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Universal Seat & Guest Search (100% Self-Hosted & Lightweight) -->
    <script src="{{ asset('js/seatsearch.js') }}"></script>
</body>
</html>
