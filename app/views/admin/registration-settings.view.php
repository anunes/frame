<form action="/admin/registration-settings" method="POST">
    @php echo csrf_field(); @endphp

    <h5 class="mb-3">User Registration</h5>

    <div class="mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input"
                   type="checkbox"
                   id="registration_enabled"
                   name="registration_enabled"
                   {{ $registrationEnabled ? 'checked' : '' }}>
            <label class="form-check-label" for="registration_enabled">
                <strong>Allow New User Registrations</strong>
            </label>
        </div>
        <small class="text-muted">When disabled, users will not be able to create new accounts. Existing users can still log in.</small>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Save Settings
        </button>
    </div>
</form>
