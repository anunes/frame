<nav class="navbar navbar-expand-lg bg-body-tertiary fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <img src="{{ site_logo() }}" alt="Logo" height="40" class="me-2">
            <span>Framework</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            {{-- Main navigation links from config --}}
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                @foreach(navbar_items('main') as $item)
                <li class="nav-item">
                    <a class="nav-link {{ navbar_item_active($item) }}" href="{{ $item['url'] }}">
                        @if(isset($item['icon']))
                        <i class="{{ $item['icon'] }} me-1"></i>
                        @endif
                        {{ $item['label'] }}
                    </a>
                </li>
                @endforeach
            </ul>



            {{-- User menu or guest links --}}
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                @if(\app\core\Session::isLogged())
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                        data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                        @if(\app\core\Session::user()->avatar)
                        <img src="/avatars/{{ \app\core\Session::user()->avatar }}" class="rounded-circle me-2"
                            width="32" height="32" style="object-fit: cover;" alt="Avatar">
                        @else
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
                            style="width: 32px; height: 32px; font-size: 0.9rem;">
                            {{ strtoupper(substr(\app\core\Session::user()->name, 0, 1)) }}
                        </div>
                        @endif
                        {{ \app\core\Session::user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" data-bs-popper="static">
                        <li>
                            <a class="dropdown-item" href="/profile" data-href="/profile">
                                <i class="bi-person me-2"></i>Profile
                            </a>
                        </li>
                        @if(\app\core\Session::user()->isAdmin())
                        <li>
                            <a class="dropdown-item" href="/admin" data-href="/admin">
                                <i class="bi-gear me-2"></i>Administration
                            </a>
                        </li>
                        @endif
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form action="/logout" method="POST" class="m-0">
                                @php echo csrf_field(); @endphp
                                <button type="submit"
                                    class="dropdown-item text-start w-100 border-0 bg-transparent p-0">
                                    <span class="dropdown-item d-block">
                                        <i class="bi-box-arrow-right me-2"></i>Logout
                                    </span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
                @else
                {{-- Guest links from config --}}
                @foreach(navbar_items('guest') as $item)
                <li class="nav-item">
                    <a class="nav-link {{ navbar_item_active($item) }}" href="{{ $item['url'] }}">
                        @if(isset($item['icon']))
                        <i class="{{ $item['icon'] }} me-1"></i>
                        @endif
                        {{ $item['label'] }}
                    </a>
                </li>
                @endforeach
                @endif
            </ul>
        </div>
    </div>
</nav>