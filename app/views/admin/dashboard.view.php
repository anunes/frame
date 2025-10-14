<x-layout-app title="Admin Panel">
<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-shield-lock"></i> Administration Panel
        </h2>

        <div class="accordion" id="adminAccordion">
            <!-- User Management Section -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingUsers">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers" aria-expanded="true" aria-controls="collapseUsers">
                        <i class="bi bi-people me-2"></i> User Management
                    </button>
                </h2>
                <div id="collapseUsers" class="accordion-collapse collapse show" aria-labelledby="headingUsers" data-bs-parent="#adminAccordion">
                    <div class="accordion-body">
                        <div id="userManagementContainer">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Settings Section -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSettings">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSettings" aria-expanded="false" aria-controls="collapseSettings">
                        <i class="bi bi-gear me-2"></i> Contact Settings
                    </button>
                </h2>
                <div id="collapseSettings" class="accordion-collapse collapse" aria-labelledby="headingSettings" data-bs-parent="#adminAccordion">
                    <div class="accordion-body">
                        <div id="settingsContainer">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Settings Section -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingRegistration">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegistration" aria-expanded="false" aria-controls="collapseRegistration">
                        <i class="bi bi-person-plus me-2"></i> User Registration
                    </button>
                </h2>
                <div id="collapseRegistration" class="accordion-collapse collapse" aria-labelledby="headingRegistration" data-bs-parent="#adminAccordion">
                    <div class="accordion-body">
                        <div id="registrationContainer">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="bi bi-exclamation-triangle"></i> Delete User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                <p>Are you sure you want to permanently delete user <strong id="deleteUserName"></strong>?</p>
                <p class="mb-0">This will remove all user data from the database.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash"></i> Yes, Delete User
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize user management state
window.adminUsers = {
    currentPage: 1,
    currentPerPage: '5',
    currentSearch: '',
    currentStatus: 'active'
};

// Define all user management functions globally
window.goToPage = function(page) {
    console.log('goToPage called:', page);
    window.adminUsers.currentPage = page;
    window.loadUsers();
};

window.changePerPage = function(perPage) {
    console.log('changePerPage called:', perPage);
    window.adminUsers.currentPerPage = perPage;
    window.adminUsers.currentPage = 1;
    window.loadUsers();
};

window.searchUsers = function() {
    console.log('searchUsers called');
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        window.adminUsers.currentSearch = searchInput.value;
    }
    window.adminUsers.currentPage = 1;
    window.loadUsers();
};

window.changeStatus = function(status) {
    console.log('changeStatus called with:', status);
    window.adminUsers.currentStatus = status;
    window.adminUsers.currentPage = 1;
    window.loadUsers();
};

window.loadUsers = function() {
    const url = `/admin/users?page=${window.adminUsers.currentPage}&per_page=${window.adminUsers.currentPerPage}&search=${encodeURIComponent(window.adminUsers.currentSearch)}&status=${window.adminUsers.currentStatus}`;
    console.log('Loading users with URL:', url);

    fetch(url)
        .then(response => response.text())
        .then(html => {
            console.log('Users loaded successfully');
            document.getElementById('userManagementContainer').innerHTML = html;

            // Re-attach enter key listener for search
            const searchInput = document.getElementById('userSearch');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        window.searchUsers();
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            document.getElementById('userManagementContainer').innerHTML = '<div class="alert alert-danger">Failed to load users</div>';
        });
};

window.toggleUserStatus = function(userId, active, csrfToken) {
    console.log('toggleUserStatus called:', { userId, active, csrfToken });

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('active', active ? 1 : 0);
    formData.append('csrf_token', csrfToken);

    fetch('/admin/users/toggle-status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response content-type:', response.headers.get('content-type'));
        return response.text(); // Get as text first to see what we're receiving
    })
    .then(text => {
        // Trim any whitespace
        text = text.trim();

        try {
            const data = JSON.parse(text);
            if (data.success) {
                // Success - status updated
                console.log('User status updated successfully');
            } else {
                alert('Failed to update user status: ' + (data.message || 'Unknown error'));
                window.loadUsers();
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            alert('An error occurred while updating user status');
            window.loadUsers();
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred: ' + error.message);
        window.loadUsers();
    });
};

// Delete user functionality
window.confirmDeleteUser = function(userId, userName, csrfToken) {
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    document.getElementById('deleteUserName').textContent = userName;

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.onclick = function() {
        deleteUser(userId, csrfToken);
        modal.hide();
    };

    modal.show();
};

window.deleteUser = async function(userId, csrfToken) {
    try {
        const response = await fetch('/admin/users/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId,
                csrf_token: csrfToken
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('User deleted successfully');
            if (window.loadUsers) {
                window.loadUsers();
            }
        } else {
            alert('Failed to delete user: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        alert('An error occurred while deleting the user');
    }
};

// Load user management section on page load
document.addEventListener('DOMContentLoaded', function() {
    window.loadUsers();

    // Load settings when accordion is opened
    document.getElementById('collapseSettings').addEventListener('show.bs.collapse', function() {
        if (document.querySelector('#settingsContainer .spinner-border')) {
            loadSettings();
        }
    });

    // Load registration settings when accordion is opened
    document.getElementById('collapseRegistration').addEventListener('show.bs.collapse', function() {
        if (document.querySelector('#registrationContainer .spinner-border')) {
            loadRegistrationSettings();
        }
    });
});

function loadSettings() {
    fetch('/admin/settings')
        .then(response => response.text())
        .then(html => {
            document.getElementById('settingsContainer').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('settingsContainer').innerHTML = '<div class="alert alert-danger">Failed to load settings</div>';
        });
}

function loadRegistrationSettings() {
    fetch('/admin/registration-settings')
        .then(response => response.text())
        .then(html => {
            document.getElementById('registrationContainer').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('registrationContainer').innerHTML = '<div class="alert alert-danger">Failed to load registration settings</div>';
        });
}
</script>
</x-layout-app>
