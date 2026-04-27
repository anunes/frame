<x-layout-app title="Edit Profile">
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title mb-4">Edit Profile</h2>

                <form action="/profile/edit" method="POST" enctype="multipart/form-data">
                    @php echo csrf_field(); @endphp

                    <div class="text-center mb-4">
                        @if(\app\core\Session::user()->avatar)
                            <img src="/avatars/{{ \app\core\Session::user()->avatar }}"
                                 class="rounded-circle mb-3"
                                 id="avatarPreview"
                                 style="width: 120px; height: 120px; object-fit: cover;"
                                 alt="Avatar">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                                 id="avatarPlaceholder"
                                 style="width: 120px; height: 120px; font-size: 2.5rem;">
                                {{ strtoupper(substr(\app\core\Session::user()->name, 0, 1)) }}
                            </div>
                            <img src="" class="rounded-circle mb-3 d-none"
                                 id="avatarPreview"
                                 style="width: 120px; height: 120px; object-fit: cover;"
                                 alt="Avatar">
                        @endif

                        <div>
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
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text"
                               class="form-control"
                               id="name"
                               name="name"
                               value="{{ \app\core\Session::user()->name }}"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               value="{{ \app\core\Session::user()->email }}"
                               required>
                    </div>

                    <div class="alert alert-info">
                        <small>
                            <i class="bi bi-info-circle"></i>
                            To change your password, use the <a href="/change-password">Change Password</a> page.
                        </small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                        <a href="/profile" class="btn btn-secondary">Cancel</a>
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
