/**
 * KALABARA (කලාබර) - Universal Guest & Seat Search Engine (2D Floor Plan)
 * 100% Self-Hosted & Lightweight - Zero external libraries or 3D dependencies
 */
(function () {
    'use strict';

    let currentSearchQuery = '';
    let searchResults = [];
    let currentSearchIndex = 0;
    let searchDebounceTimer = null;

    /**
     * Handle input in search box with debouncing
     */
    window.handleSeatSearch = function (value) {
        if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            performSeatSearch(value);
        }, 120);
    };

    /**
     * Execute Seat Search across all reservations
     */
    function performSeatSearch(query) {
        currentSearchQuery = (query || '').trim();
        const clearBtn = document.getElementById('search-clear-btn');

        if (!currentSearchQuery) {
            clearSeatSearch();
            return;
        }

        if (clearBtn) clearBtn.style.display = 'block';

        const reservations = window.reservationsList || [];
        const q = currentSearchQuery.toLowerCase();

        // 1. Search in reservations database (Name, Email, Ticket / Order No, Table code)
        searchResults = [];

        reservations.forEach(res => {
            const nameMatch = res.name && res.name.toLowerCase().includes(q);
            const emailMatch = res.email && res.email.toLowerCase().includes(q);
            const ticketMatch = res.ticket_number && res.ticket_number.toLowerCase().includes(q);

            // Table matching e.g. "L26" or "table 26"
            const tableLabel = res.table_number ? `${res.side ? res.side.charAt(0) : ''}${res.table_number}`.toLowerCase() : '';
            const tableWord = res.table_number ? `table ${res.table_number}`.toLowerCase() : '';
            const tableMatch = (tableLabel && tableLabel === q) || (tableWord && tableWord === q);

            if (nameMatch || emailMatch || ticketMatch || tableMatch) {
                searchResults.push({
                    type: res.category === 'standing' ? 'standing' : 'table',
                    reservation: res,
                    side: res.side,
                    tableNumber: res.table_number,
                    seatNumber: res.seat_number,
                    isFullTable: res.is_full_table,
                    category: res.category,
                    matchReason: nameMatch ? 'Name' : (emailMatch ? 'Email' : (ticketMatch ? 'Order No' : 'Table'))
                });
            }
        });

        // 2. Also check if user typed a specific empty table directly, e.g. "L12" or "R50"
        const tableDirectMatch = q.match(/^([lr])\s*(\d+)$/i);
        if (tableDirectMatch) {
            const sideCode = tableDirectMatch[1].toLowerCase() === 'l' ? 'left' : 'right';
            const tableNum = parseInt(tableDirectMatch[2], 10);
            const cat = tableNum <= 54 ? 'platinum' : 'gold';

            const alreadyIn = searchResults.some(r => r.side === sideCode && parseInt(r.tableNumber, 10) === tableNum);
            if (!alreadyIn) {
                searchResults.push({
                    type: 'table',
                    reservation: null,
                    side: sideCode,
                    tableNumber: tableNum,
                    seatNumber: null,
                    isFullTable: false,
                    category: cat,
                    matchReason: 'Table Code'
                });
            }
        }

        currentSearchIndex = 0;

        // 3. Update UI Elements
        updateSearchUI();

        // 4. Apply Highlights to 2D Arena
        apply2DSearchHighlights();

        // 5. Scroll to first match
        if (searchResults.length > 0) {
            focusOnSearchMatch(0);
        }
    }

    /**
     * Clear Search and remove all highlights
     */
    window.clearSeatSearch = function () {
        currentSearchQuery = '';
        searchResults = [];
        currentSearchIndex = 0;

        const input = document.getElementById('seat-search-input');
        if (input) input.value = '';

        const clearBtn = document.getElementById('search-clear-btn');
        if (clearBtn) clearBtn.style.display = 'none';

        const statusBar = document.getElementById('search-status-bar');
        if (statusBar) statusBar.style.display = 'none';

        const dropdown = document.getElementById('search-dropdown-panel');
        if (dropdown) dropdown.style.display = 'none';

        // Clear 2D Highlights
        document.querySelectorAll('.table-btn.search-matched-table').forEach(el => {
            el.classList.remove('search-matched-table');
        });
        document.querySelectorAll('.standing-box.search-matched-standing').forEach(el => {
            el.classList.remove('search-matched-standing');
        });
    };

    /**
     * Update Search Counter & Dropdown Panel
     */
    function updateSearchUI() {
        const statusBar = document.getElementById('search-status-bar');
        const countEl = document.getElementById('search-match-count');
        const navBtns = document.getElementById('search-nav-btns');
        const stepIndexEl = document.getElementById('search-step-index');
        const dropdown = document.getElementById('search-dropdown-panel');
        const listEl = document.getElementById('search-dropdown-list');

        if (searchResults.length === 0) {
            if (statusBar) statusBar.style.display = 'flex';
            if (countEl) countEl.innerHTML = `<span style="color: #ff758f;">No seats found for "${escapeHtml(currentSearchQuery)}"</span>`;
            if (navBtns) navBtns.style.display = 'none';
            if (dropdown) dropdown.style.display = 'none';
            return;
        }

        if (statusBar) statusBar.style.display = 'flex';
        const countWord = searchResults.length === 1 ? 'result' : 'results';
        if (countEl) countEl.innerHTML = `<strong style="color: #00f2fe;">${searchResults.length}</strong> ${countWord} found`;
        if (navBtns) navBtns.style.display = searchResults.length > 1 ? 'inline-flex' : 'none';
        if (stepIndexEl) stepIndexEl.innerText = `${currentSearchIndex + 1} / ${searchResults.length}`;

        // Populate Dropdown Tray
        if (dropdown && listEl) {
            listEl.innerHTML = '';
            searchResults.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = `search-dropdown-item ${index === currentSearchIndex ? 'active' : ''}`;
                card.onclick = () => {
                    focusOnSearchMatch(index);
                };

                const res = item.reservation;
                let locationBadge = '';
                if (item.type === 'standing') {
                    locationBadge = `♣ Standing (${item.side.toUpperCase()})`;
                } else {
                    const sideLetter = item.side.toUpperCase().substring(0, 1);
                    const seatPart = item.isFullTable ? 'Full Table (8 Seats)' : (item.seatNumber ? `Seat ${item.seatNumber}` : 'Table');
                    locationBadge = `Table ${sideLetter}${item.tableNumber} • ${seatPart}`;
                }

                card.innerHTML = `
                    <div class="search-item-header">
                        <span class="search-item-name">${res ? escapeHtml(res.name) : 'Table ' + item.tableNumber}</span>
                        <span class="search-item-badge">${locationBadge}</span>
                    </div>
                    <div class="search-item-sub">
                        <span>${res && res.ticket_number ? 'Order No: ' + escapeHtml(res.ticket_number) : ''}</span>
                        <span>${res && res.email ? escapeHtml(res.email) : ''}</span>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 6px;">
                        ${res && res.ticket_number ? `<button type="button" class="search-view-btn" style="background: rgba(245, 207, 109, 0.2); border-color: #f5cf6d; color: #f5cf6d;" onclick="event.stopPropagation(); window.copyCustomerSeatUrl('${encodeURIComponent(res.ticket_number)}');">🔗 Copy Link</button>` : ''}
                        <button type="button" class="search-view-btn" onclick="event.stopPropagation(); window.openMatchedModal(${index});">Inspect / View &rarr;</button>
                    </div>
                `;
                listEl.appendChild(card);
            });
            dropdown.style.display = 'block';
        }
    }

    window.openMatchedModal = function (index) {
        if (index < 0 || index >= searchResults.length) return;
        const match = searchResults[index];
        focusOnSearchMatch(index);

        if (match.type === 'table') {
            if (typeof window.openSeatingModal === 'function') {
                window.openSeatingModal(match.category, match.side, match.tableNumber);
            }
        } else if (match.type === 'standing') {
            if (typeof window.openStandingBooking === 'function') {
                window.openStandingBooking(match.side);
            }
        }
    };

    window.closeSearchDropdown = function () {
        const dropdown = document.getElementById('search-dropdown-panel');
        if (dropdown) dropdown.style.display = 'none';
    };

    /**
     * Navigate through multiple search matches (Next / Prev)
     */
    window.stepSearchMatch = function (direction) {
        if (searchResults.length === 0) return;
        currentSearchIndex = (currentSearchIndex + direction + searchResults.length) % searchResults.length;
        focusOnSearchMatch(currentSearchIndex);
    };

    /**
     * Focus on specific search match (Scroll to 2D element)
     */
    function focusOnSearchMatch(index) {
        if (index < 0 || index >= searchResults.length) return;
        currentSearchIndex = index;

        const stepIndexEl = document.getElementById('search-step-index');
        if (stepIndexEl) stepIndexEl.innerText = `${currentSearchIndex + 1} / ${searchResults.length}`;

        document.querySelectorAll('.search-dropdown-item').forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });

        scroll2DToMatch(index);
    }

    /**
     * Apply Highlights to 2D Floor Plan
     */
    function apply2DSearchHighlights() {
        document.querySelectorAll('.table-btn.search-matched-table').forEach(el => {
            el.classList.remove('search-matched-table');
        });
        document.querySelectorAll('.standing-box.search-matched-standing').forEach(el => {
            el.classList.remove('search-matched-standing');
        });

        if (searchResults.length === 0) return;

        searchResults.forEach(match => {
            if (match.type === 'table') {
                const btnId = `table-btn-${match.side}-${match.tableNumber}`;
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.classList.add('search-matched-table');
                }
            } else if (match.type === 'standing') {
                const standingEl = document.getElementById(`standing-box-${match.side}`);
                if (standingEl) {
                    standingEl.classList.add('search-matched-standing');
                }
            }
        });
    }

    /**
     * Smoothly scroll 2D table into view
     */
    function scroll2DToMatch(index) {
        if (index < 0 || index >= searchResults.length) return;
        const match = searchResults[index];
        let el = null;

        if (match.type === 'table') {
            el = document.getElementById(`table-btn-${match.side}-${match.tableNumber}`);
        } else if (match.type === 'standing') {
            el = document.getElementById(`standing-box-${match.side}`);
        }

        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
})();
