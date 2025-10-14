<x-layout-app title="Login">
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Login</h2>

                <form action="/login" method="POST">
                    @php echo csrf_field(); @endphp

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <a href="/forgot-password" class="text-decoration-none">Forgot your password?</a>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>

                @if(registration_enabled())
                <div class="text-center mt-3">
                    <p class="mb-0">Don't have an account? <a href="/register">Register here</a></p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-layout-app>
