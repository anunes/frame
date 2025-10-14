<x-layout-app title="Change Password">
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Change Password</h2>

                <form action="/change-password" method="POST">
                    @php echo csrf_field(); @endphp

                    <div class="mb-3">
                        <label for="old_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="old_password" name="old_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        <div class="form-text">Password must be at least 6 characters long</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                        <a href="/" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layout-app>
