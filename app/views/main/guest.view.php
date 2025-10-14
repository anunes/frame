<x-layout-app title="Welcome">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-lg-8">
            <div class="text-center">
                <h1 class="display-4 mb-4">Welcome to Your Framework!</h1>

                <div class="alert alert-info">
                    <h4>Get Started</h4>
                    <p class="mb-0">Please log in or register to access all features.</p>
                </div>

                <div class="mt-4">
                    <a href="/register" class="btn btn-primary btn-lg me-2">Register</a>
                    <a href="/login" class="btn btn-outline-primary btn-lg">Login</a>
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
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <strong>User Authentication:</strong> Complete login, register, and password recovery
                            </li>
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
