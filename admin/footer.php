        </main>
    </div>

    <!-- Logout Confirmation Modal (shared across all admin pages) -->
    <div id="logoutModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:2000;">
        <div class="modal-content" style="background:#fff; padding:20px; border-radius:8px; width:400px; max-width:95%; text-align:center;">
            <div style="color:#ff9800; font-size:48px; margin-bottom:15px;">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3 style="color:#333; margin-bottom:15px;">Confirm Logout</h3>
            <p style="margin-bottom:20px; color:#666;">Are you sure you want to logout? You will need to login again to access the admin panel.</p>
            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" class="btn btn-secondary" id="cancelLogout">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmLogout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>
    
    <script>
    // Confirm before delete actions
    function confirmDelete(message = 'Are you sure you want to delete this item?') {
        return confirm(message);
    }
    
    // Real-time updates for dashboard
    function updateDashboardStats() {
        fetch('../api/dashboard-stats.php')
            .then(response => response.json())
            .then(data => {
                if (!data || data.status !== 'success' || !data.stats) {
                    return;
                }

                document.querySelectorAll('[data-stat]').forEach(node => {
                    const statKey = node.getAttribute('data-stat');
                    if (Object.prototype.hasOwnProperty.call(data.stats, statKey)) {
                        node.textContent = data.stats[statKey];
                    }
                });
            });
    }
    
    // Update every 30 seconds
    setInterval(updateDashboardStats, 30000);

    // Shared logout modal handlers (sidebar + header logout buttons)
    document.addEventListener('DOMContentLoaded', function() {
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogoutBtn = document.getElementById('cancelLogout');
        const confirmLogoutBtn = document.getElementById('confirmLogout');

        if (cancelLogoutBtn && logoutModal) {
            cancelLogoutBtn.addEventListener('click', function() {
                logoutModal.style.display = 'none';
            });
        }

        if (confirmLogoutBtn) {
            confirmLogoutBtn.addEventListener('click', function() {
                window.location.href = '../login/logout.php';
            });
        }

        if (logoutModal) {
            logoutModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        }
    });

    // Exposed function used by all logout links
    function showLogoutModal() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.style.display = 'flex';
        }
        return false; // Prevent default navigation
    }

    function confirmLogout() {
        return showLogoutModal();
    }
    </script>
</body>
</html>
