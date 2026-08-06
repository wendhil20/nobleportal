<?php
//navigation/top.php
include ROOT_PATH . "/controlpanel/navigation/helpers/helpers.php";

// Determine current page for active-state highlighting
if (!function_exists('isActive')) {
    function isActive($path)
    {
        $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $current = rtrim($current, '/');
        $path = rtrim($path, '/');
        if ($path === '') {
            return false;
        }
        // Works whether the app sits at the domain root or in a subfolder
        // (e.g. /nobleportal/admin-register still matches "/admin-register")
        return $current === $path || substr($current, -strlen($path)) === $path;
    }
}

// Reusable class builders so we don't repeat the ternaries everywhere
function navLinkClass($path)
{
    return isActive($path)
        ? 'group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium bg-black/5 text-black transition-colors duration-150'
        : 'group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-black/70 hover:bg-white/5 hover:text-black transition-colors duration-150';
}

function navIconClass($path)
{
    return isActive($path)
        ? 'h-[12px] w-[10px] shrink-0 text-black transition'
        : 'h-[12px] w-[10px] shrink-0 text-black/40 group-hover:text-black/80 transition';
}
?>
<!-- Overlay -->
<div id="sidebarOverlay"
    class="fixed inset-0 bg-black/50 backdrop-blur-[2px] z-40 hidden transition-opacity duration-300 opacity-0 lg:hidden">
</div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-screen w-64 bg-white text-white z-50
           transform -translate-x-full transition-transform duration-300 ease-in-out
           flex flex-col border-r border-gray-200 ">

    <!-- Toggle Handle -->
    <button id="sidebarToggle" class="absolute top-1/2 left-full -translate-y-1/2
           bg-white hover:bg-gray-200 text-black
           w-8 h-17 flex items-center justify-center
           rounded-r-lg
           transition-colors duration-200 border-r border-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <!-- Header / Brand -->
    <div class="relative px-5 py-6 border-b border-white/5">
        <div class="relative z-10 flex items-center gap-3">
            <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction"
                class="w-9 h-9 object-contain shrink-0">
            <div class="flex flex-col leading-tight">
                <span
                    class="font-['Barlow_Condensed'] font-semibold text-base tracking-[0.1em] uppercase text-black/90">
                    Noblehome
                </span>
                <span
                    class="font-['Barlow_Condensed'] font-medium text-[11px] tracking-[0.15em] uppercase text-black/50">
                    Construction Corp.
                </span>
            </div>
        </div>
    </div>

    <!-- Profile Dropdown -->
    <div class="relative px-4 py-3 border-b border-black/5">
        <button id="profileDropdownToggle" type="button"
            class="w-full flex items-center gap-3 rounded-lg px-1 py-1.5 hover:bg-black/5 transition-colors duration-150">
            <div
                class="w-8 h-8 rounded-full bg-black/10 flex items-center justify-center text-xs font-semibold text-black/70 shrink-0">
                <?= isset($_SESSION['admin_name']) ? strtoupper(substr($_SESSION['admin_name'], 0, 1)) : '?' ?>
            </div>
            <div class="flex flex-col leading-tight min-w-0 flex-1 text-left">
                <span class="text-xs font-medium text-black/80 truncate">
                    <?= isset($_SESSION['admin_name']) ? htmlspecialchars($_SESSION['admin_name']) : 'Admin' ?>
                </span>
                <span class="text-[10px] text-black/40">
                    &copy; <?php echo date('Y'); ?> Company Name
                </span>
            </div>
            <svg id="profileDropdownChevron" xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5 text-black/40 shrink-0 transition-transform duration-200" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div id="profileDropdownMenu"
            class="hidden mt-1.5 rounded-lg border border-black/5 bg-white shadow-sm overflow-hidden">
            <a href="<?= BASE_URL ?>/admin-logout"
                class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150">
                <i class="fa-solid fa-arrow-right-from-bracket text-[13px]"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="mx-4 mt-2 border-b border-black/5"></div>

    <!-- Menu items -->
    <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-1">

        <?php if (hasAccess('hr')): ?>

            <?php if ($_SESSION['admin_position'] === 'head'): ?>

                <p class="px-3 mb-2 text-[10px] font-semibold tracking-[0.15em] uppercase text-black/30">Main</p>

                <a href="<?= BASE_URL ?>/admin-register" class="<?= navLinkClass('/admin-register') ?>">
                    <span class="flex items-center justify-center w-5 h-5 shrink-0">
                        <i class="fa-solid fa-users-rays <?= navIconClass('/admin-register') ?>"></i>
                    </span>
                    <span>Add Account Admin</span>
                </a>

                <a href="<?= BASE_URL ?>/hrpage-2" class="<?= navLinkClass('/hrpage-2') ?>">
                    <span class="flex items-center justify-center w-5 h-5 shrink-0">
                        <i class="fa-solid fa-user-plus <?= navIconClass('/hrpage-2') ?>"></i>
                    </span>
                    <span>Register Employee</span>
                </a>

                <a href="<?= BASE_URL ?>/hrpage-1" class="<?= navLinkClass('/hrpage-1') ?>">
                    <span class="flex items-center justify-center w-5 h-5 shrink-0">
                        <i class="fa-solid fa-user-tag <?= navIconClass('/hrpage-1') ?>"></i>
                    </span>
                    <span>Employee 201</span>
                </a>

            <?php endif; ?>

        <?php endif; ?>

    </nav>

</aside>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');
    let isOpen = false;

    const SIDEBAR_STATE_KEY = 'sidebarOpen';

    function getMainContent() {
        return document.getElementById('mainContent');
    }

    function openSidebar(persist = true) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        requestAnimationFrame(() => overlay.classList.remove('opacity-0'));

        const main = getMainContent();
        if (main) {
            main.classList.add('lg:ml-64');
        }
        isOpen = true;

        if (persist) {
            localStorage.setItem(SIDEBAR_STATE_KEY, '1');
        }
    }

    function closeSidebar(persist = true) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(() => overlay.classList.add('hidden'), 300);

        const main = getMainContent();
        if (main) {
            main.classList.remove('lg:ml-64');
        }
        isOpen = false;

        if (persist) {
            localStorage.setItem(SIDEBAR_STATE_KEY, '0');
        }
    }

    toggleBtn.addEventListener('click', () => {
        isOpen ? closeSidebar() : openSidebar();
    });

    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    // ==== RESTORE SIDEBAR STATE ON PAGE LOAD ====

    (function restoreSidebarState() {
        const savedState = localStorage.getItem(SIDEBAR_STATE_KEY);
        const isDesktop = window.matchMedia('(min-width: 1024px)').matches;

        if (savedState === '1' && isDesktop) {
            openSidebar(false);
        } else {
            closeSidebar(false);
        }
    })();

    // ==== PROFILE DROPDOWN ====
    const profileDropdownToggle = document.getElementById('profileDropdownToggle');
    const profileDropdownMenu = document.getElementById('profileDropdownMenu');
    const profileDropdownChevron = document.getElementById('profileDropdownChevron');

    profileDropdownToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdownMenu.classList.toggle('hidden');
        profileDropdownChevron.classList.toggle('rotate-180');
    });

    document.addEventListener('click', (e) => {
        if (!profileDropdownToggle.contains(e.target) && !profileDropdownMenu.contains(e.target)) {
            profileDropdownMenu.classList.add('hidden');
            profileDropdownChevron.classList.remove('rotate-180');
        }
    });
</script>