        </div>
    </main>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <!-- Logout Confirmation Modal (Passenger) -->
    <div id="logoutModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:3000;">
        <div class="modal-content" style="background:#fff; padding:20px; border-radius:8px; width:400px; max-width:95%; text-align:center;">
            <div style="color:var(--primary-pink); font-size:48px; margin-bottom:15px;">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3 style="color:var(--dark-gray); margin-bottom:15px;">Confirm Logout</h3>
            <p style="margin-bottom:20px; color:var(--text-color);">Are you sure you want to logout? You will need to login again to access your passenger dashboard.</p>
            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" class="btn btn-secondary" id="cancelLogout">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmLogout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </div>

    <script>
    // Global functions
    function showLoading() {
        document.getElementById('loadingSpinner').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loadingSpinner').style.display = 'none';
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    // Update vehicle locations every 30 seconds
    function updateVehicleLocations() {
        if (typeof updateMap === 'function') {
            updateMap();
        }
    }

    setInterval(updateVehicleLocations, 30000);

    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateVehicleLocations();
        }
    });

    // Shared logout modal handlers (user side)
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

    function showLogoutModal() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.style.display = 'flex';
        }
        return false;
    }

    function confirmLogout() {
        return showLogoutModal();
    }
    </script>
</body>
</html>