<?php
//registeraccount.php
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr','head');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Employee Account</title>
    <?php include ROOT_PATH . "/link/top.php"; ?>
</head>
<body class="bg-gray-50">
    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

<div id="mainContent" class="transition-all duration-300 ease-in-out min-h-screen flex items-center justify-center px-6 py-8 bg-gradient-to-br from-gray-50 to-gray-100">

        <div class="max-w-3xl mx-auto bg-white border border-black/5 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 p-6">

            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-full bg-black/90 text-white flex items-center justify-center font-semibold text-sm">
                    NH
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-black/90">Register Employee Account</h4>
                    <p class="text-xs text-black/40">Noblehome Construction · HR Control Panel</p>
                </div>
            </div>

            <div id="alertBox" class="hidden mb-4 px-4 py-2.5 rounded-lg border text-sm"></div>

            <form method="POST" id="registerForm" class="space-y-4">

                <div>
                    <label class="block text-sm font-medium text-black/70 mb-1.5">First Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-black/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <input type="text" name="first_name" id="first_name" required
                            placeholder="e.g. Juan"
                            class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                                   focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-black/70 mb-1.5">Last Name</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-black/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <input type="text" name="last_name" id="last_name" required
                            placeholder="e.g. Dela Cruz"
                            class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                                   focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-black/70 mb-1.5">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-black/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21a8 8 0 1 0-16 0"></path>
                                <circle cx="12" cy="11" r="4"></circle>
                            </svg>
                        </span>
                        <input type="text" name="username" id="username" required
                            placeholder="e.g. NHCCJDELACRUZ"
                            class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                                   focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                    </div>
                    <p class="mt-1 text-xs text-black/40">Auto-suggested based on name, editable.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-black/70 mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-black/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input type="password" name="password" id="password" required
                            placeholder="At least 8 characters"
                            class="w-full pl-9 pr-10 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                                   focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                        <button type="button" id="togglePassword"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-black/40 hover:text-black/70 transition-colors">
                            <!-- eye icon (password hidden state) -->
                            <svg id="eyeIconShow" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <!-- eye-off icon (password visible state) -->
                            <svg id="eyeIconHide" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" id="submitBtn"
                    class="w-full mt-2 bg-black/90 hover:bg-black active:scale-[0.99] text-white text-sm font-medium
                           py-2.5 rounded-lg transition-all duration-150 shadow-sm hover:shadow-md">
                    Register
                </button>

            </form>
        </div>

    </div>

    <script>
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput  = document.getElementById('last_name');
        const usernameInput  = document.getElementById('username');

        function generateUsername() {
            const first = firstNameInput.value.trim();
            const last  = lastNameInput.value.trim();

            if (first && last) {
                const initial = first.charAt(0).toUpperCase();
                const lastClean = last.replace(/\s+/g, '').toUpperCase();
                usernameInput.value = 'NHCC' + initial + lastClean;
            }
        }

        firstNameInput.addEventListener('input', generateUsername);
        lastNameInput.addEventListener('input', generateUsername);

        // ==== TOGGLE PASSWORD VISIBILITY ====
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const eyeIconShow = document.getElementById('eyeIconShow');
        const eyeIconHide = document.getElementById('eyeIconHide');

        togglePassword.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            eyeIconShow.classList.toggle('hidden', isHidden);
            eyeIconHide.classList.toggle('hidden', !isHidden);
        });

        // ==== SUBMIT VIA AJAX TO THE SEPARATE BACKEND (register_process.php) ====
        const registerForm = document.getElementById('registerForm');
        const alertBox = document.getElementById('alertBox');
        const submitBtn = document.getElementById('submitBtn');

        // Guard flag: prevents duplicate submissions from double-click,
        // double-bound listeners, or slow network + repeated Enter/click
        let isSubmitting = false;

        function showAlert(message, isSuccess) {
            alertBox.textContent = message;
            alertBox.classList.remove('hidden', 'bg-red-50', 'border-red-200', 'text-red-600', 'bg-green-50', 'border-green-200', 'text-green-600');
            if (isSuccess) {
                alertBox.classList.add('bg-green-50', 'border-green-200', 'text-green-600');
            } else {
                alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-600');
            }
        }

        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (isSubmitting) return; // block duplicate calls while one is in-flight
            isSubmitting = true;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';

            try {
                const formData = new FormData(registerForm);
                const res = await fetch('<?= BASE_URL ?>/registerprocess', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                showAlert(data.message, data.success);

                if (data.success) {
                    registerForm.reset();
                }
            } catch (err) {
                showAlert('Error connecting to the server.', false);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Register';
                isSubmitting = false; // release lock
            }
        });
    </script>

</body>
</html>