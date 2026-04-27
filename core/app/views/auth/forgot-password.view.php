<x-layout-app title="Forgot Password">
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Forgot Password</h2>

                <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>

                <form action="/forgot-password" method="POST">
                    @php echo csrf_field(); @endphp

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        <a href="/login" class="btn btn-secondary">Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layout-app>
