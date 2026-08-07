<?php
//top.php
include_once ROOT_PATH . "/network/connect.php";

$userStatus = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT status FROM nobleuser_employee_information WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $userStatus = $row['status'] ?? null;
    $stmt->close();
}
?>

<!-- ==== DESKTOP SIDEBAR ==== -->
<aside
    class="hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0 md:w-64 md:z-50 bg-white border-r border-black/5">

    <!-- Brand header (navy zone so the mark reads immediately, mirrors mobile top bar) -->
    <div class="bg-black px-5 pt-6 pb-5 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shrink-0 shadow-sm">
            <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction" class="w-8 h-8 object-contain">
        </div>
        <div class="min-w-0">
            <p
                class="font-['Barlow_Condensed'] font-semibold text-[13px] leading-tight tracking-[0.06em] uppercase text-white truncate">
                <span class="text-amber-500">Noble</span>home
            </p>
            <p
                class="font-['Barlow_Condensed'] font-medium text-[10px] leading-tight tracking-[0.16em] uppercase text-white truncate mt-0.5">
                Construction Corp.
            </p>
        </div>
    </div>

    <!-- Profile card -->
    <div class="px-4 pt-4 pb-2 relative">
        <!-- Dropdown -->
        <div id="profileDropdown"
            class="hidden absolute left-4 right-4 top-[calc(100%-2px)] mt-1 bg-white rounded-lg shadow-lg border border-[#EDEFF1] overflow-hidden z-50">
            <a href="<?= BASE_URL ?>/logout/"
                class="block px-3 py-2.5 text-xs text-[#1B2733] hover:bg-[#F5F6F7] hover:text-red-500 transition-colors">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </a>
        </div>

        <button type="button" onclick="document.getElementById('profileDropdown').classList.toggle('hidden')"
            class="w-full flex items-center gap-2.5 px-2.5 py-2.5 rounded-lg border border-black/[0.06] hover:border-black/10 hover:bg-[#F9FAFA] transition-colors">
            <div
                class="w-8 h-8 rounded-full bg-[#A9822C] text-white flex items-center justify-center text-[11px] font-semibold shrink-0 relative">
                <?= isset($_SESSION['first_name']) ? strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1)) : 'NH' ?>

                <?php if (strtoupper($userStatus ?? '') === 'APPROVED'): ?>
                    <span
                        class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-green-500 border-2 border-white flex items-center justify-center">
                        <i class="fa-solid fa-check text-[7px] text-white"></i>
                    </span>
                <?php endif; ?>
            </div>
            <div class="min-w-0 flex-1 text-left">
                <p class="text-[13px] font-semibold text-[#1B2733] truncate leading-tight">
                    <?= isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? '')) : 'Guest' ?>
                </p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[#C7CCD1] shrink-0" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m6 9 6 6 6-6"></path>
            </svg>
        </button>
    </div>

    <div class="mx-4 mt-2 border-b border-black/5"></div>

    <!-- Nav links -->
    <nav class="flex-1 flex flex-col px-3 pt-5 pb-4">
        <p class="px-3.5 pb-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-[#9AA2AA]">Menu</p>
        <div class="flex flex-col gap-0.5">
            <a href="<?= BASE_URL ?>/page-1"
                class="nav-link flex items-center gap-3 pl-3 pr-3.5 py-2.5 border-l-[3px] border-transparent rounded-r-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <i class="fa-solid fa-tachograph-digital text-black"></i>
                Create Employee Profile
            </a>

            <a href="<?= BASE_URL ?>/page-2"
                class="nav-link flex items-center gap-3 pl-3 pr-3.5 py-2.5 border-l-[3px] border-transparent rounded-r-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <i class="fa-solid fa-user-clock text-black"></i>
                Employment Status
            </a>

        </div>

        <p class="px-3.5 pb-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-[#9AA2AA]">Settings</p>

        <div class="flex flex-col gap-0.5">
             <a href="<?= BASE_URL ?>/notification"
                class="nav-link flex items-center gap-3 pl-3 pr-3.5 py-2.5 border-l-[3px] border-transparent rounded-r-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <i class="fa-solid fa-message text-black"></i>
                 Notification System
            </a>

            <a href="<?= BASE_URL ?>/page-5"
                class="nav-link flex items-center gap-3 pl-3 pr-3.5 py-2.5 border-l-[3px] border-transparent rounded-r-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <i class="fa-solid fa-circle-info text-black"></i>
                Term & Policy
            </a>

        </div>
    </nav>

</aside>

<!-- ==== MOBILE TOP BAR (brand only) ==== -->
<nav class="md:hidden sticky top-0 z-50 bg-black text-[#EDEFEF] shadow-sm">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16">

            <div class="flex items-center gap-2.5">
                <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction"
                    class="w-8 h-8 object-contain">
                <span
                    class="font-['Barlow_Condensed'] font-semibold text-xs tracking-[0.1em] uppercase text-[#EDEFEF]/90">
                    <span class="text-amber-500">Noble</span>home Construction Corp.
                </span>
            </div>

        </div>
    </div>
</nav>

<!-- ==== BOTTOM TAB BAR (mobile only, app-style like TikTok Shop) ==== -->
<div id="bottomNav"
    class="md:hidden fixed bottom-0 inset-x-0 z-50 bg-white border-t border-black/10 shadow-[0_-2px_10px_rgba(0,0,0,0.04)]"
    style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="flex items-stretch justify-between px-1">

        <a href="<?= BASE_URL ?>/page-1"
            class="bottom-tab flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 px-1 text-[#9AA2AA]">
            <i class="fa-solid fa-tachograph-digital w-[18px] text-center"></i>
            <span class="text-[9px] font-medium leading-[1.1] text-center line-clamp-2">
                Create E & P
            </span>
        </a>

        <a href="<?= BASE_URL ?>/controlpanel/employees/"
            class="bottom-tab flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[#9AA2AA]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span class="text-[10px] font-medium">Employees</span>
        </a>

        <!-- Center action button (TikTok Shop style raised icon) -->
        <a href="<?= BASE_URL ?>/controlpanel/projects/"
            class="flex-1 flex flex-col items-center justify-center relative">
            <span
                class="absolute -top-5 w-12 h-12 rounded-full bg-[#A9822C] flex items-center justify-center shadow-md border-4 border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#0B2540]" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="7" width="18" height="14" rx="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </span>
            <span class="text-[10px] font-medium text-[#9AA2AA] mt-6">Projects</span>
        </a>

        <a href="<?= BASE_URL ?>/controlpanel/reports/"
            class="bottom-tab flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[#9AA2AA]">
            <i class="fa-solid fa-message"></i>
            <span class="text-[10px] font-medium">System Notif</span>
        </a>

        <button type="button" onclick="document.getElementById('accountDrawer').classList.remove('translate-x-full')"
            class="account-tab-trigger flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[#9AA2AA]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span class="text-[10px] font-medium">Account</span>
        </button>

    </div>
</div>

<!-- ==== ACCOUNT DRAWER (mobile only, full-screen overlay) ==== -->
<div id="accountDrawer"
    class="md:hidden fixed inset-0 z-[60] bg-white translate-x-full transition-transform duration-300 ease-out flex flex-col">

    <!-- Drawer header -->
    <div class="bg-black px-3 pt-3 pb-3 flex items-center gap-3 shrink-0">
        <div
            class="w-10 h-10 rounded-full bg-[#A9822C] text-white flex items-center justify-center text-[13px] font-semibold shrink-0 relative">
            <?= isset($_SESSION['first_name']) ? strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1)) : 'NH' ?>

            <?php if (strtoupper($userStatus ?? '') === 'APPROVED'): ?>
                <span
                    class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full bg-green-500 border-2 border-black flex items-center justify-center">
                    <i class="fa-solid fa-check text-[8px] text-white"></i>
                </span>
            <?php endif; ?>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-[14px] font-semibold text-white truncate leading-tight">
                <?= isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? '')) : 'Guest' ?>
            </p>
            <p class="text-[11px] text-white/60 truncate mt-0.5">Account</p>
        </div>
        <button type="button" onclick="document.getElementById('accountDrawer').classList.add('translate-x-full')"
            class="w-8 h-8 rounded-full flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-colors shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Drawer links -->
    <nav class="flex-1 flex flex-col px-3 pt-5 pb-4 overflow-y-auto">
        <p class="px-3.5 pb-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-[#9AA2AA]">Menu</p>
        <div class="flex flex-col gap-0.5">
            <a href="<?= BASE_URL ?>/page-1"
                class="flex items-center gap-3 pl-3 pr-3.5 py-3 rounded-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <i class="fa-solid fa-tachograph-digital w-[18px] text-center"></i>
                Create Employee Profile
            </a>
            <a href="<?= BASE_URL ?>/controlpanel/employees/"
                class="flex items-center gap-3 pl-3 pr-3.5 py-3 rounded-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Employees
            </a>
        </div>

        <p class="px-3.5 pt-4 pb-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-[#9AA2AA]">Settings</p>
        <div class="flex flex-col gap-0.5">
            <a href="<?= BASE_URL ?>/page-5"
                class="flex items-center gap-3 pl-3 pr-3.5 py-3 rounded-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <i class="fa-solid fa-circle-info w-[18px] text-center"></i>
                Term & Policy
            </a>
        </div>

        <div class="mt-auto pt-4">
            <div class="border-t border-black/5 mb-3"></div>
            <a href="<?= BASE_URL ?>/logout/"
                class="flex items-center gap-3 pl-3 pr-3.5 py-3 rounded-md text-sm font-semibold text-red-500 hover:bg-red-50 hover:text-red-600 hover:pl-4 transition-all duration-200">
                <i class="fa-solid fa-right-from-bracket"></i>
                Log out
            </a>
        </div>
    </nav>

</div>

<style>
    /* active state, applied via JS below */
    .bottom-tab.active {
        color: #A9822C;
    }

    .nav-link.active {
        color: #0B2540;
        background-color: rgba(169, 130, 44, 0.08);
        border-left-color: #A9822C;
    }
</style>

<script>
    // Guard against this include running more than once on a page
    if (!window.__navBound) {
        window.__navBound = true;

        const path = window.location.pathname.replace(/\/+$/, '') || '/';

        document.querySelectorAll('.bottom-tab, .nav-link').forEach(function (link) {
            const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
            if (linkPath === path) {
                link.classList.add('active');
            }
        });

        // Lock body scroll while the mobile account drawer is open
        const accountDrawer = document.getElementById('accountDrawer');
        if (accountDrawer) {
            const observer = new MutationObserver(function () {
                const isOpen = !accountDrawer.classList.contains('translate-x-full');
                document.body.style.overflow = isOpen ? 'hidden' : '';
            });
            observer.observe(accountDrawer, { attributes: true, attributeFilter: ['class'] });
        }
    }
</script>