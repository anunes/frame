<?php ?><nav class="navbar navbar-expand-lg bg-body-tertiary fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <img src="<?php echo htmlspecialchars((site_logo()) ?? '', ENT_QUOTES, 'UTF-8'); ?>" alt="Logo" height="40" class="me-2">
            <span><?php echo htmlspecialchars((APP_NAME) ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php foreach (navbar_items('main') as $item): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo htmlspecialchars((navbar_item_active($item)) ?? '', ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars(($item['url']) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (isset($item['icon'])): ?>
                        <i class="<?php echo htmlspecialchars(($item['icon']) ?? '', ENT_QUOTES, 'UTF-8'); ?> me-1"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars(($item['label']) ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>



            
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (\app\core\Session::isLogged()): ?>
                <?php $userNavItems = navbar_items('user'); ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                        data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                        <?php if (\app\core\Session::user()->avatar): ?>
                        <img src="/avatars/<?php echo htmlspecialchars((\app\core\Session::user()->avatar) ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle me-2"
                            width="32" height="32" style="object-fit: cover;" alt="Avatar">
                        <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
                            style="width: 32px; height: 32px; font-size: 0.9rem;">
                            <?php echo htmlspecialchars((strtoupper(substr(\app\core\Session::user()->name, 0, 1))) ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <?php endif; ?>
                        <?php echo htmlspecialchars((\app\core\Session::user()->name) ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" data-bs-popper="static">
                        <?php foreach ($userNavItems as $item): ?>
                        <li>
                            <a class="dropdown-item" href="<?php echo htmlspecialchars(($item['url']) ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-href="<?php echo htmlspecialchars(($item['url']) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php if (isset($item['icon'])): ?>
                                <i class="<?php echo htmlspecialchars(($item['icon']) ?? '', ENT_QUOTES, 'UTF-8'); ?> me-2"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars(($item['label']) ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php if (!empty($userNavItems)): ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <?php endif; ?>
                        <li>
                            <form action="/logout" method="POST" class="m-0">
                                <?php echo csrf_field(); ?>
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
                <?php else: ?>
                
                <?php foreach (navbar_items('guest') as $item): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo htmlspecialchars((navbar_item_active($item)) ?? '', ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars(($item['url']) ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (isset($item['icon'])): ?>
                        <i class="<?php echo htmlspecialchars(($item['icon']) ?? '', ENT_QUOTES, 'UTF-8'); ?> me-1"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars(($item['label']) ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <?php if (user_content_hidden()): ?>
            <div class="theme-switcher dropdown ms-lg-3 mb-2 mb-lg-0">
                <button type="button"
                    class="nav-link theme-toggle dropdown-toggle d-inline-flex align-items-center"
                    data-theme-toggle
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Theme"
                    title="Theme">
                    <span data-theme-toggle-icon aria-hidden="true">
                        <i class="bi bi-circle-half"></i>
                    </span>
                    <span class="ms-2 d-lg-none">Theme</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-theme-value="light">
                            <i class="bi bi-sun-fill text-warning"></i>
                            <span>Light</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-theme-value="dark">
                            <i class="bi bi-moon-stars-fill text-primary"></i>
                            <span>Dark</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-theme-value="system">
                            <i class="bi bi-circle-half"></i>
                            <span>System</span>
                        </button>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
