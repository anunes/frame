<x-layout-app title="Create New User">
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="card-title mb-0">Create New User</h2>
                    <a href="/admin" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Admin
                    </a>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <small>
                        A random 6-character password will be generated and sent to the user's email.
                        The user will be required to change their password on first login.
                    </small>
                </div>

                <form action="/admin/users/create" method="POST">
                    @php echo csrf_field(); @endphp

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control"
                               id="name"
                               name="name"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="0">User</option>
                            <option value="1">Administrator</option>
                        </select>
                    </div>

                    <hr class="my-4">

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Create User & Send Email
                        </button>
                        <a href="/admin" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layout-app>
