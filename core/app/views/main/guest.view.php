<x-layout-app title="Welcome">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-lg-8">
            <div class="text-center">
                <h1 class="display-4 mb-4">Welcome to Your Framework!</h1>

                <div class="alert alert-info">
                    <h4>Get Started</h4>
                    @if(user_content_visible())
                    <p class="mb-0">
                        @if(registration_enabled())
                        Please log in or register to access all features.
                        @else
                        Please log in to access available account features.
                        @endif
                    </p>
                    @else
                    <p class="mb-0">Account-related content is currently hidden from the public.</p>
                    @endif
                </div>

                <div class="mt-4">
                    @if(user_content_visible())
                    @if(registration_enabled())
                    <a href="/register" class="btn btn-primary btn-lg me-2">Register</a>
                    @endif
                    <a href="/login" class="btn btn-outline-primary btn-lg">Login</a>
                    @else
                    <a href="/about" class="btn btn-outline-primary btn-lg">Learn More</a>
                    @endif
                </div>

                <div class="card shadow mt-5">
                    <div class="card-body text-start">
                        <h5 class="card-title">Framework Features</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong>PhpTemplate:</strong> Modern templating with @if, @foreach, @{{ }} syntax
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong>Bootstrap 5:</strong> Responsive design out of the box
                            </li>
                            @if(user_content_visible())
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong>User Authentication:</strong> Complete login, register, and password recovery
                            </li>
                            @endif
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong>SCSS Support:</strong> Write your styles in SCSS
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong>Email Support:</strong> PHPMailer integrated for password recovery
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong>MVC Architecture:</strong> Clean and organized code structure
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-muted">
                        <small>
                            Read the <a href="/SETUP.md" target="_blank">SETUP.md</a> file for documentation and usage instructions.
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</x-layout-app>
