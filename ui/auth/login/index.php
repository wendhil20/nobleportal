<?php
//index.php
include ROOT_PATH . "/network/connect.php";

// May existing session na? Diretso na sa dashboard, wag na ipakita ulit ang login form
if (!empty($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/page-1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
</head>

<body class="min-h-dvh flex font-['Inter'] bg-[#FAFAF7] text-[#1B2733]">

    <!-- LEFT: brand / blueprint panel -->
    <div class="hidden lg:flex lg:w-[46%] relative flex-col justify-between text-[#EDEFEF] p-12 overflow-hidden bg-cover bg-center"
        style="background-image: linear-gradient(rgba(3, 53, 104, 0.6), rgba(0, 0, 0, 0.6)), url('<?= BASE_URL ?>/icon/background/building2.png');">
        <div class="relative z-10 flex items-center gap-4">
            <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction" class="w-9 h-9 object-contain">
            <span
                class="font-['Barlow_Condensed'] font-semibold text-base tracking-[0.1em] uppercase text-[#EDEFEF]/90">
                Noblehome Construction
            </span>
        </div>

        <div class="relative z-10 max-w-sm">
            <p class="font-['Barlow_Condensed'] text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-3">
                Employee portal
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[38px] leading-[1.1] uppercase mb-4">
                Built on structure,<br>run with precision.
            </h1>
            <p class="text-sm leading-relaxed text-[#EDEFEF]/60">
                Access schedules, project files, and site reports from one place. For staff and site personnel of
                Noblehome Construction only.
            </p>
        </div>

        <div class="relative z-10 font-mono text-[11px] tracking-[0.05em] text-[#EDEFEF]/40">
            REF&nbsp;NH-2026&nbsp;/&nbsp;PORTAL
        </div>
    </div>

    <!-- RIGHT: form panel -->
    <div class="flex-1 flex items-start lg:items-center justify-center p-8 py-12 lg:py-8 bg-gray-100 overflow-y-auto">
        <div class="w-full max-w-[380px]">

            <!-- mobile-only brand mark -->
            <div class="flex lg:hidden items-center gap-2.5 mb-10">
                <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction"
                    class="w-9 h-9 object-contain">
                <span
                    class="font-['Barlow_Condensed'] font-bold text-lg tracking-[0.1em] uppercase text-black/80">
                    Noblehome Construction
                </span>
            </div>

            <p
                class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-black mb-1.5">
                Site access
            </p>
            <h2 class="font-['Barlow_Condensed'] font-bold text-[28px] uppercase leading-[1.1] mb-8 text-amber-600">
                Log in to continue
            </h2>

            <form action="<?= BASE_URL ?>/login-process" method="post" class="flex flex-col gap-5">
                <div>
                    <label for="username"
                        class="block text-[11px] font-semibold tracking-[0.1em] uppercase text-[#6B7785] mb-1.5">
                        Employee ID
                    </label>
                    <input type="text" name="username" id="username" placeholder="Enter your Employee ID"
                        autocomplete="username"
                        class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] text-[#1B2733] placeholder-[#9AA2AA] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password"
                            class="block text-[11px] font-semibold tracking-[0.1em] uppercase text-[#6B7785]">
                            Password
                        </label>

                    </div>
                    <input type="password" name="password" id="password" placeholder="Enter your password"
                        autocomplete="current-password"
                        class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] text-[#1B2733] placeholder-[#9AA2AA] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
                </div>

                <button type="submit"
                    class="mt-2 w-full py-3 bg-black text-[#FAFAF7] font-['Barlow_Condensed'] font-bold text-[15px] tracking-[0.08em] uppercase rounded-md transition-colors">
                    Log in
                </button>
            </form>

            <p class="mt-8 text-xs text-[#9AA2AA] text-center">Authorized personnel only</p>
        </div>
    </div>

</body>

</html>