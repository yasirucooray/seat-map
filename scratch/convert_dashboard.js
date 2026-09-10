import fs from 'fs';
import path from 'path';

const ejsPath = 'views/dashboard.ejs';
const bladePath = 'resources/views/dashboard.blade.php';

// Ensure directory exists
const dir = path.dirname(bladePath);
if (!fs.existsSync(dir)){
    fs.mkdirSync(dir, { recursive: true });
}

let content = fs.readFileSync(ejsPath, 'utf8');

// Replace view stats
content = content.replace(/<%= stats.platinum.percentage %>%/g, "{{ $stats['platinum']['percentage'] }}%");
content = content.replace(/<%= stats.platinum.sold %>/g, "{{ $stats['platinum']['sold'] }}");
content = content.replace(/<%= stats.platinum.capacity %>/g, "{{ $stats['platinum']['capacity'] }}");
content = content.replace(/<%= stats.gold.percentage %>%/g, "{{ $stats['gold']['percentage'] }}%");
content = content.replace(/<%= stats.gold.sold %>/g, "{{ $stats['gold']['sold'] }}");
content = content.replace(/<%= stats.gold.capacity %>/g, "{{ $stats['gold']['capacity'] }}");
content = content.replace(/<%= stats.standing.sold %>/g, "{{ $stats['standing']['sold'] }}");

// Add CSRF meta tag
content = content.replace(
    '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
    '<meta name="viewport" content="width=device-width, initial-scale=1.0">\n    <meta name="csrf-token" content="{{ csrf_token() }}">'
);

// Update logout form
content = content.replace(
    '<form action="/logout" method="POST">',
    '<form action="{{ route(\'logout\') }}" method="POST">\n                @csrf'
);

// Replace EJS loop with Blade loop for recent bookings
const startTbody = '<tbody id="recent-bookings-list">';
const endTbody = '</tbody>';
const tbodyStartIndex = content.indexOf(startTbody);
const tbodyEndIndex = content.indexOf(endTbody, tbodyStartIndex);

if (tbodyStartIndex !== -1 && tbodyEndIndex !== -1) {
    const originalTbody = content.substring(tbodyStartIndex, tbodyEndIndex + endTbody.length);
    const bladeTbody = `<tbody id="recent-bookings-list">
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
                                    <button onclick="confirmDeleteBooking('{{ $booking->id }}')" class="logout-btn" style="padding: 4px 10px; font-size: 0.8rem; border-color: rgba(255, 0, 85, 0.4); color: var(--neon-red);">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted);">No reservations found.</td>
                        </tr>
                    @endif
                </tbody>`;
    content = content.replace(originalTbody, bladeTbody);
}

// Add CSRF headers in Javascript Fetch requests
// 1. POST /api/bookings headers:
content = content.replace(
    `                const response = await fetch('/api/bookings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },`,
    `                const response = await fetch('/api/bookings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },`
);

// 2. DELETE /api/bookings/${id} headers:
content = content.replace(
    `                const response = await fetch(\`/api/bookings/\${id}\`, {
                    method: 'DELETE',
                });`,
    `                const response = await fetch(\`/api/bookings/\${id}\`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });`
);

fs.writeFileSync(bladePath, content, 'utf8');
console.log('Successfully converted dashboard.ejs to dashboard.blade.php!');
