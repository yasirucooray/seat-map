<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KALABARA • V2 Stage Map (Tables 1–130) QR Scanner &amp; Check-In</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root {
            --bg-dark: #07050d;
            --bg-card: rgba(18, 14, 28, 0.88);
            --gold-primary: #f5cf6d;
            --gold-gradient: linear-gradient(135deg, #ffe89a 0%, #d4af37 50%, #aa7c11 100%);
            --gold-glow: rgba(212, 175, 55, 0.35);
            --border-gold: rgba(212, 175, 55, 0.3);
            --neon-green: #39ff14;
            --ruby-red: #e63946;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
        }
        header {
            background: rgba(12, 10, 20, 0.92);
            border-bottom: 1px solid var(--border-gold);
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .brand-title {
            font-family: 'Cinzel', serif;
            font-size: 1.45rem;
            font-weight: 900;
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .nav-btn {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none;
            color: #07050d;
            background: var(--gold-gradient);
        }
        .container {
            max-width: 1380px;
            margin: 24px auto;
            padding: 0 20px 60px;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-box {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 16px;
            padding: 18px 20px;
        }
        .stat-label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            font-weight: 700;
        }
        .stat-num {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            font-weight: 800;
            margin-top: 4px;
        }
        .scanner-grid {
            display: grid;
            grid-template-columns: 390px 1fr;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .scanner-grid { grid-template-columns: 1fr; }
        }
        .panel-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 20px;
            padding: 22px;
        }
        #qr-reader {
            width: 100%;
            border-radius: 14px;
            overflow: hidden;
            background: #000;
            margin-bottom: 14px;
        }
        .input-row {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        .input-row input {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-gold);
            border-radius: 10px;
            padding: 10px 14px;
            color: #fff;
            font-size: 0.9rem;
        }
        .btn-gold {
            background: var(--gold-gradient);
            color: #07050d;
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 800;
            cursor: pointer;
        }
        .btn-checkin-big {
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: none;
            font-size: 1rem;
            font-weight: 900;
            cursor: pointer;
            background: linear-gradient(135deg, #39ff14 0%, #10b981 100%);
            color: #07050d;
            box-shadow: 0 0 25px rgba(57, 255, 20, 0.4);
            margin-top: 14px;
        }

        /* 8-Seat Table Ring */
        .seats-container {
            position: relative;
            width: 260px;
            height: 260px;
            margin: 16px auto;
            background: rgba(255, 255, 255, 0.02);
            border: 2px dashed rgba(212, 175, 55, 0.25);
            border-radius: 50%;
        }
        .modal-table-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 68px;
            height: 68px;
            background: radial-gradient(circle, #251f38 0%, #110e1c 100%);
            border: 2px solid var(--gold-primary);
            box-shadow: 0 0 20px var(--gold-glow);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Cinzel', serif;
            font-weight: 800;
            color: var(--gold-primary);
            font-size: 1.05rem;
        }
        .seat-circle {
            position: absolute;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.03);
            color: var(--text-muted);
        }
        .seat-circle.guest-seat {
            border: 2px solid var(--gold-primary);
            background: var(--gold-gradient);
            color: #07050d;
            font-weight: 900;
            box-shadow: 0 0 16px var(--gold-glow);
        }
        .seat-circle.checked-in-seat {
            border: 2px solid var(--neon-green) !important;
            background: rgba(16, 185, 129, 0.38) !important;
            color: #fff !important;
            box-shadow: 0 0 14px rgba(57, 255, 20, 0.65) !important;
            font-size: 0.68rem;
            line-height: 1.05;
        }

        /* Mini 13x10 Tables Map (Tables 1-130) */
        .mini-venue {
            background: rgba(10, 8, 18, 0.65);
            border: 1px solid var(--border-gold);
            border-radius: 16px;
            padding: 16px;
            margin-top: 18px;
        }
        .mini-stage {
            background: linear-gradient(180deg, rgba(212, 175, 55, 0.14), rgba(18, 15, 28, 0.9));
            border: 1.5px solid var(--gold-primary);
            text-align: center;
            padding: 10px;
            font-family: 'Cinzel', serif;
            font-weight: 900;
            letter-spacing: 0.25em;
            color: var(--gold-primary);
            border-radius: 10px;
            margin-bottom: 12px;
        }
        .mini-row {
            display: grid;
            grid-template-columns: 32px repeat(10, 1fr);
            gap: 6px;
            align-items: center;
            margin-bottom: 6px;
        }
        .mini-row-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(212, 175, 55, 0.14);
            border: 1px solid var(--gold-primary);
            color: var(--gold-primary);
            font-weight: 800;
            font-size: 0.72rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mini-table-btn {
            height: 28px;
            border-radius: 6px;
            background: rgba(57, 255, 20, 0.04);
            border: 1px solid rgba(57, 255, 20, 0.25);
            color: #8ade80;
            font-size: 0.68rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mini-table-btn.target-table {
            background: var(--gold-gradient) !important;
            color: #07050d !important;
            border: 2px solid #fff !important;
            font-weight: 900;
            box-shadow: 0 0 18px var(--gold-glow);
            transform: scale(1.14);
            z-index: 5;
        }
    </style>
</head>
<body>
    <header>
        <div class="brand-title">📷 V2 STAGE MAP (TABLES 1–130) • QR SCANNER &amp; CHECK-IN</div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="{{ route('v2.dashboard') }}" class="nav-btn">⬅ 13×10 Stage Seat Map</a>
            <form action="/logout" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="nav-btn" style="background: rgba(230, 57, 70, 0.2); border: 1px solid var(--ruby-red); color: #fff; cursor: pointer;">Log Out</button>
            </form>
        </div>
    </header>

    <div class="container">
        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-label">PERMANENT SOLD (V2)</div>
                <div class="stat-num" id="s-total-sold">{{ $stats['total_sold'] }}</div>
            </div>
            <div class="stat-box" style="border-color:rgba(57,255,20,0.5);">
                <div class="stat-label" style="color:var(--neon-green);">CHECKED-IN COUNT</div>
                <div class="stat-num" style="color:var(--neon-green);" id="s-checked-in">{{ $stats['checked_in'] }}</div>
            </div>
            <div class="stat-box" style="border-color:rgba(0,242,254,0.5);">
                <div class="stat-label" style="color:#00f2fe;">REMAINING COUNTDOWN</div>
                <div class="stat-num" style="color:#00f2fe;" id="s-remaining">{{ $stats['remaining'] }}</div>
            </div>
        </div>

        <div class="scanner-grid">
            <!-- Left: Camera / Upload / Manual Input -->
            <div class="panel-card">
                <h3 style="margin-top:0;color:var(--gold-primary);">Scan Entry Ticket QR</h3>
                <div id="qr-reader"></div>
                <div style="display:flex;gap:8px;margin-bottom:12px;">
                    <button type="button" class="btn-gold" style="flex:1;" onclick="startCamera()">📷 Start Camera</button>
                    <label class="btn-gold" style="flex:1;text-align:center;cursor:pointer;">
                        🖼️ Upload QR Image
                        <input type="file" accept="image/*" style="display:none;" onchange="scanImageFile(this)">
                    </label>
                </div>

                <form onsubmit="handleManualLookup(event)">
                    <label style="font-size:0.76rem;color:var(--text-muted);text-transform:uppercase;font-weight:700;">Or Enter Order / Ticket No:</label>
                    <div class="input-row">
                        <input type="text" id="manual-code-input" value="{{ $initialQuery }}" placeholder="e.g. ORDMTU76ZQFUYH58C">
                        <button type="submit" class="btn-gold">Find</button>
                    </div>
                </form>
            </div>

            <!-- Right: Result, 8-Seat Table Ring & 13x10 Map Highlight -->
            <div class="panel-card">
                <div id="lookup-result-box" style="display:none;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,0.1);">
                        <div>
                            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Guest Name</div>
                            <div style="font-size:1.45rem;font-weight:800;color:var(--gold-primary);" id="res-guest-name"></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Order Number</div>
                            <div style="font-size:1.05rem;font-family:monospace;color:#00f2fe;font-weight:700;" id="res-order-no"></div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-top:14px;">
                        <div style="background:rgba(255,255,255,0.04);padding:10px 14px;border-radius:10px;">
                            <div style="font-size:0.72rem;color:var(--text-muted);">SECTION</div>
                            <div style="font-size:1.05rem;font-weight:800;" id="res-category"></div>
                        </div>
                        <div style="background:rgba(255,255,255,0.04);padding:10px 14px;border-radius:10px;">
                            <div style="font-size:0.72rem;color:var(--text-muted);">TABLE &amp; SEATS</div>
                            <div style="font-size:1.05rem;font-weight:800;color:var(--gold-primary);" id="res-location"></div>
                        </div>
                        <div style="background:rgba(255,255,255,0.04);padding:10px 14px;border-radius:10px;">
                            <div style="font-size:0.72rem;color:var(--text-muted);">ORDER CHECK-IN COUNTDOWN</div>
                            <div style="font-size:1.05rem;font-weight:800;color:var(--neon-green);" id="res-order-countdown"></div>
                        </div>
                    </div>

                    <!-- 8-Seat Table Ring (Shown for Seated Bookings) -->
                    <div id="ring-wrapper" style="display:none;text-align:center;">
                        <div class="seats-container">
                            <div class="modal-table-center" id="ring-table-center">1</div>
                            @for ($s = 1; $s <= 8; $s++)
                                <div class="seat-circle" id="ring-seat-{{ $s }}">{{ $s }}</div>
                            @endfor
                        </div>
                    </div>

                    <button type="button" class="btn-checkin-big" id="btn-mark-order-checkin" onclick="markCurrentOrderCheckIn()">
                        🔒 Mark as Checked-In
                    </button>
                </div>

                <div id="lookup-empty-box" style="text-align:center;padding:18px;color:var(--text-muted);">
                    Scan a ticket QR code or enter an Order Number to locate the table (1–130) and seats (1–8).
                </div>

                <!-- 13x10 Tables Map Visual Locator (Tables 1-130) -->
                <div class="mini-venue">
                    <div class="mini-stage">♠ STAGE (66 FT) ♠</div>
                    <div id="mini-grid-rows"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Position 8 seats around the ring
        const radius = 92;
        const angles = [0, 45, 90, 135, 180, 225, 270, 315];
        angles.forEach((angle, idx) => {
            const s = idx + 1;
            const el = document.getElementById(`ring-seat-${s}`);
            const rad = (angle - 90) * (Math.PI / 180);
            const x = Math.round(radius * Math.cos(rad));
            const y = Math.round(radius * Math.sin(rad));
            el.style.left = `${130 + x - 20}px`;
            el.style.top = `${130 + y - 20}px`;
        });

        let currentBooking = null;
        let html5QrScanner = null;

        function renderMiniMap() {
            const container = document.getElementById('mini-grid-rows');
            container.innerHTML = '';

            const targetTable = currentBooking && currentBooking.is_seated ? parseInt(currentBooking.table_number, 10) : null;

            for (let r = 1; r <= 13; r++) {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'mini-row';

                const num = document.createElement('div');
                num.className = 'mini-row-num';
                num.innerText = r;
                rowDiv.appendChild(num);

                for (let c = 1; c <= 10; c++) {
                    const tNum = ((r - 1) * 10) + c;
                    const tDiv = document.createElement('div');
                    tDiv.className = 'mini-table-btn';
                    if (targetTable === tNum) {
                        tDiv.classList.add('target-table');
                        tDiv.innerText = (currentBooking.all_checked_in ? '🔒' : '') + tNum;
                    } else {
                        tDiv.innerText = tNum;
                    }
                    rowDiv.appendChild(tDiv);
                }

                container.appendChild(rowDiv);
            }
        }

        function renderSeatRing(b) {
            const wrap = document.getElementById('ring-wrapper');
            if (!b || !b.is_seated || !b.table_number) {
                wrap.style.display = 'none';
                return;
            }
            wrap.style.display = 'block';
            document.getElementById('ring-table-center').innerText = b.table_number;

            const guestSeats = b.seats || [];
            const tableCheckedIn = b.table_checked_in_seats || [];

            for (let s = 1; s <= 8; s++) {
                const el = document.getElementById(`ring-seat-${s}`);
                el.className = 'seat-circle';
                const isGuest = guestSeats.includes(s);
                const isCheckedIn = tableCheckedIn.includes(s);

                if (isGuest) el.classList.add('guest-seat');
                if (isCheckedIn) el.classList.add('checked-in-seat');

                // Lock icon ONLY for checked-in seated users
                el.innerHTML = isCheckedIn ? `<span>🔒</span><span>${s}</span>` : `${s}`;
            }
        }

        async function lookupCode(rawText) {
            try {
                const res = await fetch('/api/v2/check-in/lookup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ qr_data: rawText })
                });
                const data = await res.json();
                if (data.stats) updateStatsBar(data.stats);

                if (data.found && data.booking) {
                    currentBooking = data.booking;
                    showBookingResult(data.booking);
                } else {
                    currentBooking = null;
                    document.getElementById('lookup-result-box').style.display = 'none';
                    const emptyBox = document.getElementById('lookup-empty-box');
                    emptyBox.style.display = 'block';
                    emptyBox.innerHTML = `<span style="color:#ff4d6d;font-weight:700;">❌ ${data.message || 'Order not found.'}</span>`;
                }
                renderMiniMap();
            } catch (err) {
                console.error(err);
            }
        }

        function showBookingResult(b) {
            document.getElementById('lookup-empty-box').style.display = 'none';
            document.getElementById('lookup-result-box').style.display = 'block';
            document.getElementById('res-guest-name').innerText = b.name;
            document.getElementById('res-order-no').innerText = b.ticket_number;
            document.getElementById('res-category').innerText = b.category.toUpperCase();

            if (b.is_seated) {
                const seatDesc = b.is_full_table ? 'Full Table (All 8 Seats)' : `Seats: ${(b.seats || []).join(', ')}`;
                document.getElementById('res-location').innerText = `Table ${b.table_number} (Row ${b.row_number}) • ${seatDesc}`;
            } else {
                document.getElementById('res-location').innerText = `${(b.side || 'left').toUpperCase()} Standing Area (${b.total_tickets} Ticket(s))`;
            }

            document.getElementById('res-order-countdown').innerText = `${b.checked_in_tickets} / ${b.total_tickets} Checked In (${b.remaining_tickets} Left)`;
            renderSeatRing(b);

            const btn = document.getElementById('btn-mark-order-checkin');
            const lockPrefix = b.is_seated ? '🔒 ' : '✓ ';
            if (b.all_checked_in) {
                btn.innerText = `${lockPrefix}All Checked-In! (Click to Undo)`;
                btn.style.background = 'rgba(255,255,255,0.12)';
                btn.style.color = '#fff';
            } else {
                btn.innerText = `${lockPrefix}Mark as Checked-In (${b.remaining_tickets} Remaining)`;
                btn.style.background = 'linear-gradient(135deg, #39ff14 0%, #10b981 100%)';
                btn.style.color = '#07050d';
            }
        }

        async function markCurrentOrderCheckIn() {
            if (!currentBooking) return;
            const newStatus = !currentBooking.all_checked_in;
            try {
                const res = await fetch('/api/v2/check-in/mark', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        ticket_number: currentBooking.ticket_number,
                        status: newStatus
                    })
                });
                const data = await res.json();
                if (data.success) {
                    currentBooking = data.booking;
                    if (data.stats) updateStatsBar(data.stats);
                    showBookingResult(currentBooking);
                    renderMiniMap();
                }
            } catch (err) {
                console.error(err);
            }
        }

        function updateStatsBar(s) {
            document.getElementById('s-total-sold').innerText = s.total_sold;
            document.getElementById('s-checked-in').innerText = s.checked_in;
            document.getElementById('s-remaining').innerText = s.remaining;
        }

        function handleManualLookup(e) {
            e.preventDefault();
            const val = document.getElementById('manual-code-input').value.trim();
            if (val) lookupCode(val);
        }

        function startCamera() {
            if (html5QrScanner) return;
            html5QrScanner = new Html5Qrcode("qr-reader");
            html5QrScanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (decodedText) => lookupCode(decodedText),
                () => {}
            ).catch(err => alert('Camera error: ' + err));
        }

        function scanImageFile(input) {
            if (!input.files || !input.files[0]) return;
            const scanner = new Html5Qrcode("qr-reader");
            scanner.scanFile(input.files[0], true)
                .then(decodedText => lookupCode(decodedText))
                .catch(() => alert('Could not decode QR code from image.'));
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderMiniMap();
            const initQ = document.getElementById('manual-code-input').value.trim();
            if (initQ) lookupCode(initQ);
        });
    </script>
</body>
</html>
