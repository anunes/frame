<x-layout-app title="Edit User">
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="card-title mb-0">Edit User</h2>
                    <a href="/admin" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Admin
                    </a>
                </div>

                <form action="/admin/users/{{ $user->id }}" method="POST" enctype="multipart/form-data">
                    @php echo csrf_field(); @endphp

                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <div class="mb-3">
                                @if($user->avatar)
                                    <img src="/avatars/{{ $user->avatar }}"
                                         class="rounded-circle mb-3"
                                         id="avatarPreview"
                                         style="width: 150px; height: 150px; object-fit: cover;"
                                         alt="Avatar">
                                @else
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                                         id="avatarPlaceholder"
                                         style="width: 150px; height: 150px; font-size: 3rem;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <img src="" class="rounded-circle mb-3 d-none"
                                         id="avatarPreview"
                                         style="width: 150px; height: 150px; object-fit: cover;"
                                         alt="Avatar">
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="avatar" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-camera"></i> Change Avatar
                                </label>
                                <input type="file"
                                       class="d-none"
                                       id="avatar"
                                       name="avatar"
                                       accept="image/*"
                                       onchange="previewAvatar(this)">
                                <div class="form-text">Max 5MB (JPG, PNG, GIF, WebP)</div>
                            </div>

                            <p class="text-muted">
                                <small>User ID: #{{ $user->id }}</small><br>
                                <small>Registered: {{ date('M d, Y', strtotime($user->created_at)) }}</small>
                            </p>
                        </div>

                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text"
                                       class="form-control"
                                       id="name"
                                       name="name"
                                       value="{{ $user->name }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email"
                                       class="form-control"
                                       id="email"
                                       name="email"
                                       value="{{ $user->email }}"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="0" {{ $user->role == 0 ? 'selected' : '' }}>User</option>
                                    <option value="1" {{ $user->role == 1 ? 'selected' : '' }}>Administrator</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="active"
                                           name="active"
                                           {{ $user->active == 1 ? 'checked' : '' }}
                                           {{ $user->id == \app\core\Session::user()->id ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="active">
                                        Account Active
                                    </label>
                                </div>
                                @if($user->id == \app\core\Session::user()->id)
                                    <small class="text-muted">You cannot deactivate your own account</small>
                                @endif
                            </div>

                            <hr class="my-4">

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                                <a href="/admin" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            const placeholder = document.getElementById('avatarPlaceholder');

            preview.src = e.target.result;
            preview.classList.remove('d-none');

            if (placeholder) {
                placeholder.classList.add('d-none');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</x-layout-app>
