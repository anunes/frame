<form action="/admin/settings" method="POST" enctype="multipart/form-data">
    @php echo csrf_field(); @endphp

    <h5 class="mb-3">Site Logo</h5>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Current Logo</label>
                <div class="border rounded p-3 bg-light text-center">
                    <img src="{{ $logo }}" alt="Current Logo" style="max-height: 60px; max-width: 100%;">
                </div>
            </div>

            <div class="mb-3">
                <label for="logo" class="form-label">Upload New Logo</label>
                <input type="file"
                       class="form-control"
                       id="logo"
                       name="logo"
                       accept="image/*">
                <small class="text-muted">Max 2MB (JPG, PNG, GIF, WebP, SVG)</small>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <h5 class="mb-3">Contact Information</h5>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="company_name" class="form-label">Company Name</label>
            <input type="text"
                   class="form-control"
                   id="company_name"
                   name="company_name"
                   value="{{ $settings->company_name }}">
        </div>

        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email"
                   class="form-control"
                   id="email"
                   name="email"
                   value="{{ $settings->email }}">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text"
                   class="form-control"
                   id="phone"
                   name="phone"
                   value="{{ $settings->phone }}">
        </div>

        <div class="col-md-6 mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text"
                   class="form-control"
                   id="address"
                   name="address"
                   value="{{ $settings->address }}">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="postal_code" class="form-label">Postal Code</label>
            <input type="text"
                   class="form-control"
                   id="postal_code"
                   name="postal_code"
                   value="{{ $settings->postal_code }}">
        </div>

        <div class="col-md-6 mb-3">
            <label for="city" class="form-label">City</label>
            <input type="text"
                   class="form-control"
                   id="city"
                   name="city"
                   value="{{ $settings->city }}">
        </div>
    </div>

    <hr class="my-4">

    <h5 class="mb-3">Copyright Text</h5>

    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="copyright_text" class="form-label">Footer Copyright Text</label>
            <input type="text"
                   class="form-control"
                   id="copyright_text"
                   name="copyright_text"
                   value="{{ $copyrightText }}"
                   placeholder="&copy; 2006-{{ date('Y') }} @nunes.net">
            <small class="text-muted">Use {year} to automatically insert the current year</small>
        </div>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Save Settings
        </button>
    </div>
</form>
