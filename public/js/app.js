document.addEventListener('DOMContentLoaded', () => {
    // Dynamic Filter & Search Handling
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const url = new URL(window.location.href);
                if (e.target.value.trim() !== '') {
                    url.searchParams.set('search', e.target.value.trim());
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }, 500);
        });
    }

    // Quick Status Update on Ticket View Page
    const statusSelect = document.getElementById('ticket-status-select');
    if (statusSelect) {
        statusSelect.addEventListener('change', async (e) => {
            const ticketId = statusSelect.dataset.ticketId;
            const newStatus = e.target.value;

            try {
                const response = await fetch('ticket_view.php?id=' + ticketId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update_status&status=${encodeURIComponent(newStatus)}`
                });
                
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Failed to update ticket status.');
                }
            } catch (err) {
                console.error(err);
                alert('Error updating status');
            }
        });
    }

    // Quick Priority Update
    const prioritySelect = document.getElementById('ticket-priority-select');
    if (prioritySelect) {
        prioritySelect.addEventListener('change', async (e) => {
            const ticketId = prioritySelect.dataset.ticketId;
            const newPriority = e.target.value;

            try {
                const response = await fetch('ticket_view.php?id=' + ticketId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update_priority&priority=${encodeURIComponent(newPriority)}`
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    // File input preview on Simulator form
    const fileInput = document.getElementById('simulator-file-input');
    const fileListDisplay = document.getElementById('file-list-preview');

    if (fileInput && fileListDisplay) {
        fileInput.addEventListener('change', (e) => {
            fileListDisplay.innerHTML = '';
            const files = Array.from(e.target.files);
            
            if (files.length === 0) return;

            files.forEach(f => {
                const item = document.createElement('div');
                item.className = 'attachment-card';
                item.style.padding = '0.5rem 0.8rem';
                item.innerHTML = `
                    <span class="att-icon">📎</span>
                    <div class="att-info">
                        <div class="att-name">${f.name}</div>
                        <div class="att-size">${(f.size / 1024).toFixed(1)} KB</div>
                    </div>
                `;
                fileListDisplay.appendChild(item);
            });
        });
    }
});
