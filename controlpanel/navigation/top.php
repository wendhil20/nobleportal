<?php
//navigation/top.php
include ROOT_PATH . "/controlpanel/navigation/helpers/helpers.php";


$navUnreadNotifCount = 0;
if (isset($conn)) {
    $navAdminId = $_SESSION['admin_id'] ?? 0;
    $navAdminRole = $_SESSION['admin_role'] ?? '';
    $navAdminPosition = $_SESSION['admin_position'] ?? null;

    $navNotifStmt = $conn->prepare("SELECT COUNT(*) AS cnt
        FROM nobleportalnotification
        WHERE is_read = 0
  AND (
        (for_user_id = ? AND recipient_type = 'admin')
                OR (
                     (for_role IS NOT NULL OR for_position IS NOT NULL)
                     AND (for_role IS NULL OR for_role = ?)
                     AND (for_position IS NULL OR for_position = ?)
                   )
              )");
    $navNotifStmt->bind_param("iss", $navAdminId, $navAdminRole, $navAdminPosition);
    $navNotifStmt->execute();
    $navUnreadNotifCount = (int) ($navNotifStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $navNotifStmt->close();
}

if (!function_exists('isActive')) {
    function isActive($paths)
    {
        $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $current = rtrim($current, '/');

        // pwede nang array ng paths o single string
        $paths = is_array($paths) ? $paths : [$paths];

        foreach ($paths as $path) {
            $path = rtrim($path, '/');
            if ($path === '')
                continue;

            if ($current === $path || substr($current, -strlen($path)) === $path) {
                return true;
            }
        }
        return false;
    }
}

// Reusable class builders so we don't repeat the ternaries everywhere
function navLinkClass($path)
{
    return isActive($path)   // gagana na rin kahit array na yung ipasa mo
        ? 'group flex items-center gap-3 px-3 py-2.5 border-l-[3px] border-[#A9822C] rounded-r-lg bg-black/5 text-black transition-colors duration-150'
        : 'group flex items-center gap-3 px-3 py-2.5 border-l-[3px] border-transparent rounded-lg text-sm text-black/70 hover:bg-white/5 hover:text-black transition-colors duration-150';
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

    <!-- Brand header (navy zone so the mark reads immediately, mirrors mobile top bar) -->
    <div class="bg-black px-5 pt-6 pb-5 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shrink-0 shadow-sm">
            <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction" class="w-8 h-8 object-contain">
        </div>
        <div class="min-w-0">
            <p
                class="font-['Barlow_Condensed'] font-semibold text-[13px] leading-tight tracking-[0.06em] uppercase text-white truncate">
                <span class="text-amber-500">Noble</span>home ADMIN
            </p>
            <p
                class="font-['Barlow_Condensed'] font-medium text-[10px] leading-tight tracking-[0.16em] uppercase text-white truncate mt-0.5">
                Construction Corp.
            </p>
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
            </div>
            <svg id="profileDropdownChevron" xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5 text-black/40 shrink-0 transition-transform duration-200" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div id="profileDropdownMenu"
            class="hidden absolute left-4 right-4 top-[calc(100%-4px)] mt-1.5 rounded-lg border border-black/5 bg-white shadow-lg overflow-hidden z-50">
            <a href="<?= BASE_URL ?>/admin-logout"
                class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150">
                <i class="fa-solid fa-arrow-right-from-bracket text-[13px]"></i>
                <span class="text-xs font-medium">Logout</span>
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

                 <p class="px-3 py-2 text-[10px] font-semibold tracking-[0.15em] uppercase text-black/30">Management</p>

                <a href="<?= BASE_URL ?>/management-account" class="<?= navLinkClass('/management-account') ?>">
                    <span class="flex items-center justify-center w-5 h-5 shrink-0">
                        <i class="fa-solid fa-user-gear <?= navIconClass('/management-account') ?>"></i>
                    </span>
                    <span>Employee List</span>
                </a>

                <?php
                $employee201Pages = ['/hrpage-1', '/hr-employees', '/hr-orientation', 'view-information', ];
                ?>
                <a href="<?= BASE_URL ?>/hrpage-1" class="<?= navLinkClass($employee201Pages) ?>">
                    <span class="flex items-center justify-center w-5 h-5 shrink-0">
                        <i class="fa-solid fa-user-tag <?= navIconClass($employee201Pages) ?>"></i>
                    </span>
                    <span>Employee 201</span>
                </a>

                <a href="<?= BASE_URL ?>/admin-resignation" class="<?= navLinkClass('/admin-resignation') ?>">
                    <span class="flex items-center justify-center w-5 h-5 shrink-0">
                        <i class="fa-solid fa-file-circle-exclamation <?= navIconClass('/admin-resignation') ?>"></i>
                        
                    </span>
                    <span>Resignation list</span>
                </a>

                

            <?php endif; ?>

        <?php endif; ?>

        <p class="px-3 py-2 text-[10px] font-semibold tracking-[0.15em] uppercase text-black/30">Settings</p>

        <a href="<?= BASE_URL ?>/admin-notification" class="<?= navLinkClass('/admin-notification') ?>">
            <span class="relative flex items-center justify-center w-5 h-5 shrink-0">
                <i class="fa-solid fa-bell <?= navIconClass('/admin-notification') ?>"></i>
                <?php if ($navUnreadNotifCount > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                <?php endif; ?>
            </span>
            <span>Notification System</span>
        </a>

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