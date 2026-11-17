<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KALABARA (කලාබර) • 13×10 Table Map Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07050d;
            --bg-card: rgba(18, 14, 28, 0.82);
            --bg-card-hover: rgba(28, 22, 42, 0.95);
            --gold-primary: #f5cf6d;
            --gold-secondary: #c59b27;
            --gold-gradient: linear-gradient(135deg, #ffe89a 0%, #d4af37 50%, #aa7c11 100%);
            --gold-glow: rgba(212, 175, 55, 0.35);
            --border-gold: rgba(212, 175, 55, 0.28);

            --ruby-red: #e63946;
            --ruby-glow: rgba(230, 57, 70, 0.4);
            --crimson-gradient: linear-gradient(135deg, #ff4d6d 0%, #c9184a 50%, #800f2f 100%);

            --royal-purple: #9d4edd;
            --purple-glow: rgba(157, 78, 221, 0.35);

            --neon-green: #39ff14;
            --green-glow: rgba(57, 255, 20, 0.25);

            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            background-image:
                radial-gradient(circle at 50% 0%, rgba(201, 24, 74, 0.18) 0%, transparent 60%),
                radial-gradient(circle at 10% 90%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(128, 15, 47, 0.12) 0%, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Header Bar (Auto-Hides When Scrolling Down) */
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
            flex-wrap: wrap;
            gap: 14px;
            transition: transform 0.3s ease, opacity 0.25s ease;
        }

        header.header-hidden {
            transform: translateY(-110%);
            opacity: 0;
            pointer-events: none;
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
            flex-wrap: wrap;
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
            gap: 12px;
            flex-wrap: wrap;
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

        .dashboard-container {
            max-width: 1440px;
            margin: 30px auto;
            padding: 0 24px 80px;
            position: relative;
            z-index: 1;
        }

        /* Metrics Row */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
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
        .card-temporary::before { background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%); }
        .card-checkin::before { background: linear-gradient(135deg, #39ff14 0%, #10b981 50%, #00f2fe 100%); }

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
        .card-temporary .metric-suit-icon { color: #f59e0b; text-shadow: 0 0 10px rgba(245, 158, 11, 0.5); }
        .card-checkin .metric-suit-icon { color: var(--neon-green); text-shadow: 0 0 12px rgba(57, 255, 20, 0.5); }

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
        .card-checkin .progress-bar { background: linear-gradient(135deg, #39ff14, #10b981); box-shadow: 0 0 10px rgba(57, 255, 20, 0.45); }

        /* Main Arena Booking Card */
        .arena-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            padding: 36px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            margin-bottom: 40px;
            backdrop-filter: blur(16px);
        }

        .arena-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: rgba(14, 11, 23, 0.7);
            border: 1px solid var(--border-gold);
            border-radius: 18px;
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            flex-wrap: wrap;
        }

        .arena-search-box {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-grow: 1;
            max-width: 520px;
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
            box-sizing: border-box;
        }
        .seat-search-input:focus {
            border-color: #00f2fe;
            box-shadow: 0 0 18px rgba(0, 242, 254, 0.35);
            background: rgba(10, 8, 18, 0.95);
        }

        /* Editor Interactive Mode Banner (Move Allocation / Place V1 Booking) */
        .editor-mode-banner {
            position: sticky;
            top: 78px;
            z-index: 90;
            background: #0d1e26;
            border: 2px solid #00f2fe;
            border-radius: 16px;
            padding: 14px 20px;
            margin-bottom: 22px;
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.7);
        }
        .editor-mode-banner.active {
            display: flex;
        }

        /* Base Map (V1) Import Drawer */
        .v1-import-drawer {
            background: rgba(12, 10, 22, 0.95);
            border: 1.5px solid var(--gold-primary);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 26px;
            display: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.75), 0 0 25px var(--gold-glow);
        }
        .v1-import-drawer.active {
            display: block;
        }
        .v1-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 12px;
            max-height: 380px;
            overflow-y: auto;
            padding-right: 6px;
            margin-top: 14px;
            contain: content;
        }
        .v1-booking-card {
            background: rgba(18, 14, 28, 0.85);
            border: 1px solid rgba(212, 175, 55, 0.28);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            contain: layout style;
        }
        .v1-booking-card:hover {
            border-color: var(--gold-primary);
            background: rgba(28, 22, 42, 0.95);
        }
        .v1-booking-card.already-placed {
            border-color: rgba(57, 255, 20, 0.35);
            background: rgba(16, 185, 129, 0.08);
        }

        .stage-container {
            text-align: center;
            margin-bottom: 28px;
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

        .arena-legend {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 18px;
            margin: 0 auto 24px;
            padding: 12px 20px;
            background: rgba(14, 11, 23, 0.65);
            border: 1px solid rgba(212, 175, 55, 0.25);
            border-radius: 14px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .arena-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            display: inline-block;
        }

        /* 13×10 Table Map Wrapper */
        .v2-map-wrapper {
            max-width: 920px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .category-section {
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 18px;
            padding: 22px 24px;
            background: rgba(10, 8, 18, 0.5);
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .category-section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.92rem;
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

        .v2-col-headers, .v2-grid-row {
            display: grid;
            grid-template-columns: 44px repeat(10, 1fr);
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }

        .v2-axis-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.14);
            border: 1.5px solid var(--gold-primary);
            color: var(--gold-primary);
            font-family: 'Cinzel', serif;
            font-size: 0.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 0 10px var(--gold-glow);
            user-select: none;
        }

        /* Exact V1 `.table-btn` States */
        .table-btn {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
        }

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

        .table-btn.temporary-reserved {
            border-color: rgba(245, 158, 11, 0.75);
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border-style: dashed;
            box-shadow: inset 0 0 8px rgba(245, 158, 11, 0.25);
        }
        .table-btn.temporary-reserved:hover {
            background: rgba(245, 158, 11, 0.3);
            border-color: #f59e0b;
            box-shadow: 0 0 14px rgba(245, 158, 11, 0.55);
            transform: scale(1.05);
        }

        .table-btn.has-temporary::after {
            content: '⏳';
            position: absolute;
            top: -4px;
            right: -4px;
            font-size: 0.65rem;
        }

        .table-btn.has-checked-in {
            box-shadow: inset 0 0 0 1px rgba(57, 255, 20, 0.65), 0 0 8px rgba(57, 255, 20, 0.3);
        }

        .table-btn.search-matched-table {
            border-color: #00f2fe !important;
            background: rgba(0, 242, 254, 0.28) !important;
            color: #ffffff !important;
            box-shadow: 0 0 20px #00f2fe, inset 0 0 12px #00f2fe !important;
            transform: scale(1.16) !important;
            z-index: 15;
        }

        /* Bottom Section: Standing Area Left | FOH + Entrance | Standing Area Right */
        .v2-bottom-layout {
            display: grid;
            grid-template-columns: 1fr 240px 1fr;
            gap: 20px;
            align-items: stretch;
        }

        .standing-box {
            background: rgba(157, 78, 221, 0.06);
            border: 1px dashed var(--royal-purple);
            border-radius: 18px;
            padding: 24px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
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

        .foh-entrance-card {
            border: 1px solid var(--border-gold);
            border-radius: 18px;
            background: rgba(10, 8, 18, 0.7);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .foh-block {
            padding: 16px 12px;
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: var(--gold-primary);
            border-bottom: 1px solid var(--border-gold);
            background: rgba(212, 175, 55, 0.06);
        }

        .entrance-block {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 14px 10px;
            color: var(--text-main);
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.15em;
        }

        /* V1 Modals & 8-Seat Table Ring */
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
            padding: 20px 16px;
            box-sizing: border-box;
            overflow-y: auto;
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
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            overscroll-behavior: contain;
            box-sizing: border-box;
            padding: 24px 28px;
            margin: auto;
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
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.15);
            padding-bottom: 12px;
            position: sticky;
            top: -24px;
            background: #110e1c;
            z-index: 25;
            padding-top: 4px;
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
            line-height: 1;
        }
        .close-modal:hover {
            color: var(--ruby-red);
        }

        /* 8 Seats Table Ring (Identical to V1) */
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

        .seat-circle.temporary {
            border: 2px dashed #f59e0b;
            background: rgba(245, 158, 11, 0.22);
            color: #fbbf24;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.35);
        }

        .seat-circle.checked-in {
            border: 2px solid var(--neon-green);
            background: rgba(16, 185, 129, 0.32);
            color: #ffffff;
            box-shadow: 0 0 14px rgba(57, 255, 20, 0.55);
            font-size: 0.72rem;
            line-height: 1.05;
            flex-direction: column;
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

        .btn-full-table {
            background: var(--crimson-gradient) !important;
            box-shadow: 0 4px 15px var(--ruby-glow) !important;
            color: #ffffff !important;
            margin-bottom: 14px;
        }

        .btn-temp-action {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35) !important;
            font-weight: 700 !important;
            border: none !important;
            margin-bottom: 12px;
        }

        .temporary-allocation-banner {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.45);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 16px;
            text-align: left;
            font-size: 0.88rem;
            line-height: 1.5;
        }

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

        .badge-status-temp {
            background: rgba(245, 158, 11, 0.16);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.4);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-status-perm {
            background: rgba(57, 255, 20, 0.12);
            color: #8ade80;
            border: 1px solid rgba(57, 255, 20, 0.35);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-status-checkin {
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            border: 1px solid rgba(57, 255, 20, 0.5);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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
            color: var(--gold-primary);
        }
        .recent-table-container { overflow-x: auto; }
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

        .loader-overlay {
            position: fixed;
            inset: 0;
            background: rgba(7, 5, 13, 0.85);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .loader-overlay.active { opacity: 1; pointer-events: auto; }
        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(212, 175, 55, 0.2);
            border-top-color: var(--gold-primary);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin-bottom: 14px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 2500;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast-card {
            background: #161224;
            border: 1px solid var(--border-gold);
            border-radius: 12px;
            padding: 12px 18px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
            opacity: 0;
            transform: translateY(15px);
            transition: all 0.3s ease;
        }
        .toast-card.show { opacity: 1; transform: translateY(0); }
        .toast-success { border-left: 4px solid var(--neon-green); }
        .toast-error { border-left: 4px solid var(--ruby-red); }

        .v2-grid-scroll-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 4px;
        }

        /* Map Zoom Viewport & Controls (Responsive & True Proportional Scaling) */
        .map-zoom-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0 auto 16px;
            padding: 8px 14px;
            background: rgba(14, 11, 23, 0.9);
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            width: 100%;
            max-width: 520px;
            box-sizing: border-box;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45);
            position: sticky;
            top: 10px;
            z-index: 40;
            backdrop-filter: blur(10px);
        }
        .zoom-ctrl-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(212, 175, 55, 0.45);
            color: var(--gold-primary);
            padding: 7px 13px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.18s ease;
            font-family: 'Outfit', sans-serif;
            white-space: nowrap;
            touch-action: manipulation;
            user-select: none;
        }
        .zoom-ctrl-btn:hover, .zoom-ctrl-btn:active {
            background: var(--gold-gradient);
            color: #07050d;
            border-color: var(--gold-primary);
            transform: scale(1.04);
        }
        .zoom-slider {
            width: 110px;
            accent-color: var(--gold-primary);
            cursor: pointer;
        }
        .zoom-level-badge {
            font-size: 0.8rem;
            font-weight: 800;
            color: #00f2fe;
            min-width: 64px;
            text-align: center;
            font-family: monospace;
        }
        .map-zoom-viewport {
            width: 100%;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 18px;
            position: relative;
        }
        .map-zoom-sizer {
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        .map-zoom-canvas {
            width: 880px;
            transform-origin: top left;
            transition: transform 0.15s ease-out;
        }
        .v2-map-wrapper {
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Responsive Layout Adjustments (Full Seat Map Fits on Mobile Screen by Default!) */
        @media (max-width: 1240px) {
            .metrics-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
            }
        }

        @media (max-width: 900px) {
            header {
                padding: 10px 14px;
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
            .header-brand {
                flex-direction: row;
                justify-content: center;
                gap: 10px;
            }
            .brand-title-group h1 {
                font-size: 1.45rem;
            }
            .event-info-pills, .header-actions {
                justify-content: center;
                gap: 8px;
            }
            .dashboard-container {
                padding: 0 10px 60px;
                margin: 14px auto;
            }
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 20px;
            }
            .metric-card {
                padding: 14px 16px;
            }
            .metric-value {
                font-size: 1.65rem;
            }
            .arena-card {
                padding: 14px 8px;
                border-radius: 18px;
            }
            .map-zoom-controls {
                padding: 6px 10px;
                gap: 6px;
                border-radius: 16px;
            }
            .zoom-ctrl-btn {
                padding: 6px 10px;
                font-size: 0.74rem;
            }
            .zoom-slider {
                width: 85px;
            }
            .recent-section {
                padding: 16px 10px;
            }
            table.recent-table th, table.recent-table td {
                padding: 10px 8px;
                font-size: 0.78rem;
            }
        }

        @media (max-width: 576px) {
            .arena-toolbar {
                padding: 10px;
                gap: 8px;
            }
            .arena-search-box {
                width: 100%;
                max-width: 100%;
            }
            .modal-content {
                padding: 18px 14px;
                width: 95%;
            }
            .seats-container {
                transform: scale(0.85);
                margin-bottom: 6px;
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
                    <span>13×10 STAGE MAP (TABLES 1 – 130)</span>
                    <span>•</span>
                    <span>8 SEATS PER TABLE</span>
                </div>
            </div>
        </div>

        <div class="event-info-pills">
            <div class="info-pill gold-accent">
                📅 <strong>20 NOV</strong> (6:30 PM)
            </div>
            <div class="info-pill">
                📐 <strong>66 FT × 115 FT</strong>
            </div>
            <div class="info-pill" style="border-color: rgba(0, 242, 254, 0.4); color: #00f2fe; background: rgba(0, 242, 254, 0.08);">
                🎟️ <strong><span id="header-total-sold">{{ $stats['total']['sold'] }}</span> TOTAL SOLD</strong>
            </div>
        </div>

        <div class="header-actions">
            <a href="{{ route('dashboard') }}" class="btn-primary" style="width: auto; margin: 0; padding: 8px 14px; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: var(--gold-gradient); color: #07050d; font-weight: 800; border-radius: 20px;">
                ↩️ Base Tables Map (V1)
            </a>
            <a href="{{ route('v2.scanner') }}" class="btn-primary" style="width: auto; margin: 0; padding: 8px 16px; font-size: 0.82rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #39ff14 0%, #10b981 100%); color: #07050d; font-weight: 800; box-shadow: 0 0 18px rgba(57, 255, 20, 0.4); border-radius: 20px;">
                📷 QR Check-In Scanner
            </a>
            @if (!empty($isEditor))
                <span class="admin-badge" style="border-color: #00f2fe; color: #00f2fe; background: rgba(0, 242, 254, 0.12); box-shadow: 0 0 12px rgba(0, 242, 254, 0.35);">✦ EDITOR ACCESS</span>
            @else
                <span class="admin-badge">♠ ADMIN CONTROL</span>
            @endif
            <form action="/logout" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="logout-btn">Log Out</button>
            </form>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Metrics Row -->
        <div class="metrics-grid">
            <div class="metric-card card-total">
                <div class="metric-header">
                    <div class="metric-title">PERMANENT TICKETS SOLD</div>
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

            <div class="metric-card card-checkin" style="cursor: pointer;" onclick="window.location.href='{{ route('v2.scanner') }}'" title="Click to open V2 QR Check-In Scanner">
                <div class="metric-header">
                    <div class="metric-title" style="color: var(--neon-green);">CHECKED-IN / COUNTDOWN</div>
                    <div class="metric-suit-icon">🔒</div>
                </div>
                <div class="metric-value" style="color: var(--neon-green);">
                    <span id="stats-checkin-sold">{{ $stats['checkin']['checked_in'] ?? 0 }}</span>
                    <span style="font-size: 0.95rem; font-family: 'Outfit', sans-serif; font-weight: 700; color: #00f2fe; margin-left: 4px;">
                        (<span id="stats-checkin-remaining">{{ $stats['checkin']['remaining'] ?? 0 }}</span> Left)
                    </span>
                </div>
                <div class="metric-sub" id="stats-checkin-sub">
                    <span style="color: var(--gold-primary);">♠ {{ $stats['checkin']['platinum'] ?? 0 }}</span> &bull;
                    <span style="color: #ff758f;">♥ {{ $stats['checkin']['gold'] ?? 0 }}</span> &bull;
                    <span style="color: #c77dff;">♣ {{ $stats['checkin']['standing'] ?? 0 }}</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="stats-checkin-progress" style="width: {{ $stats['checkin']['percentage'] ?? 0 }}%"></div>
                </div>
            </div>

            <div class="metric-card card-temporary">
                <div class="metric-header">
                    <div class="metric-title" style="color: #fbbf24;">TEMPORARY HOLD</div>
                    <div class="metric-suit-icon">⏳</div>
                </div>
                <div class="metric-value" id="stats-temporary-sold" style="color: #fbbf24;">{{ $stats['total']['temporary'] ?? 0 }}</div>
                <div class="metric-sub" id="stats-temporary-sub">
                    <span style="color: var(--gold-primary);">♠ {{ $stats['platinum']['temporary'] ?? 0 }}</span> &bull;
                    <span style="color: #ff758f;">♥ {{ $stats['gold']['temporary'] ?? 0 }}</span> &bull;
                    <span style="color: #c77dff;">♣ {{ $stats['standing']['temporary'] ?? 0 }}</span>
                </div>
                <div style="margin-top: 14px; font-size: 0.75rem; color: #fbbf24; font-weight: 600;">
                    ⚠️ Not Added to Excel Sheet
                </div>
            </div>

            <div class="metric-card card-platinum">
                <div class="metric-header">
                    <div class="metric-title">VIP (TABLES 1–50 • 5 ROWS)</div>
                    <div class="metric-suit-icon">♠</div>
                </div>
                <div class="metric-value" id="stats-platinum-sold">{{ $stats['platinum']['sold'] }} <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);">/ {{ $stats['platinum']['capacity'] }}</span></div>
                <div class="metric-sub"><span id="stats-platinum-pct">{{ $stats['platinum']['percentage'] }}</span>% Sold &bull; <span style="color: #fbbf24;"><span id="stats-platinum-temp">{{ $stats['platinum']['temporary'] ?? 0 }}</span> Temp</span></div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="stats-platinum-progress" style="width: {{ $stats['platinum']['percentage'] }}%"></div>
                </div>
            </div>

            <div class="metric-card card-gold">
                <div class="metric-header">
                    <div class="metric-title">GOLD (TABLES 51–130 • 8 ROWS)</div>
                    <div class="metric-suit-icon">♥</div>
                </div>
                <div class="metric-value" id="stats-gold-sold">{{ $stats['gold']['sold'] }} <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);">/ {{ $stats['gold']['capacity'] }}</span></div>
                <div class="metric-sub"><span id="stats-gold-pct">{{ $stats['gold']['percentage'] }}</span>% Sold &bull; <span style="color: #fbbf24;"><span id="stats-gold-temp">{{ $stats['gold']['temporary'] ?? 0 }}</span> Temp</span></div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="stats-gold-progress" style="width: {{ $stats['gold']['percentage'] }}%"></div>
                </div>
            </div>

            <div class="metric-card card-standing">
                <div class="metric-header">
                    <div class="metric-title">STANDING ARENA</div>
                    <div class="metric-suit-icon">♣</div>
                </div>
                <div class="metric-value" id="stats-standing-sold">{{ $stats['standing']['sold'] }}</div>
                <div class="metric-sub">Confirmed &bull; <span style="color: #fbbf24;"><span id="stats-standing-temp">{{ $stats['standing']['temporary'] ?? 0 }}</span> Temp</span></div>
            </div>
        </div>

        <!-- Main Arena Booking Card -->
        <div class="arena-card">
            <div class="arena-toolbar">
                <form class="arena-search-box" onsubmit="event.preventDefault(); executeV2Search();" style="max-width: 640px;">
                    <div class="search-input-group">
                        <span class="search-glass-icon">🔍</span>
                        <input type="text" id="v2-search-input" class="seat-search-input"
                               placeholder="Enter guest name, email, order no, or table (1 - 130)..."
                               autocomplete="off" />
                    </div>
                    <button type="submit" class="btn-primary" style="width: auto; padding: 10px 18px; font-size: 0.84rem; border-radius: 20px; white-space: nowrap;">
                        🔍 Search
                    </button>
                    <button type="button" id="v2-search-clear-btn" class="logout-btn" style="display: none; padding: 9px 14px; font-size: 0.8rem; border-radius: 20px; white-space: nowrap;" onclick="clearV2Search()">
                        ✕ Clear
                    </button>
                    <span id="v2-search-match-badge" style="display: none; font-size: 0.8rem; font-weight: 800; color: #00f2fe; white-space: nowrap;"></span>
                </form>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="btn-primary" style="width: auto; padding: 10px 18px; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 8px; border-radius: 20px;" onclick="toggleV1ImportDrawer()">
                        📥 Add from Base Map (V1) Bookings
                        <span id="v1-unassigned-badge" style="background: #07050d; color: var(--gold-primary); padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;">0</span>
                    </button>
                </div>
            </div>

            <!-- Interactive Editor Mode Banner (Shown when Moving a V2 Seat/Table or Placing a V1 Booking) -->
            <div class="editor-mode-banner" id="editor-mode-banner">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                    <span style="font-size: 1.4rem;" id="editor-mode-icon">⇄</span>
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 800; color: #00f2fe; text-transform: uppercase; letter-spacing: 0.08em;" id="editor-mode-title">
                            EDITOR SEAT ALLOCATION MOVE MODE
                        </div>
                        <div style="font-size: 0.95rem; font-weight: 700; color: #fff;" id="editor-mode-desc">
                            Click any Table (1–130) on the map below to move the guest here.
                        </div>
                    </div>
                </div>
                <button type="button" class="logout-btn" style="background: rgba(230, 57, 70, 0.25); border-color: var(--ruby-red);" onclick="cancelEditorMode()">
                    ✕ Cancel Mode
                </button>
            </div>

            <!-- Collapsible Base Map (V1) Bookings Import Drawer -->
            <div class="v1-import-drawer" id="v1-import-drawer">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(212, 175, 55, 0.2);">
                    <div>
                        <h3 style="margin: 0; font-family: 'Cinzel', serif; color: var(--gold-primary); font-size: 1.1rem;">
                            📥 Assign Base Map (V1) Bookings to New 13×10 Seat Map
                        </h3>
                        <p style="margin: 4px 0 0; font-size: 0.8rem; color: var(--text-muted);">
                            Type a target Table number (1–130) and click <strong>Assign</strong>, or click <strong>📌 Pick on Map</strong> and click any table below.
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <input type="text" id="v1-filter-input" class="seat-search-input" style="width: 200px; padding: 8px 14px;" placeholder="Filter V1 name, order, L12..." oninput="debouncedRenderV1()" onkeydown="if(event.key==='Enter'){event.preventDefault();renderV1ImportList();}">
                        <select id="v1-sort-order" class="seat-search-input" style="width: auto; padding: 8px 12px; font-size: 0.78rem;" onchange="renderV1ImportList()">
                            <option value="oldest" selected>⏳ Oldest First (Top)</option>
                            <option value="newest">⚡ Newest First</option>
                        </select>
                        <button type="button" class="btn-primary" style="width: auto; padding: 8px 14px; font-size: 0.78rem; border-radius: 18px;" onclick="renderV1ImportList()">🔍 Search</button>
                        <label style="font-size: 0.8rem; color: #cbd5e1; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" id="v1-show-placed" onchange="renderV1ImportList()">
                            Show already placed on V2
                        </label>
                        <button type="button" class="logout-btn" style="padding: 6px 12px;" onclick="toggleV1ImportDrawer()">Close</button>
                    </div>
                </div>

                <div class="v1-cards-grid" id="v1-cards-grid">
                    <div style="color: var(--text-muted); font-size: 0.85rem;">Loading Base Map bookings...</div>
                </div>
            </div>

            <!-- Map Zoom Controls (Fit Full Screen on Mobile or Zoom In/Out Smoothly) -->
            <div class="map-zoom-controls">
                <button type="button" class="zoom-ctrl-btn" onclick="stepMapZoom(-0.15)">➖ Zoom Out</button>
                <input type="range" id="map-zoom-slider" class="zoom-slider" min="35" max="200" value="100" oninput="setMapZoomFromSlider(this.value)">
                <span class="zoom-level-badge" id="map-zoom-label">Fit</span>
                <button type="button" class="zoom-ctrl-btn" onclick="fitMapToViewport()">📐 Fit</button>
                <button type="button" class="zoom-ctrl-btn" onclick="stepMapZoom(0.15)">➕ Zoom In</button>
            </div>

            <div class="map-zoom-viewport" id="v2-zoom-viewport">
                <div class="map-zoom-sizer" id="v2-zoom-sizer">
                    <div class="map-zoom-canvas" id="v2-zoom-canvas">
                    <div class="stage-container">
                        <div class="stage-banner">
                            <div class="stage-title">♠ STAGE (66 FT) ♠</div>
                            <div class="stage-subtitle">KALABARA LIVE • WATERS EDGE</div>
                        </div>
                    </div>

                    <!-- Color Legend (Identical to V1) -->
                    <div class="arena-legend">
                        <div class="arena-legend-item">
                            <span class="legend-dot" style="background: rgba(57, 255, 20, 0.15); border: 1px solid var(--neon-green);"></span>
                            <span>Available</span>
                        </div>
                        <div class="arena-legend-item">
                            <span class="legend-dot" style="background: rgba(245, 207, 109, 0.25); border: 1px solid var(--gold-primary);"></span>
                            <span>Partially Reserved</span>
                        </div>
                        <div class="arena-legend-item">
                            <span class="legend-dot" style="background: rgba(230, 57, 70, 0.3); border: 1px solid var(--ruby-red);"></span>
                            <span>Fully Reserved (Permanent - Excel Synced)</span>
                        </div>
                        <div class="arena-legend-item">
                            <span class="legend-dot" style="background: rgba(245, 158, 11, 0.25); border: 1px dashed #f59e0b;"></span>
                            <span style="color: #fbbf24; font-weight: 700;">⏳ Temporary Hold (Name Only - Excluded from Excel)</span>
                        </div>
                        <div class="arena-legend-item">
                            <span class="legend-dot" style="background: rgba(16, 185, 129, 0.35); border: 1px solid var(--neon-green);"></span>
                            <span style="color: var(--neon-green); font-weight: 700;">🔒 Checked-In Seat (Seated Users Only)</span>
                        </div>
                    </div>

                    <div class="v2-map-wrapper">
                        <!-- VIP Section: Rows 1 - 5 (Tables 1 - 50, 8 Seats per Table) -->
                        <div class="category-section">
                            <div class="category-section-title platinum-section-title">
                                <span>♠</span> VIP (Tables 1 - 50 • Rows 1 - 5)
                            </div>
                            <div class="v2-grid-scroll-wrap">
                                <div class="v2-col-headers">
                                    <div></div>
                                    @for ($c = 1; $c <= 10; $c++)
                                        <div class="v2-axis-badge">{{ $c }}</div>
                                    @endfor
                                </div>
                                <div id="platinum-v2-rows"></div>
                            </div>
                        </div>

                        <!-- Gold Section: Rows 6 - 13 (Tables 51 - 130, 8 Seats per Table) -->
                        <div class="category-section">
                            <div class="category-section-title gold-section-title">
                                <span>♥</span> GOLD (Tables 51 - 130 • Rows 6 - 13)
                            </div>
                            <div class="v2-grid-scroll-wrap">
                                <div class="v2-col-headers">
                                    <div></div>
                                    @for ($c = 1; $c <= 10; $c++)
                                        <div class="v2-axis-badge">{{ $c }}</div>
                                    @endfor
                                </div>
                                <div id="gold-v2-rows"></div>
                            </div>
                        </div>

                        <!-- Bottom Section: Left Standing | FOH + Entrance | Right Standing -->
                        <div class="v2-bottom-layout">
                            <div class="standing-box" onclick="openStandingBooking('left')">
                                <div class="suit-icon">♣</div>
                                <h3>Left Standing Area</h3>
                                <p id="left-standing-count">Click to reserve</p>
                            </div>

                            <div class="foh-entrance-card">
                                <div class="foh-block">FOH</div>
                                <div class="entrance-block">
                                    <span style="color: var(--gold-primary); font-size: 1.4rem; line-height: 1; margin-bottom: 4px;">⬆</span>
                                    <span>ENTRANCE</span>
                                </div>
                            </div>

                            <div class="standing-box" onclick="openStandingBooking('right')">
                                <div class="suit-icon">♥</div>
                                <h3>Right Standing Area</h3>
                                <p id="right-standing-count">Click to reserve</p>
                            </div>
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
                            <th>Status</th>
                            <th>Table</th>
                            <th>Seat / Option</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Ticket No</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="recent-bookings-list"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 8-Seat Table Ring Modal (Identical to V1 `#seating-modal`) -->
    <div class="modal-overlay" id="seating-modal">
        <div class="modal-content" style="max-width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-table-title">Table Detail</h3>
                <button class="close-modal" onclick="closeModal('seating-modal')">&times;</button>
            </div>

            <!-- Editor Transfer / Placement Action Box inside Modal (When Moving a Seat or Placing a V1 Booking) -->
            <div id="modal-editor-transfer-box" style="display: none; background: linear-gradient(135deg, rgba(0, 242, 254, 0.14), rgba(16, 185, 129, 0.16)); border: 1.5px solid #00f2fe; border-radius: 12px; padding: 12px 14px; margin-bottom: 14px;">
                <div style="font-size: 0.76rem; font-weight: 800; color: #00f2fe; text-transform: uppercase; letter-spacing: 0.06em;" id="modal-transfer-title">
                    ⇄ EDITOR SEAT REALLOCATION
                </div>
                <div style="font-size: 0.88rem; color: #fff; margin: 4px 0 10px;" id="modal-transfer-desc"></div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn-primary" id="btn-confirm-transfer-here" style="background: linear-gradient(135deg, #00f2fe, #10b981); color: #07050d; font-weight: 900; padding: 10px 14px; font-size: 0.85rem; width: auto; flex: 1;" onclick="executeEditorTransferToActiveTable()">
                        ✓ Confirm Allocation Here
                    </button>
                    <button type="button" class="logout-btn" style="padding: 8px 12px; font-size: 0.8rem;" onclick="cancelEditorMode()">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Temporary Allocation Banner -->
            <div class="temporary-allocation-banner" id="modal-temporary-banner" style="display: none;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                    <span style="font-weight: 800; color: #fbbf24; text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.05em;">⏳ Temporary Table Allocation</span>
                    <span style="font-size: 0.7rem; background: rgba(245, 158, 11, 0.25); color: #fef3c7; border: 1px solid rgba(245, 158, 11, 0.5); padding: 2px 6px; border-radius: 4px;">Not in Excel</span>
                </div>
                <div id="modal-temporary-names" style="margin-top: 8px; color: #f8fafc; font-size: 0.92rem; line-height: 1.5;"></div>
                <div style="font-size: 0.75rem; color: #cbd5e1; margin-top: 6px;">Click a seat or use the edit option below to add all booking details and make this permanent.</div>
            </div>

            <div class="seats-container">
                <div class="modal-table-center" id="modal-table-center-id">1</div>
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

            <p id="seat-select-hint" style="text-align: center; font-size: 0.82rem; color: var(--text-muted); margin: 0 0 16px 0;">Select available seats to book or temporarily hold.</p>

            <!-- Selected Seats Booking Buttons -->
            <button class="btn-primary btn-book-selected" id="btn-book-selected" onclick="openMultiSeatBooking()">Book Selected Seats (0)</button>
            <button class="btn-primary btn-temp-action" id="btn-temp-hold-selected" style="display: none;" onclick="openTemporaryHoldModal('seats')">⏳ Temporary Hold Selected Seats (0)</button>

            <!-- Full Table Booking Buttons -->
            <button class="btn-primary btn-full-table" id="btn-reserve-full" onclick="initiateFullTableBooking()">Reserve Full Table (Permanent)</button>
            <button class="btn-primary btn-temp-action" id="btn-temp-hold-full" onclick="openTemporaryHoldModal('full')">⏳ Temporary Reserve Full Table (Name Only)</button>

            <!-- Full Table Temporary Actions -->
            <div id="full-table-temp-actions" style="display: none; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                <button class="btn-primary" id="btn-make-table-perm" style="background: var(--gold-gradient); color: #07060c; font-weight: 800;">✨ Edit &amp; Make Full Table Permanent</button>
                <button class="btn-primary" id="btn-release-table-temp" style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fbbf24; font-weight: 700; box-shadow: none;">🗑️ Release Temporary Table Hold</button>
            </div>

            <div class="reservation-detail-pop" id="seat-detail-bubble">
                <div style="margin-bottom: 4px;"><strong>Status:</strong> <span id="bubble-status"></span></div>
                <div id="bubble-checkin-row" style="margin-bottom: 4px; display: none;"><strong>Check-In:</strong> <span id="bubble-checkin"></span></div>
                <strong>Allocated Name:</strong> <span id="bubble-name" style="color: var(--gold-primary); font-weight: 700;"></span><br>
                <div id="bubble-email-row"><strong>Email:</strong> <span id="bubble-email"></span></div>
                <div id="bubble-ticket-row"><strong>Ticket No:</strong> <span id="bubble-ticket" style="color: var(--gold-primary);"></span></div>

                <!-- Editor-Only Quick Reallocate / Move Seat Section (Visible ONLY to editor2026.kalabara@gmail.com) -->
                @if (!empty($isEditor))
                <div id="editor-move-controls-box" style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed rgba(0, 242, 254, 0.35);">
                    <div style="font-size: 0.75rem; font-weight: 800; color: #00f2fe; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px;">
                        ⇄ Editor: Move / Reallocate User Seat
                    </div>
                    <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-bottom: 8px;">
                        <input type="number" id="quick-move-table" min="1" max="130" placeholder="Table (1-130)" style="width: 115px; background: rgba(7,5,13,0.9); border: 1px solid rgba(0,242,254,0.45); border-radius: 8px; padding: 6px 10px; color: #fff; font-size: 0.8rem;">
                        <select id="quick-move-seat" style="background: rgba(7,5,13,0.9); border: 1px solid rgba(0,242,254,0.45); border-radius: 8px; padding: 6px 8px; color: #fff; font-size: 0.8rem;">
                            <option value="">Auto / Keep Seat</option>
                            <option value="1">Seat 1</option>
                            <option value="2">Seat 2</option>
                            <option value="3">Seat 3</option>
                            <option value="4">Seat 4</option>
                            <option value="5">Seat 5</option>
                            <option value="6">Seat 6</option>
                            <option value="7">Seat 7</option>
                            <option value="8">Seat 8</option>
                        </select>
                        <select id="quick-move-scope" style="background: rgba(7,5,13,0.9); border: 1px solid rgba(212,175,55,0.4); border-radius: 8px; padding: 6px 8px; color: var(--gold-primary); font-size: 0.78rem;">
                            <option value="single">This Seat Only</option>
                            <option value="group">All Guest's Seats on Table</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <button type="button" class="btn-primary" style="background: linear-gradient(135deg, #00f2fe, #4facfe); color: #07050d; font-weight: 800; padding: 7px 12px; font-size: 0.8rem; width: auto;" onclick="submitQuickMoveFromBubble()">
                            ⇄ Move to Table Now
                        </button>
                        <button type="button" class="btn-primary" style="background: rgba(0, 242, 254, 0.14); border: 1px solid #00f2fe; color: #00f2fe; font-weight: 800; padding: 7px 12px; font-size: 0.8rem; width: auto; box-shadow: none;" onclick="startMoveOnMapFromBubble()">
                            📌 Click Target Table on Map
                        </button>
                    </div>
                </div>
                @endif

                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
                    <button id="btn-make-seat-perm" class="btn-primary" style="display: none; background: var(--gold-gradient); color: #110e1c; font-weight: 800; padding: 8px 12px; font-size: 0.85rem; width: auto;" onclick="openMakePermanentForActiveSeat()">✨ Add Details &amp; Make Permanent</button>
                    <button id="btn-toggle-seat-checkin" class="btn-primary" style="display: none; background: linear-gradient(135deg, #39ff14, #10b981); color: #07050d; font-weight: 800; padding: 8px 12px; font-size: 0.85rem; width: auto;" onclick="toggleActiveSeatCheckIn()">🔒 Mark as Checked-In</button>
                    <button id="btn-copy-seat-url" class="btn-primary" style="background: var(--gold-gradient); color: #110e1c; font-weight: 800; padding: 8px 12px; font-size: 0.85rem; width: auto;" onclick="copyActiveSeatUrl()">🔗 Copy Customer Link</button>
                    <button id="btn-delete-seat" class="btn-primary" style="background: var(--crimson-gradient); box-shadow: 0 4px 15px var(--ruby-glow); padding: 8px 12px; font-size: 0.85rem; color: #fff; width: auto;" onclick="deleteActiveSeatBooking()">Delete Ticket</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Temporary Table Hold Modal -->
    <div class="modal-overlay" id="temporary-hold-modal">
        <div class="modal-content" style="max-width: 430px; border-color: rgba(245, 158, 11, 0.5);">
            <div class="modal-header">
                <h3 class="modal-title" style="color: #fbbf24;">⏳ Temporary Table Reserve</h3>
                <button class="close-modal" onclick="closeModal('temporary-hold-modal')">&times;</button>
            </div>
            <form onsubmit="submitTemporaryHold(event)">
                <input type="hidden" id="temp-hold-category">
                <input type="hidden" id="temp-hold-table-number">
                <input type="hidden" id="temp-hold-seat-numbers">
                <input type="hidden" id="temp-hold-is-full-table" value="true">

                <div id="temp-hold-summary-text" style="background: rgba(245, 158, 11, 0.1); padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 0.9rem; color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35);"></div>

                <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 10px 14px; margin-bottom: 18px; font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
                    ℹ️ <strong>Temporary Hold:</strong> Reserves the table/seats with just a name. <strong>Not added to Excel sheet</strong> and counted separately until made permanent.
                </div>

                <div class="form-group">
                    <label for="temp-hold-name">Guest / Allocated Name *</label>
                    <input type="text" id="temp-hold-name" required placeholder="Enter guest name (e.g. Mr. Perera)">
                </div>

                <button type="submit" class="btn-primary btn-temp-action" style="margin-bottom: 0;">
                    ⏳ Confirm Temporary Reservation
                </button>
            </form>
        </div>
    </div>

    <!-- Edit & Make Permanent Modal -->
    <div class="modal-overlay" id="make-permanent-modal">
        <div class="modal-content" style="max-width: 440px;">
            <div class="modal-header">
                <h3 class="modal-title" style="color: var(--gold-primary);">✨ Edit &amp; Make Permanent</h3>
                <button class="close-modal" onclick="closeModal('make-permanent-modal')">&times;</button>
            </div>
            <form onsubmit="submitMakePermanent(event)">
                <input type="hidden" id="permanent-res-id">

                <div id="permanent-summary-text" style="background: rgba(212, 175, 55, 0.08); padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 0.9rem; color: var(--gold-primary); border: 1px solid var(--border-gold);"></div>

                <div style="background: rgba(57, 255, 20, 0.07); border: 1px solid rgba(57, 255, 20, 0.3); border-radius: 10px; padding: 10px 14px; margin-bottom: 18px; font-size: 0.8rem; color: #8ade80; line-height: 1.5;">
                    ✓ Completing all booking details below will convert this temporary hold into a <strong>Permanent Booking</strong> and automatically save it to the <strong>Excel Sheet</strong>.
                </div>

                <div class="form-group">
                    <label for="permanent-name">Guest Full Name *</label>
                    <input type="text" id="permanent-name" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label for="permanent-email">Email Address *</label>
                    <input type="email" id="permanent-email" required placeholder="john@example.com">
                </div>
                <div class="form-group">
                    <label for="permanent-ticket">Ticket Number *</label>
                    <input type="text" id="permanent-ticket" required placeholder="KAL-1001">
                </div>

                <button type="submit" class="btn-primary">
                    ✓ Confirm Permanent &amp; Save to Excel
                </button>
            </form>
        </div>
    </div>

    <!-- Booking Input Form Modal -->
    <div class="modal-overlay" id="booking-form-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="booking-title">Create Reservation</h3>
                <button class="close-modal" onclick="closeModal('booking-form-modal')">&times;</button>
            </div>
            <form onsubmit="submitBooking(event)">
                <input type="hidden" id="form-category">
                <input type="hidden" id="form-side">
                <input type="hidden" id="form-table-number">
                <input type="hidden" id="form-seat-numbers">
                <input type="hidden" id="form-is-full-table" value="false">

                <div id="booking-summary-text" style="background: rgba(212, 175, 55, 0.06); padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; color: var(--gold-primary); border: 1px solid var(--border-gold);"></div>

                <div class="form-group">
                    <label for="form-name">Guest Full Name</label>
                    <input type="text" id="form-name" required placeholder="John Doe">
                </div>
                <div class="form-group" id="form-email-group">
                    <label for="form-email">Email Address</label>
                    <input type="email" id="form-email" required placeholder="john@example.com">
                </div>
                <div class="form-group" id="form-ticket-group">
                    <label for="form-ticket">Ticket Number</label>
                    <input type="text" id="form-ticket" required placeholder="KAL-1001">
                </div>
                <div class="form-group" id="ticket-count-group" style="display: none;">
                    <label for="form-ticket-count">Number of Tickets</label>
                    <input type="number" id="form-ticket-count" min="1" value="1" placeholder="1">
                </div>

                <div class="form-group" style="background: rgba(245, 158, 11, 0.08); border: 1px dashed rgba(245, 158, 11, 0.4); padding: 10px 14px; border-radius: 10px; display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="toggleFormTempCheckbox(event)">
                    <input type="checkbox" id="form-is-temporary" style="width: 18px; height: 18px; cursor: pointer; margin: 0;" onchange="handleTempCheckboxChange()">
                    <label for="form-is-temporary" style="margin: 0; cursor: pointer; color: #fbbf24; font-size: 0.8rem; text-transform: none; letter-spacing: 0;">⏳ Reserve as <strong>Temporary Hold</strong> (Name only, not added to Excel)</label>
                </div>

                <button type="submit" class="btn-primary" id="form-submit-btn">Confirm Kalabara Reservation</button>
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
                    <button type="button" class="btn-primary" style="padding: 8px 14px; font-size: 0.8rem; background: var(--gold-gradient); color: #110e1c; font-weight: 800; border: none; border-radius: 8px; cursor: pointer; white-space: nowrap; width: auto;" onclick="copySuccessCustomerUrl()">
                        Copy Link
                    </button>
                </div>
            </div>

            <button class="btn-primary" onclick="closeModal('success-modal')">Done</button>
        </div>
    </div>

    <!-- Loader Overlay -->
    <div class="loader-overlay" id="loading-overlay">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loading-text">Processing...</div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toast-container"></div>

    <script>
        // Trigonometric 8-seat positioning around the table ring (Identical to V1)
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
            el.style.left = `${150 + x - 22}px`;
            el.style.top = `${150 + y - 22}px`;
        });

        const IS_EDITOR = @json(!empty($isEditor));
        let reservationsList = [];
        let baseGroupsList = [];
        let editorTransferState = null; // { mode: 'v2_move' | 'v1_import', ... }
        let activeCategory = '';
        let activeTable = null;
        let activeBooking = null;
        let selectedSeats = [];
        let currentSearch = '';

        async function fetchBookingsAndRender() {
            try {
                const res = await fetch('/api/v2/bookings');
                if (res.ok) {
                    const data = await res.json();
                    reservationsList = data.reservations || [];
                }
            } catch (err) {
                console.error('Error fetching V2 bookings:', err);
            } finally {
                renderGrids();
                updateStats();
                reloadRecentBookingsTable();
                if (IS_EDITOR) {
                    fetchBaseBookings();
                }
            }
        }

        async function fetchBaseBookings() {
            if (!IS_EDITOR) return;
            try {
                const res = await fetch('/api/v2/base-bookings');
                if (res.ok) {
                    const data = await res.json();
                    baseGroupsList = data.base_groups || [];
                    const unassignedCount = baseGroupsList.filter(g => !g.already_on_v2).length;
                    const badge = document.getElementById('v1-unassigned-badge');
                    if (badge) badge.innerText = unassignedCount;
                    const drawer = document.getElementById('v1-import-drawer');
                    if (drawer && drawer.classList.contains('active')) {
                        renderV1ImportList();
                    }
                }
            } catch (err) {
                console.error('Error fetching base V1 bookings:', err);
            }
        }

        function toggleV1ImportDrawer() {
            const drawer = document.getElementById('v1-import-drawer');
            if (!drawer) return;
            const willBeActive = !drawer.classList.contains('active');
            drawer.classList.toggle('active');
            if (willBeActive) {
                if (baseGroupsList.length === 0) {
                    fetchBaseBookings();
                } else {
                    renderV1ImportList();
                }
            }
        }

        let v1DisplayLimit = 30;
        let v1SearchTimer = null;

        function debouncedRenderV1() {
            clearTimeout(v1SearchTimer);
            v1SearchTimer = setTimeout(renderV1ImportList, 120);
        }

        function renderV1ImportList() {
            const grid = document.getElementById('v1-cards-grid');
            if (!grid) return;

            const q = (document.getElementById('v1-filter-input')?.value || '').trim().toLowerCase();
            const showPlaced = Boolean(document.getElementById('v1-show-placed')?.checked);
            const sortOrder = document.getElementById('v1-sort-order')?.value || 'oldest';

            const filtered = baseGroupsList.filter(g => {
                // If table/booking already added to V2, DO NOT show it in the assign section
                if (!showPlaced && g.already_on_v2) return false;
                if (!q) return true;
                return (g.name && g.name.toLowerCase().includes(q)) ||
                       (g.ticket_number && g.ticket_number.toLowerCase().includes(q)) ||
                       (g.v1_location && g.v1_location.toLowerCase().includes(q)) ||
                       (g.category && g.category.toLowerCase().includes(q));
            });

            // Sort so the oldest bookings appear at the very top of the list
            if (sortOrder === 'oldest') {
                filtered.sort((a, b) => a.primary_id - b.primary_id);
            } else {
                filtered.sort((a, b) => b.primary_id - a.primary_id);
            }

            if (filtered.length === 0) {
                const emptyMsg = (!showPlaced && baseGroupsList.some(g => g.already_on_v2))
                    ? '🎉 All Base Map (V1) bookings have been assigned to the V2 map! (Check "Show already placed on V2" to view them).'
                    : 'No matching Base Map (V1) bookings found.';
                grid.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; padding: 18px; color: var(--text-muted);">${escapeModalHtml(emptyMsg)}</div>`;
                return;
            }

            const toShow = filtered.slice(0, v1DisplayLimit);
            let html = '';

            for (let i = 0; i < toShow.length; i++) {
                const g = toShow[i];
                const seatDesc = g.category === 'standing'
                    ? `${g.seat_count} Standing Ticket(s)`
                    : (g.is_full_table ? 'Full Table (8 Seats)' : `Seat(s): ${g.seats.join(', ')} (${g.seat_count})`);

                const placedBadge = g.already_on_v2
                    ? `<span class="badge-status-checkin" style="font-size:0.68rem;">✓ On V2: ${escapeModalHtml(g.v2_locations.join(', '))}</span>`
                    : `<span class="badge-status-temp" style="font-size:0.68rem;">Not on V2 Yet</span>`;

                let actionControls = '';
                if (g.category === 'standing') {
                    actionControls = `
                        <div style="display:flex;gap:6px;margin-top:4px;">
                            <button type="button" class="btn-primary" style="padding:6px 10px;font-size:0.75rem;width:auto;flex:1;" onclick="importStandingV1ById(${g.primary_id}, 'left')">⚡ Add to Left Standing</button>
                            <button type="button" class="btn-primary" style="padding:6px 10px;font-size:0.75rem;width:auto;flex:1;" onclick="importStandingV1ById(${g.primary_id}, 'right')">⚡ Add to Right Standing</button>
                        </div>
                    `;
                } else {
                    actionControls = `
                        <div style="display:flex;gap:6px;align-items:center;margin-top:4px;">
                            <input type="number" id="v1-target-tbl-${g.primary_id}" min="1" max="130" placeholder="V2 Table (1-130)" style="width:125px;background:rgba(7,5,13,0.9);border:1px solid var(--border-gold);border-radius:8px;padding:6px 8px;color:#fff;font-size:0.78rem;">
                            <button type="button" class="btn-primary" style="padding:6px 10px;font-size:0.76rem;width:auto;" onclick="quickImportV1ToTableById(${g.primary_id})">⚡ Assign</button>
                            <button type="button" class="btn-primary" style="padding:6px 10px;font-size:0.76rem;width:auto;background:rgba(0,242,254,0.16);border:1px solid #00f2fe;color:#00f2fe;box-shadow:none;" onclick="startPlaceV1OnMapById(${g.primary_id})">📌 Pick on Map</button>
                        </div>
                    `;
                }

                html += `
                    <div class="v1-booking-card ${g.already_on_v2 ? 'already-placed' : ''}">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                            <div>
                                <div style="font-weight:800;color:#fff;font-size:0.92rem;">${escapeModalHtml(g.name)}</div>
                                <div style="font-size:0.75rem;color:var(--gold-primary);font-family:monospace;">${g.is_temporary ? '⏳ TEMP HOLD' : escapeModalHtml(g.ticket_number)}</div>
                            </div>
                            ${placedBadge}
                        </div>
                        <div style="font-size:0.78rem;color:#cbd5e1;">
                            <span class="category-badge badge-${g.category}" style="padding:2px 6px;font-size:0.68rem;margin-right:4px;">${g.category}</span>
                            <strong>V1:</strong> ${escapeModalHtml(g.v1_location)} • ${escapeModalHtml(seatDesc)}
                        </div>
                        ${actionControls}
                    </div>
                `;
            }

            if (filtered.length > v1DisplayLimit) {
                html += `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 10px;">
                        <button type="button" class="btn-primary" style="width:auto;padding:8px 20px;border-radius:20px;" onclick="v1DisplayLimit += 30; renderV1ImportList();">Load More (${filtered.length - v1DisplayLimit} remaining)</button>
                    </div>
                `;
            }

            grid.innerHTML = html;
        }
        }

        async function importStandingV1ById(primaryId, side) {
            const groupObj = baseGroupsList.find(g => g.primary_id === primaryId);
            if (!groupObj) return;
            showLoader('Importing standing booking from Base Map...');
            try {
                const res = await fetch('/api/v2/import-from-base', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        v1_ids: groupObj.ids,
                        target_category: 'standing',
                        side: side
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message, 'success');
                    await fetchBookingsAndRender();
                } else {
                    showToast(data.message || 'Import failed.', 'error');
                }
            } catch (err) {
                showToast('Error importing standing booking.', 'error');
            } finally {
                hideLoader();
            }
        }

        async function quickImportV1ToTableById(primaryId) {
            const groupObj = baseGroupsList.find(g => g.primary_id === primaryId);
            if (!groupObj) return;
            const input = document.getElementById(`v1-target-tbl-${primaryId}`);
            const tbl = parseInt(input?.value || '0', 10);
            if (!tbl || tbl < 1 || tbl > 130) {
                showToast('Please enter a valid V2 Table number (1 to 130).', 'error');
                return;
            }

            showLoader(`Assigning Base Map booking to V2 Table ${tbl}...`);
            try {
                const res = await fetch('/api/v2/import-from-base', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        v1_ids: groupObj.ids,
                        table_number: tbl,
                        is_full_table: Boolean(groupObj.is_full_table)
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message, 'success');
                    await fetchBookingsAndRender();
                } else {
                    showToast(data.message || 'Could not assign to that table.', 'error');
                }
            } catch (err) {
                showToast('Error assigning booking to V2 table.', 'error');
            } finally {
                hideLoader();
            }
        }

        function startPlaceV1OnMapById(primaryId) {
            const groupObj = baseGroupsList.find(g => g.primary_id === primaryId);
            if (!groupObj) return;
            editorTransferState = {
                mode: 'v1_import',
                v1Group: groupObj
            };
            const banner = document.getElementById('editor-mode-banner');
            const title = document.getElementById('editor-mode-title');
            const desc = document.getElementById('editor-mode-desc');
            const icon = document.getElementById('editor-mode-icon');
            if (banner) {
                icon.innerText = '📌';
                title.innerText = 'PLACING BASE MAP (V1) BOOKING ON V2 MAP';
                const seatInfo = groupObj.is_full_table ? 'Full Table (8 Seats)' : `${groupObj.seat_count} Seat(s)`;
                desc.innerText = `Placing "${groupObj.name}" (${seatInfo} from V1 ${groupObj.v1_location}) — Click any Table (1–130) below!`;
                banner.classList.add('active');
            }
            const drawer = document.getElementById('v1-import-drawer');
            if (drawer && drawer.classList.contains('active')) {
                drawer.classList.remove('active');
            }
            showToast(`Click any Table (1–130) on the map to place ${groupObj.name}!`, 'success');
        }

        function cancelEditorMode() {
            editorTransferState = null;
            const banner = document.getElementById('editor-mode-banner');
            if (banner) banner.classList.remove('active');
            const modalBox = document.getElementById('modal-editor-transfer-box');
            if (modalBox) modalBox.style.display = 'none';
        }

        function escapeModalHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function renderGrids() {
            cachedCanvasNaturalH = 0;
            // VIP: Rows 1 - 5 (Tables 1 - 50)
            renderRowsSection('platinum', 1, 5, 'platinum-v2-rows');
            // Gold: Rows 6 - 13 (Tables 51 - 130)
            renderRowsSection('gold', 6, 13, 'gold-v2-rows');

            let leftPerm = 0, leftTemp = 0, rightPerm = 0, rightTemp = 0;
            for (let i = 0; i < reservationsList.length; i++) {
                const r = reservationsList[i];
                if (r.category === 'standing') {
                    if (r.side === 'left') {
                        if (r.is_temporary) leftTemp++; else leftPerm++;
                    } else if (r.side === 'right') {
                        if (r.is_temporary) rightTemp++; else rightPerm++;
                    }
                }
            }

            const leftEl = document.getElementById('left-standing-count');
            if (leftEl) leftEl.innerText = leftTemp > 0 ? `${leftPerm} Sold • ${leftTemp} Temp (Click to reserve)` : `${leftPerm} Sold (Click to reserve)`;
            const rightEl = document.getElementById('right-standing-count');
            if (rightEl) rightEl.innerText = rightTemp > 0 ? `${rightPerm} Sold • ${rightTemp} Temp (Click to reserve)` : `${rightPerm} Sold (Click to reserve)`;

            if (!window.__v2InitialZoomSet) {
                window.__v2InitialZoomSet = true;
                fitMapToViewport();
            } else {
                applyMapScale(false);
            }
        }

        function renderRowsSection(category, startRow, endRow, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;
            container.innerHTML = '';

            const q = currentSearch.trim().toLowerCase();

            // Pre-index reservations by table_number for O(1) instant lookup
            const bookingsByTable = {};
            for (let i = 0; i < reservationsList.length; i++) {
                const r = reservationsList[i];
                if (r.category !== 'standing' && r.table_number) {
                    const t = r.table_number;
                    if (!bookingsByTable[t]) bookingsByTable[t] = [];
                    bookingsByTable[t].push(r);
                }
            }

            const fragment = document.createDocumentFragment();

            for (let row = startRow; row <= endRow; row++) {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'v2-grid-row';

                // Left Row Number Circle (1 - 13)
                const rowBadge = document.createElement('div');
                rowBadge.className = 'v2-axis-badge';
                rowBadge.innerText = row;
                rowDiv.appendChild(rowBadge);

                for (let col = 1; col <= 10; col++) {
                    // Sequential table number starting at 1 (1 to 130)
                    const tableNum = ((row - 1) * 10) + col;
                    const label = `${tableNum}`;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'table-btn';
                    btn.id = `table-btn-${tableNum}`;

                    const bookingsForTable = bookingsByTable[tableNum] || [];

                    let hasTemp = false;
                    let hasCheckedInSeated = false;
                    let hasPermanent = false;
                    let isFull = false;

                    for (let j = 0; j < bookingsForTable.length; j++) {
                        const b = bookingsForTable[j];
                        if (b.is_full_table) isFull = true;
                        if (b.is_temporary) {
                            hasTemp = true;
                        } else {
                            hasPermanent = true;
                            if (b.is_checked_in) hasCheckedInSeated = true;
                        }
                    }

                    const bookedSeatsCount = bookingsForTable.length;

                    if (bookedSeatsCount > 0 && !hasPermanent && hasTemp) {
                        btn.classList.add('temporary-reserved');
                        btn.title = `Table ${tableNum} • Temporarily Reserved`;
                    } else if (isFull || bookedSeatsCount >= 8) {
                        btn.classList.add('fully-reserved');
                        if (hasTemp) btn.classList.add('has-temporary');
                    } else if (bookedSeatsCount > 0) {
                        btn.classList.add('partially-reserved');
                        if (hasTemp) btn.classList.add('has-temporary');
                    } else {
                        btn.classList.add('available');
                    }

                    if (hasCheckedInSeated) {
                        btn.classList.add('has-checked-in');
                        btn.innerText = `🔒${label}`;
                    } else {
                        btn.innerText = label;
                    }

                    if (q.length > 0) {
                        const isMatched = label === q ||
                            `table ${tableNum}` === q ||
                            `t${tableNum}` === q ||
                            bookingsForTable.some(b =>
                                (b.name && b.name.toLowerCase().includes(q)) ||
                                (b.email && b.email.toLowerCase().includes(q)) ||
                                (b.ticket_number && b.ticket_number.toLowerCase().includes(q))
                            );
                        if (isMatched) {
                            btn.classList.add('search-matched-table');
                        }
                    }

                    btn.onclick = () => openSeatingModal(category, tableNum);
                    rowDiv.appendChild(btn);
                }

                fragment.appendChild(rowDiv);
            }

            container.appendChild(fragment);
        }

        // Open 8-Seat Table Ring Modal (Identical to V1)
        function openSeatingModal(category, tableNumber) {
            activeCategory = category;
            activeTable = tableNumber;
            selectedSeats = [];

            const rowNum = Math.ceil(tableNumber / 10);
            const colNum = ((tableNumber - 1) % 10) + 1;
            const suitSymbol = category === 'platinum' ? '♠' : '♥';
            const catDisplay = category === 'platinum' ? 'VIP' : 'GOLD';
            document.getElementById('modal-table-title').innerText = `${suitSymbol} ${catDisplay} - Table ${tableNumber} (Row ${rowNum}, Col ${colNum})`;
            document.getElementById('modal-table-center-id').innerText = `${tableNumber}`;

            const bookings = reservationsList.filter(r =>
                r.category !== 'standing' &&
                parseInt(r.table_number, 10) === parseInt(tableNumber, 10)
            );
            const isFullTableBooked = bookings.some(r => r.is_full_table);
            const tempBookings = bookings.filter(r => Boolean(r.is_temporary));

            const bubble = document.getElementById('seat-detail-bubble');
            bubble.style.display = 'none';

            const reserveFullBtn = document.getElementById('btn-reserve-full');
            const tempHoldFullBtn = document.getElementById('btn-temp-hold-full');
            const fullTableTempActions = document.getElementById('full-table-temp-actions');
            const hint = document.getElementById('seat-select-hint');

            // Temporary Allocation Banner
            const tempBanner = document.getElementById('modal-temporary-banner');
            const tempNamesContainer = document.getElementById('modal-temporary-names');
            if (tempBookings.length > 0 && tempBanner && tempNamesContainer) {
                const fullTemp = tempBookings.find(r => r.is_full_table);
                if (fullTemp) {
                    tempNamesContainer.innerHTML = `<strong>Full Table (All 8 Seats)</strong> allocated to: <span style="color: #fbbf24; font-weight: 800; font-size: 1rem;">${escapeModalHtml(fullTemp.name)}</span>`;
                } else {
                    const byName = {};
                    tempBookings.forEach(b => {
                        const n = b.name || 'Unnamed';
                        if (!byName[n]) byName[n] = [];
                        if (b.seat_number) byName[n].push(`Seat ${b.seat_number}`);
                    });
                    tempNamesContainer.innerHTML = Object.entries(byName)
                        .map(([name, seatsArr]) => `• <strong style="color: #fbbf24;">${escapeModalHtml(name)}</strong> (${seatsArr.join(', ')})`)
                        .join('<br>');
                }
                tempBanner.style.display = 'block';
            } else if (tempBanner) {
                tempBanner.style.display = 'none';
            }

            if (isFullTableBooked) {
                const fullBooking = bookings.find(r => r.is_full_table);
                const isFullTemp = Boolean(fullBooking.is_temporary);

                reserveFullBtn.style.display = 'none';
                tempHoldFullBtn.style.display = 'none';
                hint.style.display = 'none';

                if (isFullTemp && fullTableTempActions) {
                    fullTableTempActions.style.display = 'flex';
                    document.getElementById('btn-make-table-perm').onclick = () => openMakePermanentModal(fullBooking.id);
                    document.getElementById('btn-release-table-temp').onclick = async () => {
                        if (confirm(`Release temporary table hold for ${fullBooking.name}?`)) {
                            await executeDelete(fullBooking.id);
                            closeModal('seating-modal');
                        }
                    };
                } else if (fullTableTempActions) {
                    fullTableTempActions.style.display = 'none';
                }

                for (let s = 1; s <= 8; s++) {
                    const seatEl = document.getElementById(`seat-${s}`);
                    const isFullCheckedIn = Boolean(fullBooking.is_checked_in) && !isFullTemp;
                    seatEl.className = isFullTemp
                        ? 'seat-circle temporary'
                        : (isFullCheckedIn ? 'seat-circle reserved checked-in' : 'seat-circle reserved');
                    seatEl.innerHTML = isFullCheckedIn ? `<span style="font-size:0.7rem;">🔒</span><span>${s}</span>` : `${s}`;
                    seatEl.onclick = () => showBubble(fullBooking);
                }

                showBubble(fullBooking);
            } else {
                const isTableEmpty = bookings.length === 0;
                reserveFullBtn.style.display = isTableEmpty ? 'block' : 'none';
                tempHoldFullBtn.style.display = isTableEmpty ? 'block' : 'none';
                fullTableTempActions.style.display = 'none';
                hint.style.display = 'block';

                for (let s = 1; s <= 8; s++) {
                    const seatEl = document.getElementById(`seat-${s}`);
                    const seatBooking = bookings.find(r => parseInt(r.seat_number, 10) === s);

                    if (seatBooking) {
                        const isSeatTemp = Boolean(seatBooking.is_temporary);
                        const isSeatCheckedIn = Boolean(seatBooking.is_checked_in) && !isSeatTemp;
                        seatEl.className = isSeatTemp
                            ? 'seat-circle temporary'
                            : (isSeatCheckedIn ? 'seat-circle reserved checked-in' : 'seat-circle reserved');
                        seatEl.innerHTML = isSeatCheckedIn ? `<span style="font-size:0.7rem;">🔒</span><span>${s}</span>` : `${s}`;
                        seatEl.onclick = () => showBubble(seatBooking);
                    } else {
                        seatEl.className = 'seat-circle available';
                        seatEl.innerHTML = `${s}`;
                        seatEl.onclick = () => toggleSeatSelection(s);
                    }
                }

                if (tempBookings.length > 0) {
                    showBubble(tempBookings[0]);
                }
            }

            updateSelectedSeatsButton();

            // Show Editor Transfer / Placement Action Box inside the modal if Editor Mode is active
            const transferBox = document.getElementById('modal-editor-transfer-box');
            const transferTitle = document.getElementById('modal-transfer-title');
            const transferDesc = document.getElementById('modal-transfer-desc');
            const transferBtn = document.getElementById('btn-confirm-transfer-here');

            if (editorTransferState && transferBox) {
                transferBox.style.display = 'block';
                if (editorTransferState.mode === 'v2_move') {
                    transferTitle.innerText = '⇄ MOVE USER ALLOCATION TO THIS TABLE';
                    const countLabel = editorTransferState.isFullTable ? 'Full Table (8 Seats)' : `${editorTransferState.ids.length} Seat(s)`;
                    transferDesc.innerHTML = `Moving <strong>${escapeModalHtml(editorTransferState.guestName)}</strong> (${countLabel} from Table ${editorTransferState.sourceTable}) to <strong>Table ${tableNumber}</strong>.<br><span style="font-size:0.76rem;color:#cbd5e1;">Tip: You can click specific green seat circles (1–8) below first, or click confirm to auto-assign seats.</span>`;
                    transferBtn.innerText = `✓ Move ${editorTransferState.guestName} to Table ${tableNumber}`;
                } else if (editorTransferState.mode === 'v1_import') {
                    const g = editorTransferState.v1Group;
                    transferTitle.innerText = '📥 ASSIGN BASE MAP (V1) BOOKING TO THIS TABLE';
                    const countLabel = g.is_full_table ? 'Full Table (8 Seats)' : `${g.seat_count} Seat(s)`;
                    transferDesc.innerHTML = `Assigning <strong>${escapeModalHtml(g.name)}</strong> (${countLabel} from V1 ${escapeModalHtml(g.v1_location)}) to <strong>Table ${tableNumber}</strong>.<br><span style="font-size:0.76rem;color:#cbd5e1;">Tip: Select specific green seat circles (1–8) below or click confirm to auto-assign seats.</span>`;
                    transferBtn.innerText = `✓ Assign ${g.name} to Table ${tableNumber}`;
                }
            } else if (transferBox) {
                transferBox.style.display = 'none';
            }

            openModal('seating-modal');
        }

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

            if (selectedSeats.length > 0) {
                const bookBtn = document.getElementById('btn-book-selected');
                if (bookBtn) {
                    setTimeout(() => bookBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
                }
            }
        }

        function updateSelectedSeatsButton() {
            const btn = document.getElementById('btn-book-selected');
            const tempBtn = document.getElementById('btn-temp-hold-selected');
            const count = selectedSeats.length;
            if (btn) {
                btn.innerText = `Book Selected Seats (${count})`;
                btn.style.display = count > 0 ? 'block' : 'none';
            }
            if (tempBtn) {
                tempBtn.innerText = `⏳ Temporary Hold Selected Seats (${count})`;
                tempBtn.style.display = count > 0 ? 'block' : 'none';
            }
        }

        function showBubble(booking) {
            activeBooking = booking;
            const isTemp = Boolean(booking.is_temporary);
            const isCheckedIn = Boolean(booking.is_checked_in) && !isTemp;
            const isSeated = booking.category !== 'standing';
            const bubble = document.getElementById('seat-detail-bubble');
            const statusEl = document.getElementById('bubble-status');
            const checkinRow = document.getElementById('bubble-checkin-row');
            const checkinEl = document.getElementById('bubble-checkin');
            const emailRow = document.getElementById('bubble-email-row');
            const btnMakePerm = document.getElementById('btn-make-seat-perm');
            const btnToggleCheckin = document.getElementById('btn-toggle-seat-checkin');
            const btnCopyUrl = document.getElementById('btn-copy-seat-url');
            const btnDelete = document.getElementById('btn-delete-seat');

            statusEl.innerHTML = isTemp
                ? `<span class="badge-status-temp">⏳ Temporary Hold (Not in Excel)</span>`
                : `<span class="badge-status-perm">✓ Permanent (Synced to Excel)</span>`;

            if (isTemp) {
                checkinRow.style.display = 'none';
                btnToggleCheckin.style.display = 'none';
            } else {
                checkinRow.style.display = 'block';
                const lockIcon = isSeated ? '🔒 ' : '✓ ';
                checkinEl.innerHTML = isCheckedIn
                    ? `<span class="badge-status-checkin">${lockIcon}Checked In</span>`
                    : `<span style="color: var(--text-muted); font-size: 0.8rem;">⏳ Pending Check-In</span>`;

                btnToggleCheckin.style.display = 'inline-block';
                if (isCheckedIn) {
                    btnToggleCheckin.innerText = '↩️ Undo Check-In';
                    btnToggleCheckin.style.background = 'rgba(255, 255, 255, 0.1)';
                    btnToggleCheckin.style.color = '#e2e8f0';
                } else {
                    btnToggleCheckin.innerText = isSeated ? '🔒 Mark as Checked-In' : '✓ Mark as Checked-In';
                    btnToggleCheckin.style.background = 'linear-gradient(135deg, #39ff14, #10b981)';
                    btnToggleCheckin.style.color = '#07050d';
                }
            }

            document.getElementById('bubble-name').innerText = booking.name;
            document.getElementById('bubble-email').innerText = booking.email || 'Not added yet';
            document.getElementById('bubble-ticket').innerText = isTemp ? 'Pending Permanent Confirmation' : booking.ticket_number;

            // Populate default Quick-Move fields in bubble
            const qTableInput = document.getElementById('quick-move-table');
            const qSeatSelect = document.getElementById('quick-move-seat');
            const qScopeSelect = document.getElementById('quick-move-scope');
            if (qTableInput) qTableInput.value = booking.table_number || '';
            if (qSeatSelect) {
                qSeatSelect.value = booking.seat_number ? String(booking.seat_number) : '';
                qSeatSelect.style.display = booking.is_full_table ? 'none' : 'inline-block';
            }
            if (qScopeSelect) {
                qScopeSelect.style.display = booking.is_full_table ? 'none' : 'inline-block';
            }

            emailRow.style.display = isTemp && !booking.email ? 'none' : 'block';
            btnMakePerm.style.display = isTemp ? 'inline-block' : 'none';
            btnCopyUrl.style.display = isTemp ? 'none' : 'inline-block';
            btnDelete.innerText = isTemp ? '🗑️ Release Hold' : 'Delete Ticket';

            bubble.style.display = 'block';
            setTimeout(() => bubble.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
        }

        function getActiveMoveIds() {
            if (!activeBooking) return [];
            if (activeBooking.is_full_table) return [activeBooking.id];
            const scope = document.getElementById('quick-move-scope')?.value || 'single';
            if (scope === 'group') {
                return reservationsList
                    .filter(r =>
                        parseInt(r.table_number, 10) === parseInt(activeBooking.table_number, 10) &&
                        !r.is_full_table &&
                        ((activeBooking.ticket_number && r.ticket_number === activeBooking.ticket_number) || r.name === activeBooking.name)
                    )
                    .map(r => r.id);
            }
            return [activeBooking.id];
        }

        async function submitQuickMoveFromBubble() {
            if (!activeBooking) return;
            const targetTable = parseInt(document.getElementById('quick-move-table')?.value || '0', 10);
            if (!targetTable || targetTable < 1 || targetTable > 130) {
                showToast('Please enter a valid target Table number (1 to 130).', 'error');
                return;
            }
            const seatVal = document.getElementById('quick-move-seat')?.value || '';
            const ids = getActiveMoveIds();
            const targetSeatNumbers = (!activeBooking.is_full_table && ids.length === 1 && seatVal) ? [parseInt(seatVal, 10)] : [];

            await performMoveAllocation(ids, targetTable, targetSeatNumbers);
        }

        function startMoveOnMapFromBubble() {
            if (!activeBooking) return;
            const ids = getActiveMoveIds();
            editorTransferState = {
                mode: 'v2_move',
                ids: ids,
                guestName: activeBooking.name,
                sourceTable: activeBooking.table_number,
                isFullTable: Boolean(activeBooking.is_full_table)
            };

            closeModal('seating-modal');

            const banner = document.getElementById('editor-mode-banner');
            const title = document.getElementById('editor-mode-title');
            const desc = document.getElementById('editor-mode-desc');
            const icon = document.getElementById('editor-mode-icon');
            if (banner) {
                icon.innerText = '⇄';
                title.innerText = 'EDITOR SEAT ALLOCATION MOVE MODE';
                const countDesc = activeBooking.is_full_table ? 'Full Table (8 Seats)' : `${ids.length} Seat(s)`;
                desc.innerText = `Moving "${activeBooking.name}" (${countDesc} from Table ${activeBooking.table_number}) — Click any target Table (1–130) on the map!`;
                banner.classList.add('active');
            }
            showToast(`Click any target Table (1–130) on the map to move ${activeBooking.name}!`, 'success');
        }

        async function executeEditorTransferToActiveTable() {
            if (!editorTransferState || !activeTable) return;

            if (editorTransferState.mode === 'v2_move') {
                const chosenSeats = [...selectedSeats].sort((a, b) => a - b);
                await performMoveAllocation(editorTransferState.ids, activeTable, chosenSeats);
            } else if (editorTransferState.mode === 'v1_import') {
                const g = editorTransferState.v1Group;
                const chosenSeats = [...selectedSeats].sort((a, b) => a - b);
                showLoader(`Assigning ${g.name} to Table ${activeTable}...`);
                try {
                    const res = await fetch('/api/v2/import-from-base', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            v1_ids: g.ids,
                            table_number: activeTable,
                            is_full_table: g.is_full_table && chosenSeats.length === 0,
                            seat_numbers: chosenSeats
                        })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        cancelEditorMode();
                        closeModal('seating-modal');
                        showToast(data.message, 'success');
                        await fetchBookingsAndRender();
                    } else {
                        showToast(data.message || 'Failed to assign booking.', 'error');
                    }
                } catch (err) {
                    showToast('Error assigning V1 booking.', 'error');
                } finally {
                    hideLoader();
                }
            }
        }

        async function performMoveAllocation(reservationIds, targetTable, targetSeatNumbers = []) {
            showLoader(`Moving seat allocation to Table ${targetTable}...`);
            try {
                const res = await fetch('/api/v2/move-allocation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        reservation_ids: reservationIds,
                        target_table_number: targetTable,
                        target_seat_numbers: targetSeatNumbers
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    cancelEditorMode();
                    closeModal('seating-modal');
                    showToast(data.message, 'success');
                    await fetchBookingsAndRender();
                } else {
                    showToast(data.message || 'Move failed.', 'error');
                }
            } catch (err) {
                showToast('Error moving seat allocation.', 'error');
            } finally {
                hideLoader();
            }
        }

        function openTemporaryHoldModal(mode) {
            if (mode === 'seats' && selectedSeats.length === 0) return;
            closeModal('seating-modal');

            const isFull = mode === 'full';
            const seatsSorted = isFull ? [] : [...selectedSeats].sort((a, b) => a - b);

            document.getElementById('temp-hold-category').value = activeCategory;
            document.getElementById('temp-hold-table-number').value = activeTable;
            document.getElementById('temp-hold-seat-numbers').value = isFull ? '' : JSON.stringify(seatsSorted);
            document.getElementById('temp-hold-is-full-table').value = isFull ? 'true' : 'false';
            document.getElementById('temp-hold-name').value = '';

            document.getElementById('temp-hold-summary-text').innerText = isFull
                ? `Section: ${activeCategory.toUpperCase()} | Table: ${activeTable} (Full Table - 8 Seats)`
                : `Section: ${activeCategory.toUpperCase()} | Table: ${activeTable} | ${seatsSorted.length === 1 ? 'Seat' : 'Seats'}: ${seatsSorted.join(', ')}`;

            openModal('temporary-hold-modal');
        }

        async function submitTemporaryHold(event) {
            event.preventDefault();
            const seatNumbersRaw = document.getElementById('temp-hold-seat-numbers').value;
            const payload = {
                category: document.getElementById('temp-hold-category').value,
                side: 'center',
                table_number: document.getElementById('temp-hold-table-number').value,
                seat_numbers: seatNumbersRaw ? JSON.parse(seatNumbersRaw) : [],
                is_full_table: document.getElementById('temp-hold-is-full-table').value,
                is_temporary: true,
                name: document.getElementById('temp-hold-name').value
            };

            showLoader('Saving temporary table reservation...');
            try {
                const res = await fetch('/api/v2/bookings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    closeModal('temporary-hold-modal');
                    showToast(data.message || 'Temporary reservation saved (Excluded from Excel).', 'success');
                    await fetchBookingsAndRender();
                } else {
                    showToast('Temporary hold failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (err) {
                showToast('An error occurred while saving temporary hold.', 'error');
            } finally {
                hideLoader();
            }
        }

        function openMultiSeatBooking() {
            if (selectedSeats.length === 0) return;
            closeModal('seating-modal');

            const seatsSorted = [...selectedSeats].sort((a, b) => a - b);
            const seatWord = seatsSorted.length === 1 ? 'Seat' : 'Seats';

            document.getElementById('booking-title').innerText = seatsSorted.length === 1 ? 'Seat Booking' : 'Multi-Seat Booking';
            document.getElementById('booking-summary-text').innerText = `Section: ${activeCategory.toUpperCase()} | Table: ${activeTable} | ${seatWord}: ${seatsSorted.join(', ')}`;

            document.getElementById('form-category').value = activeCategory;
            document.getElementById('form-side').value = 'center';
            document.getElementById('form-table-number').value = activeTable;
            document.getElementById('form-seat-numbers').value = JSON.stringify(seatsSorted);
            document.getElementById('form-is-full-table').value = 'false';
            document.getElementById('ticket-count-group').style.display = 'none';

            clearFormFields();
            openModal('booking-form-modal');
        }

        function initiateFullTableBooking() {
            closeModal('seating-modal');

            document.getElementById('booking-title').innerText = 'Full Table Booking';
            document.getElementById('booking-summary-text').innerText = `Section: ${activeCategory.toUpperCase()} | Table: ${activeTable} (All 8 Seats)`;

            document.getElementById('form-category').value = activeCategory;
            document.getElementById('form-side').value = 'center';
            document.getElementById('form-table-number').value = activeTable;
            document.getElementById('form-seat-numbers').value = '';
            document.getElementById('form-is-full-table').value = 'true';
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
            document.getElementById('form-seat-numbers').value = '';
            document.getElementById('form-is-full-table').value = 'false';
            document.getElementById('ticket-count-group').style.display = 'block';
            document.getElementById('form-ticket-count').value = '1';

            clearFormFields();
            openModal('booking-form-modal');
        }

        function clearFormFields() {
            document.getElementById('form-name').value = '';
            document.getElementById('form-email').value = '';
            document.getElementById('form-ticket').value = '';
            const tempCb = document.getElementById('form-is-temporary');
            if (tempCb) {
                tempCb.checked = false;
                handleTempCheckboxChange();
            }
        }

        function toggleFormTempCheckbox(event) {
            if (event.target.tagName !== 'INPUT' && event.target.tagName !== 'LABEL') {
                const cb = document.getElementById('form-is-temporary');
                if (cb) {
                    cb.checked = !cb.checked;
                    handleTempCheckboxChange();
                }
            }
        }

        function handleTempCheckboxChange() {
            const isTemp = document.getElementById('form-is-temporary').checked;
            const emailInput = document.getElementById('form-email');
            const ticketInput = document.getElementById('form-ticket');
            const emailGroup = document.getElementById('form-email-group');
            const ticketGroup = document.getElementById('form-ticket-group');
            const submitBtn = document.getElementById('form-submit-btn');

            if (isTemp) {
                emailInput.removeAttribute('required');
                ticketInput.removeAttribute('required');
                emailGroup.style.display = 'none';
                ticketGroup.style.display = 'none';
                submitBtn.innerText = '⏳ Confirm Temporary Hold (No Excel Sync)';
                submitBtn.classList.add('btn-temp-action');
            } else {
                emailInput.setAttribute('required', 'required');
                ticketInput.setAttribute('required', 'required');
                emailGroup.style.display = 'block';
                ticketGroup.style.display = 'block';
                submitBtn.innerText = 'Confirm Kalabara Reservation';
                submitBtn.classList.remove('btn-temp-action');
            }
        }

        async function submitBooking(event) {
            event.preventDefault();
            const category = document.getElementById('form-category').value;
            const seatNumbersRaw = document.getElementById('form-seat-numbers').value;
            const isTemp = document.getElementById('form-is-temporary').checked;

            const payload = {
                category: category,
                side: document.getElementById('form-side').value,
                table_number: document.getElementById('form-table-number').value,
                seat_numbers: seatNumbersRaw ? JSON.parse(seatNumbersRaw) : [],
                is_full_table: document.getElementById('form-is-full-table').value,
                is_temporary: isTemp,
                name: document.getElementById('form-name').value,
                email: document.getElementById('form-email').value,
                ticket_number: document.getElementById('form-ticket').value,
                ticket_count: category === 'standing' ? parseInt(document.getElementById('form-ticket-count').value || '1', 10) : 1
            };

            showLoader(isTemp ? 'Saving temporary reservation...' : 'Creating Kalabara reservation...');
            try {
                const res = await fetch('/api/v2/bookings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    closeModal('booking-form-modal');

                    if (data.is_temporary) {
                        showToast(data.message || 'Temporary hold created (Excluded from Excel).', 'success');
                    } else {
                        const reservations = data.reservations || [data.reservation];
                        const first = data.reservation;
                        document.getElementById('success-guest-name').innerText = first.name;
                        document.getElementById('success-category').innerText = first.category.toUpperCase();
                        document.getElementById('success-ticket').innerText = reservations.length > 1
                            ? `${first.ticket_number} (${reservations.length} Tickets)`
                            : first.ticket_number;

                        if (first.category === 'standing') {
                            document.getElementById('success-details').innerText = `${(first.side || 'left').toUpperCase()} Standing` + (reservations.length > 1 ? ` (${reservations.length} Tickets)` : '');
                        } else if (first.is_full_table) {
                            document.getElementById('success-details').innerText = `Table ${first.table_number} (Full Table - 8 Seats)`;
                        } else {
                            const seatNums = reservations.map(r => r.seat_number).sort((a, b) => a - b);
                            document.getElementById('success-details').innerText = `Table ${first.table_number} (${seatNums.length === 1 ? 'Seat' : 'Seats'} ${seatNums.join(', ')})`;
                        }

                        document.getElementById('success-customer-url').value = `${window.location.origin}/v2/seat/${encodeURIComponent(first.ticket_number)}`;
                        openModal('success-modal');
                    }

                    await fetchBookingsAndRender();
                } else {
                    showToast('Booking failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (err) {
                showToast('An error occurred during submission.', 'error');
            } finally {
                hideLoader();
            }
        }

        function openMakePermanentForActiveSeat() {
            if (!activeBooking || !activeBooking.id) return;
            openMakePermanentModal(activeBooking.id);
        }

        function openMakePermanentModal(bookingId) {
            const booking = reservationsList.find(r => String(r.id) === String(bookingId));
            if (!booking) return;

            closeModal('seating-modal');
            document.getElementById('permanent-res-id').value = booking.id;
            document.getElementById('permanent-name').value = booking.name || '';
            document.getElementById('permanent-email').value = booking.email || '';
            document.getElementById('permanent-ticket').value = (booking.ticket_number && !String(booking.ticket_number).startsWith('TEMP-')) ? booking.ticket_number : '';

            const locText = booking.category === 'standing'
                ? `STANDING (${(booking.side || 'left').toUpperCase()})`
                : `${booking.category.toUpperCase()} | Table ${booking.table_number} (${booking.is_full_table ? 'Full Table - 8 Seats' : 'Seat ' + booking.seat_number})`;

            document.getElementById('permanent-summary-text').innerHTML = `<strong>Converting Temporary Hold:</strong> ${locText}<br>Currently Allocated To: <strong>${escapeModalHtml(booking.name)}</strong>`;
            openModal('make-permanent-modal');
        }

        async function submitMakePermanent(event) {
            event.preventDefault();
            const id = document.getElementById('permanent-res-id').value;
            showLoader('Converting to permanent booking & syncing to Excel...');
            try {
                const res = await fetch(`/api/v2/bookings/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        make_permanent: true,
                        name: document.getElementById('permanent-name').value,
                        email: document.getElementById('permanent-email').value,
                        ticket_number: document.getElementById('permanent-ticket').value,
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    closeModal('make-permanent-modal');
                    showToast(data.message || 'Converted to permanent & synced to Excel!', 'success');
                    await fetchBookingsAndRender();
                } else {
                    showToast(data.message || 'Update failed.', 'error');
                }
            } catch (err) {
                showToast('Error converting reservation.', 'error');
            } finally {
                hideLoader();
            }
        }

        async function toggleActiveSeatCheckIn() {
            if (!activeBooking || !activeBooking.id) return;
            const newStatus = !Boolean(activeBooking.is_checked_in);
            showLoader(newStatus ? 'Marking seat as checked in...' : 'Reverting check-in status...');
            try {
                const res = await fetch('/api/v2/check-in/mark', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        reservation_id: activeBooking.id,
                        status: newStatus
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'Check-in status updated!', 'success');
                    await fetchBookingsAndRender();
                    if (activeCategory && activeTable) {
                        openSeatingModal(activeCategory, activeTable);
                        const updated = reservationsList.find(r => r.id == activeBooking.id);
                        if (updated) showBubble(updated);
                    }
                } else {
                    showToast(data.message || 'Failed to update check-in status.', 'error');
                }
            } catch (err) {
                showToast('Error updating check-in status.', 'error');
            } finally {
                hideLoader();
            }
        }

        async function deleteActiveSeatBooking() {
            if (!activeBooking || !activeBooking.id) return;
            const msg = activeBooking.is_temporary
                ? `Release temporary hold for ${activeBooking.name}?`
                : `Cancel permanent reservation for ${activeBooking.name} (Ticket: ${activeBooking.ticket_number})?`;
            if (confirm(msg)) {
                await executeDelete(activeBooking.id);
                closeModal('seating-modal');
            }
        }

        async function confirmDeleteBooking(id) {
            const b = reservationsList.find(r => r.id == id);
            if (!b) return;
            const msg = b.is_temporary
                ? `Release temporary hold for ${b.name}?`
                : `Cancel permanent reservation for ${b.name} (Ticket: ${b.ticket_number})?`;
            if (confirm(msg)) {
                await executeDelete(id);
            }
        }

        async function executeDelete(id) {
            showLoader('Cancelling reservation...');
            try {
                const res = await fetch(`/api/v2/bookings/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message, 'success');
                    await fetchBookingsAndRender();
                } else {
                    showToast(data.message || 'Delete failed.', 'error');
                }
            } catch (err) {
                showToast('Error deleting booking.', 'error');
            } finally {
                hideLoader();
            }
        }

        function updateStats() {
            const isTemp = r => Boolean(r.is_temporary);
            const isPerm = r => !r.is_temporary;
            const isCheckedIn = r => Boolean(r.is_checked_in) && isPerm(r);

            // VIP / Platinum (Rows 1-5, Tables 1-50, 8 seats per table = 400 capacity)
            const platFull = reservationsList.filter(r => r.category === 'platinum' && r.is_full_table && isPerm(r)).length;
            const platInd = reservationsList.filter(r => r.category === 'platinum' && !r.is_full_table && isPerm(r)).length;
            const platSold = (platFull * 8) + platInd;
            const platCap = 50 * 8;
            const platPct = ((platSold / platCap) * 100).toFixed(1);

            const platTempFull = reservationsList.filter(r => r.category === 'platinum' && r.is_full_table && isTemp(r)).length;
            const platTempInd = reservationsList.filter(r => r.category === 'platinum' && !r.is_full_table && isTemp(r)).length;
            const platTemp = (platTempFull * 8) + platTempInd;

            const platCheckFull = reservationsList.filter(r => r.category === 'platinum' && r.is_full_table && isCheckedIn(r)).length;
            const platCheckInd = reservationsList.filter(r => r.category === 'platinum' && !r.is_full_table && isCheckedIn(r)).length;
            const platCheckedIn = (platCheckFull * 8) + platCheckInd;

            // Gold (Rows 6-13, Tables 51-130, 8 seats per table = 640 capacity)
            const goldFull = reservationsList.filter(r => r.category === 'gold' && r.is_full_table && isPerm(r)).length;
            const goldInd = reservationsList.filter(r => r.category === 'gold' && !r.is_full_table && isPerm(r)).length;
            const goldSold = (goldFull * 8) + goldInd;
            const goldCap = 80 * 8;
            const goldPct = ((goldSold / goldCap) * 100).toFixed(1);

            const goldTempFull = reservationsList.filter(r => r.category === 'gold' && r.is_full_table && isTemp(r)).length;
            const goldTempInd = reservationsList.filter(r => r.category === 'gold' && !r.is_full_table && isTemp(r)).length;
            const goldTemp = (goldTempFull * 8) + goldTempInd;

            const goldCheckFull = reservationsList.filter(r => r.category === 'gold' && r.is_full_table && isCheckedIn(r)).length;
            const goldCheckInd = reservationsList.filter(r => r.category === 'gold' && !r.is_full_table && isCheckedIn(r)).length;
            const goldCheckedIn = (goldCheckFull * 8) + goldCheckInd;

            // Standing
            const standingSold = reservationsList.filter(r => r.category === 'standing' && isPerm(r)).length;
            const standingTemp = reservationsList.filter(r => r.category === 'standing' && isTemp(r)).length;
            const standingCheckedIn = reservationsList.filter(r => r.category === 'standing' && isCheckedIn(r)).length;

            const totalSold = platSold + goldSold + standingSold;
            const totalTemp = platTemp + goldTemp + standingTemp;
            const totalCheckedIn = platCheckedIn + goldCheckedIn + standingCheckedIn;
            const remainingCheckIn = Math.max(0, totalSold - totalCheckedIn);
            const checkInPct = totalSold > 0 ? ((totalCheckedIn / totalSold) * 100).toFixed(1) : 0;
            const totalSeatedPct = (((platSold + goldSold) / (platCap + goldCap)) * 100).toFixed(1);

            document.getElementById('stats-total-sold').innerText = totalSold;
            document.getElementById('header-total-sold').innerText = totalSold;
            document.getElementById('stats-total-sub').innerHTML = `<span style="color: var(--gold-primary);">♠ ${platSold}</span> &bull; <span style="color: #ff758f;">♥ ${goldSold}</span> &bull; <span style="color: #c77dff;">♣ ${standingSold}</span>`;
            document.getElementById('stats-total-progress').style.width = `${totalSeatedPct}%`;

            document.getElementById('stats-checkin-sold').innerText = totalCheckedIn;
            document.getElementById('stats-checkin-remaining').innerText = remainingCheckIn;
            document.getElementById('stats-checkin-sub').innerHTML = `<span style="color: var(--gold-primary);">♠ ${platCheckedIn}</span> &bull; <span style="color: #ff758f;">♥ ${goldCheckedIn}</span> &bull; <span style="color: #c77dff;">♣ ${standingCheckedIn}</span>`;
            document.getElementById('stats-checkin-progress').style.width = `${checkInPct}%`;

            document.getElementById('stats-temporary-sold').innerText = totalTemp;
            document.getElementById('stats-temporary-sub').innerHTML = `<span style="color: var(--gold-primary);">♠ ${platTemp}</span> &bull; <span style="color: #ff758f;">♥ ${goldTemp}</span> &bull; <span style="color: #c77dff;">♣ ${standingTemp}</span>`;

            document.getElementById('stats-platinum-sold').innerHTML = `${platSold} <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);">/ ${platCap}</span>`;
            document.getElementById('stats-platinum-pct').innerText = platPct;
            document.getElementById('stats-platinum-temp').innerText = platTemp;
            document.getElementById('stats-platinum-progress').style.width = `${platPct}%`;

            document.getElementById('stats-gold-sold').innerHTML = `${goldSold} <span style="font-size: 1.1rem; font-weight: 500; color: var(--text-muted);">/ ${goldCap}</span>`;
            document.getElementById('stats-gold-pct').innerText = goldPct;
            document.getElementById('stats-gold-temp').innerText = goldTemp;
            document.getElementById('stats-gold-progress').style.width = `${goldPct}%`;

            document.getElementById('stats-standing-sold').innerText = standingSold;
            document.getElementById('stats-standing-temp').innerText = standingTemp;
        }

        function reloadRecentBookingsTable() {
            const container = document.getElementById('recent-bookings-list');
            if (!container) return;

            if (reservationsList.length === 0) {
                container.innerHTML = '<tr><td colspan="9" style="text-align: center; color: var(--text-muted);">No reservations found.</td></tr>';
                return;
            }

            const sorted = [...reservationsList].sort((a, b) => b.id - a.id).slice(0, 15);
            container.innerHTML = '';

            sorted.forEach(b => {
                const row = document.createElement('tr');
                const date = b.created_at ? new Date(b.created_at).toISOString().replace('T', ' ').substring(0, 16) : 'N/A';
                const isTemp = Boolean(b.is_temporary);
                const isSeated = b.category !== 'standing';

                const statusBadge = isTemp
                    ? `<span class="badge-status-temp">⏳ Temporary</span>`
                    : (Boolean(b.is_checked_in)
                        ? `<span class="badge-status-checkin">${isSeated ? '🔒' : '✓'} Checked In</span>`
                        : `<span class="badge-status-perm">✓ Permanent</span>`);

                const tableLabel = isSeated ? `Table ${b.table_number}` : `${(b.side || 'left').toUpperCase()} Standing`;
                const seatLabel = isSeated ? (b.is_full_table ? 'Full Table (8 Seats)' : `Seat ${b.seat_number}`) : 'Standing';

                const moveBtn = (IS_EDITOR && isSeated)
                    ? `<button onclick="openSeatForMove('${b.id}')" class="btn-primary" style="padding: 4px 8px; font-size: 0.75rem; background: linear-gradient(135deg, #00f2fe, #4facfe); color: #07050d; font-weight: 800; margin-right: 6px; width: auto;" title="Move / Reallocate Seat">⇄ Move</button>`
                    : '';

                const actionsHtml = isTemp
                    ? `${moveBtn}<button onclick="openMakePermanentModal('${b.id}')" class="btn-primary" style="padding: 4px 10px; font-size: 0.75rem; background: var(--gold-gradient); color: #110e1c; font-weight: 800; margin-right: 6px; width: auto;">✨ Make Permanent</button>
                       <button onclick="confirmDeleteBooking('${b.id}')" class="logout-btn" style="padding: 4px 10px; font-size: 0.8rem; background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4);">Release</button>`
                    : `${moveBtn}<button onclick="copyCustomerSeatUrl('${encodeURIComponent(b.ticket_number)}')" class="btn-primary" style="padding: 4px 8px; font-size: 0.75rem; background: var(--gold-gradient); color: #110e1c; font-weight: 700; margin-right: 6px; width: auto;">🔗 Copy Link</button>
                       <button onclick="confirmDeleteBooking('${b.id}')" class="logout-btn" style="padding: 4px 10px; font-size: 0.8rem;">Delete</button>`;

                const catLabel = b.category === 'platinum' ? 'vip' : b.category;

                row.innerHTML = `
                    <td>${date}</td>
                    <td><span class="category-badge badge-${b.category}">${catLabel}</span></td>
                    <td>${statusBadge}</td>
                    <td>${escapeModalHtml(tableLabel)}</td>
                    <td>${escapeModalHtml(seatLabel)}</td>
                    <td><strong>${escapeModalHtml(b.name)}</strong></td>
                    <td>${escapeModalHtml(b.email || '-')}</td>
                    <td>${isTemp ? 'TEMP HOLD' : escapeModalHtml(b.ticket_number)}</td>
                    <td>${actionsHtml}</td>
                `;
                container.appendChild(row);
            });
        }

        function openSeatForMove(bookingId) {
            const b = reservationsList.find(r => String(r.id) === String(bookingId));
            if (!b || !b.table_number) return;
            openSeatingModal(b.category, parseInt(b.table_number, 10));
            showBubble(b);
        }

        function copyCustomerSeatUrl(ticketNumber) {
            const cleanTicket = decodeURIComponent(ticketNumber);
            const url = `${window.location.origin}/v2/seat/${encodeURIComponent(cleanTicket)}`;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => showToast('Customer seat link copied to clipboard!', 'success'));
            } else {
                prompt('Copy customer seat locator link:', url);
            }
        }

        function copyActiveSeatUrl() {
            if (!activeBooking || !activeBooking.ticket_number) return;
            copyCustomerSeatUrl(activeBooking.ticket_number);
        }

        function copySuccessCustomerUrl() {
            const input = document.getElementById('success-customer-url');
            if (!input || !input.value) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(() => showToast('Customer seat link copied to clipboard!', 'success'));
            } else {
                prompt('Copy customer seat locator link:', input.value);
            }
        }

        const BASE_MAP_WIDTH = 880;
        let currentScale = 1;
        let fitScale = 1;

        function computeFitScale() {
            const viewport = document.getElementById('v2-zoom-viewport');
            if (!viewport) return 1;
            const availW = Math.max(280, viewport.clientWidth - 4);
            return Math.min(1, availW / BASE_MAP_WIDTH);
        }

        function fitMapToViewport() {
            fitScale = computeFitScale();
            currentScale = fitScale;
            applyMapScale(true);
        }

        function stepMapZoom(delta) {
            fitScale = computeFitScale();
            const minScale = Math.min(0.35, fitScale);
            currentScale = Math.min(2.0, Math.max(minScale, +(currentScale + delta).toFixed(2)));
            applyMapScale(false);
        }

        function setMapZoomFromSlider(pctVal) {
            currentScale = Math.min(2.0, Math.max(0.35, parseInt(pctVal, 10) / 100));
            applyMapScale(false);
        }

        let cachedCanvasNaturalH = 0;
        function getCanvasNaturalHeight() {
            if (!cachedCanvasNaturalH) {
                const canvas = document.getElementById('v2-zoom-canvas');
                cachedCanvasNaturalH = (canvas && canvas.scrollHeight) ? canvas.scrollHeight : 1250;
            }
            return cachedCanvasNaturalH;
        }

        function applyMapScale(isFitMode = false) {
            const canvas = document.getElementById('v2-zoom-canvas');
            const sizer = document.getElementById('v2-zoom-sizer');
            const label = document.getElementById('map-zoom-label');
            const slider = document.getElementById('map-zoom-slider');
            if (!canvas || !sizer) return;

            canvas.style.transform = `scale(${currentScale})`;
            const naturalH = getCanvasNaturalHeight();
            sizer.style.width = `${Math.round(BASE_MAP_WIDTH * currentScale)}px`;
            sizer.style.height = `${Math.round(naturalH * currentScale)}px`;

            const pct = Math.round(currentScale * 100);
            if (slider && slider.value != pct) slider.value = pct;
            if (label) {
                label.innerText = (isFitMode || Math.abs(currentScale - fitScale) < 0.02)
                    ? `Fit (${pct}%)`
                    : `${pct}%`;
            }
        }

        let resizeRaf = null;
        window.addEventListener('resize', () => {
            if (resizeRaf) return;
            resizeRaf = requestAnimationFrame(() => {
                resizeRaf = null;
                cachedCanvasNaturalH = 0;
                const newFit = computeFitScale();
                if (Math.abs(currentScale - fitScale) < 0.03) {
                    fitScale = newFit;
                    currentScale = newFit;
                    applyMapScale(true);
                } else {
                    fitScale = newFit;
                    applyMapScale(false);
                }
            });
        }, { passive: true });

        function executeV2Search() {
            const input = document.getElementById('v2-search-input');
            currentSearch = (input ? input.value : '').trim();
            renderGrids();

            const clearBtn = document.getElementById('v2-search-clear-btn');
            const matchBadge = document.getElementById('v2-search-match-badge');

            if (currentSearch.length > 0) {
                if (clearBtn) clearBtn.style.display = 'inline-block';
                const matchedEls = document.querySelectorAll('.table-btn.search-matched-table');
                if (matchBadge) {
                    matchBadge.style.display = 'inline-block';
                    matchBadge.innerText = `${matchedEls.length} table(s) matched`;
                }
                if (matchedEls.length > 0) {
                    matchedEls[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    showToast(`No table or booking found matching "${currentSearch}"`, 'error');
                }
            } else {
                if (clearBtn) clearBtn.style.display = 'none';
                if (matchBadge) matchBadge.style.display = 'none';
            }
        }

        function clearV2Search() {
            const input = document.getElementById('v2-search-input');
            if (input) input.value = '';
            currentSearch = '';
            const clearBtn = document.getElementById('v2-search-clear-btn');
            const matchBadge = document.getElementById('v2-search-match-badge');
            if (clearBtn) clearBtn.style.display = 'none';
            if (matchBadge) matchBadge.style.display = 'none';
            renderGrids();
        }

        // Hide top navigation header when scrolling down, show when back near top
        let lastScrollY = window.scrollY;
        let scrollRaf = null;
        window.addEventListener('scroll', () => {
            if (scrollRaf) return;
            scrollRaf = requestAnimationFrame(() => {
                scrollRaf = null;
                const hdr = document.querySelector('header');
                if (!hdr) return;
                const currentY = window.scrollY;
                if (currentY > 40 && currentY > lastScrollY) {
                    hdr.classList.add('header-hidden');
                } else if (currentY <= 30 || currentY < lastScrollY - 15) {
                    hdr.classList.remove('header-hidden');
                }
                lastScrollY = currentY;
            });
        }, { passive: true });

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        function showLoader(msg = 'Processing...') {
            document.getElementById('loading-text').innerText = msg;
            document.getElementById('loading-overlay').classList.add('active');
        }
        function hideLoader() {
            document.getElementById('loading-overlay').classList.remove('active');
        }
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const card = document.createElement('div');
            card.className = `toast-card toast-${type}`;
            card.innerHTML = `<div>${type === 'success' ? '✓' : '✗'}</div><div>${message}</div>`;
            container.appendChild(card);
            setTimeout(() => card.classList.add('show'), 10);
            setTimeout(() => {
                card.classList.remove('show');
                setTimeout(() => card.remove(), 400);
            }, 4000);
        }

        document.addEventListener('DOMContentLoaded', fetchBookingsAndRender);
    </script>
</body>
</html>
