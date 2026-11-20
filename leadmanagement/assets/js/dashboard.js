// assets/js/dashboard.js – Handles fetching stats and leads for the dashboard

const API_BASE = "api/"; // relative to dashboard page

// Helper for GET requests
async function fetchJSON(url) {
    const resp = await fetch(url);
    if (!resp.ok) throw new Error(`Network error: ${resp.status}`);
    return await resp.json();
}

// Render statistic cards with counter increment effect
function renderStats(stats) {
    animateCount('card-today', stats.leads_today);
    animateCount('card-total', stats.total_leads);
    document.getElementById('card-conversions').querySelector('p').textContent = `${stats.conversions}%`;
}

// Micro-animation for counts
function animateCount(id, targetValue) {
    const element = document.getElementById(id).querySelector('p');
    let start = 0;
    const duration = 800; // ms
    const increment = Math.ceil(targetValue / (duration / 16));
    
    if (targetValue === 0) {
        element.textContent = "0";
        return;
    }
    
    const timer = setInterval(() => {
        start += increment;
        if (start >= targetValue) {
            clearInterval(timer);
            element.textContent = targetValue;
        } else {
            element.textContent = start;
        }
    }, 16);
}

// Render leads table rows
function renderLeads(leads) {
    const tbody = document.getElementById('leads-body');
    tbody.innerHTML = '';
    
    if (leads.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="9" style="text-align: center; color: var(--text-light); padding: calc(var(--spacing) * 2);">No leads registered yet.</td></tr>`;
        return;
    }

    leads.forEach(lead => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td data-label="Enquiry Token"><span class="badge" style="background: var(--warning-light); color: #d97706; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-family: monospace;">${lead.token_no || '-'}</span></td>
            <td data-label="Name"><strong>${lead.name}</strong></td>
            <td data-label="Contact"><span class="badge" style="background: var(--primary-light); color: var(--primary-accent); padding: 4px 8px; border-radius: 6px; font-weight: 500;">${lead.contact}</span></td>
            <td data-label="State">${lead.state}</td>
            <td data-label="District">${lead.district}</td>
            <td data-label="City/Village">${lead.city}</td>
            <td data-label="Product"><span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #047857; padding: 4px 8px; border-radius: 6px; font-weight: 500;">${lead.product}</span></td>
            <td data-label="Quantity"><strong>${lead.quantity}</strong></td>
            <td data-label="Remark" style="color: var(--text-muted); font-size: 0.9rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${lead.remark || ''}">${lead.remark || '-'}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Real-time client-side search filtering
function setupRealtimeSearch() {
    const searchInput = document.getElementById('leadSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const term = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#leads-body tr');
            
            // If the empty row is present, skip
            if (rows.length === 1 && rows[0].cells.length === 1) return;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(term)) {
                    row.style.display = '';
                    row.style.animation = 'fadeIn 0.2s ease-in-out';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

async function loadDashboard() {
    try {
        const data = await fetchJSON(API_BASE + 'lead_stats.php');
        renderStats(data.stats);
        renderLeads(data.leads);
        setupRealtimeSearch();
    } catch (e) {
        console.error('Failed to load dashboard data:', e);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', loadDashboard);
