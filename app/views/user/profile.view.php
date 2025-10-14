<x-layout-app title="My Profile">

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title mb-4">My Profile</h2>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile"
                            type="button" role="tab" aria-controls="profile" aria-selected="true">
                            <i class="bi bi-person"></i> Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password"
                            type="button" role="tab" aria-controls="password" aria-selected="false">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="theme-tab" data-bs-toggle="tab" data-bs-target="#theme"
                            type="button" role="tab" aria-controls="theme" aria-selected="false">
                            <i class="bi bi-palette"></i> Theme
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content border border-top-0 rounded-end rounded-bottom p-4" id="profileTabsContent" style="margin-top: -1px;">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <div class="mb-3">
                                    @if(\app\core\Session::user()->avatar)
                                    <img src="/avatars/{{ \app\core\Session::user()->avatar }}"
                                        class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;"
                                        alt="Profile Avatar">
                                    @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto"
                                        style="width: 150px; height: 150px; font-size: 3rem;">
                                        {{ strtoupper(substr(\app\core\Session::user()->name, 0, 1)) }}
                                    </div>
                                    @endif
                                </div>
                                <h4>{{ \app\core\Session::user()->name }}</h4>
                                <p class="text-muted">
                                    @if(\app\core\Session::user()->isAdmin())
                                    <span class="badge bg-danger">Administrator</span>
                                    @else
                                    <span class="badge bg-primary">User</span>
                                    @endif
                                </p>

                                <div class="mt-4 pt-4">
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#deleteAccountModal">
                                        <i class="bi bi-trash"></i> Delete Account?
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <h5 class="mb-4">Account Information</h5>

                                <div class="mb-3">
                                    <label for="profileName" class="form-label fw-bold">Name</label>
                                    <input type="text" class="form-control" id="profileName"
                                        value="{{ \app\core\Session::user()->name }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="profileEmail" class="form-label fw-bold">Email Address</label>
                                    <input type="email" class="form-control" id="profileEmail"
                                        value="{{ \app\core\Session::user()->email }}" readonly>
                                </div>
                                <!-- 
                                <div class="mb-3">
                                    <label for="profileRole" class="form-label fw-bold">Account Type</label>
                                    <input type="text"
                                           class="form-control"
                                           id="profileRole"
                                           value="@if(\app\core\Session::user()->isAdmin())Administrator @else Standard User @endif"
                                           readonly>
                                </div> -->

                                <hr class="my-4">

                                <div class="d-grid gap-2">
                                    <a href="/profile/edit" class="btn btn-primary">
                                        <i class="bi bi-pencil"></i> Edit Profile
                                    </a>
                                    <a href="/" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Home
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password Tab -->
                    <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <h5 class="mb-4">Change Your Password</h5>

                                <form action="/change-password" method="POST">
                                    @php echo csrf_field(); @endphp

                                    <div class="mb-3">
                                        <label for="old_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="old_password"
                                            name="old_password" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password"
                                            name="new_password" required minlength="6">
                                        <div class="form-text">Password must be at least 6 characters long (letters and
                                            numbers only)</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password"
                                            name="confirm_password" required minlength="6">
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg"></i> Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Theme Tab -->
                    <div class="tab-pane fade" id="theme" role="tabpanel" aria-labelledby="theme-tab">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <h5 class="mb-4">Theme Preferences</h5>
                                <p class="text-muted mb-4">Choose how the site appears to you.</p>

                                <div class="list-group">
                                    <label class="list-group-item d-flex gap-3 align-items-center"
                                        style="cursor: pointer;">
                                        <input class="form-check-input flex-shrink-0" type="radio" name="theme"
                                            value="light" id="theme-light">
                                        <div class="d-flex gap-3 w-100 align-items-center">
                                            <i class="bi bi-sun-fill text-warning fs-4"></i>
                                            <div>
                                                <strong class="mb-0">Light</strong>
                                                <small class="d-block text-muted">Bright and clean interface</small>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="list-group-item d-flex gap-3 align-items-center"
                                        style="cursor: pointer;">
                                        <input class="form-check-input flex-shrink-0" type="radio" name="theme"
                                            value="dark" id="theme-dark">
                                        <div class="d-flex gap-3 w-100 align-items-center">
                                            <i class="bi bi-moon-stars-fill text-primary fs-4"></i>
                                            <div>
                                                <strong class="mb-0">Dark</strong>
                                                <small class="d-block text-muted">Easy on the eyes in low light</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                <div class="alert alert-info mt-4">
                                    <i class="bi bi-info-circle"></i>
                                    <small>Your theme preference is saved locally in your browser.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Theme switching functionality
    (function () {
        const themeRadios = document.querySelectorAll('input[name="theme"]');

        // Get stored theme or default to 'light'
        const getStoredTheme = () => localStorage.getItem('theme') || 'light';
        const setStoredTheme = theme => localStorage.setItem('theme', theme);

        // Apply theme to document
        const setTheme = theme => {
            document.documentElement.setAttribute('data-bs-theme', theme);
        };

        // Initialize theme on page load
        const storedTheme = getStoredTheme();
        setTheme(storedTheme);

        // Set the correct radio button
        const radioToCheck = document.getElementById(`theme-${storedTheme}`);
        if (radioToCheck) {
            radioToCheck.checked = true;
        }

        // Listen for theme changes
        themeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const theme = e.target.value;
                setStoredTheme(theme);
                setTheme(theme);
            });
        });
    })();
</script>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="bi bi-exclamation-triangle"></i> Delete Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i>
                    <strong>Warning:</strong> This action will deactivate your account.
                </div>
                <p>Are you sure you want to delete your account? This will:</p>
                <ul>
                    <li>Deactivate your account immediately</li>
                    <li>Log you out of the system</li>
                    <li>Prevent you from logging in again</li>
                </ul>
                <p class="mb-0"><strong>This action cannot be undone by you.</strong> Contact an administrator if you
                    need to reactivate your account.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="/profile/delete" method="POST" style="display: inline;">
                    @php echo csrf_field(); @endphp
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Yes, Delete My Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layout-app>