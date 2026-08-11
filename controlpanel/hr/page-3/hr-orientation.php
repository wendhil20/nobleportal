<?php
//hr-orientation.php — HR 201 File: Employee Orientation
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

/* =========================================================
   CONFIG
   ========================================================= */
$PHOTO_DIR      = ROOT_PATH . '/uploads/orientation/photos/';
$HANDBOOK_DIR   = ROOT_PATH . '/uploads/orientation/handbooks/';
$PHOTO_URL      = BASE_URL . '/uploads/orientation/photos/';
$HANDBOOK_URL   = BASE_URL . '/uploads/orientation/handbooks/';

$MAX_PHOTO_BYTES    = 5 * 1024 * 1024;  // 5MB
$MAX_HANDBOOK_BYTES = 15 * 1024 * 1024; // 15MB
$WEBP_QUALITY       = 82;

$ALLOWED_PHOTO_MIME = [
    'image/jpeg' => 'imagecreatefromjpeg',
    'image/png'  => 'imagecreatefrompng',
    'image/gif'  => 'imagecreatefromgif',
    'image/webp' => 'imagecreatefromwebp',
];
$ALLOWED_HANDBOOK_MIME = [
    'application/pdf'                                                          => 'pdf',
    'application/msword'                                                       => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'  => 'docx',
];

if (!is_dir($PHOTO_DIR))    { @mkdir($PHOTO_DIR, 0755, true); }
if (!is_dir($HANDBOOK_DIR)) { @mkdir($HANDBOOK_DIR, 0755, true); }

/* =========================================================
   HELPERS
   ========================================================= */
function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Converts an uploaded image (any GD-supported type) to .webp and saves it.
 * Returns the stored filename on success, or null on failure.
 */
function convertUploadToWebp(array $file, string $destDir, array $allowedMime, int $maxBytes, ?string &$error = null): ?string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload error.';
        return null;
    }
    if ($file['size'] > $maxBytes) {
        $error = 'Photo exceeds the maximum allowed size.';
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMime[$mime])) {
        $error = 'Unsupported image type. Allowed: JPG, PNG, GIF, WEBP.';
        return null;
    }

    $createFn = $allowedMime[$mime];
    $srcImage = @$createFn($file['tmp_name']);
    if ($srcImage === false) {
        $error = 'Could not read the uploaded image.';
        return null;
    }

    // Preserve transparency for png/gif/webp
    imagepalettetotruecolor($srcImage);
    imagealphablending($srcImage, true);
    imagesavealpha($srcImage, true);

    $filename = bin2hex(random_bytes(16)) . '.webp';
    $destPath = $destDir . $filename;

    $ok = imagewebp($srcImage, $destPath, $GLOBALS['WEBP_QUALITY']);
    imagedestroy($srcImage);

    if (!$ok) {
        $error = 'Failed to convert image to WEBP.';
        return null;
    }

    return $filename;
}

/**
 * Stores an uploaded document as-is (pdf/doc/docx). Returns [filename, originalName] or null.
 */
function storeUploadedHandbook(array $file, string $destDir, array $allowedMime, int $maxBytes, ?string &$error = null): ?array {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload error.';
        return null;
    }
    if ($file['size'] > $maxBytes) {
        $error = 'Handbook file exceeds the maximum allowed size.';
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMime[$mime])) {
        $error = 'Unsupported file type. Allowed: PDF, DOC, DOCX.';
        return null;
    }

    $ext = $allowedMime[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $destDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        $error = 'Failed to save the uploaded file.';
        return null;
    }

    return [$filename, $file['name']];
}

function deleteFileIfExists(?string $dir, ?string $filename): void {
    if ($filename && is_file($dir . $filename)) {
        @unlink($dir . $filename);
    }
}

/* =========================================================
   LOAD EMPLOYEE
   ========================================================= */
$employeeId = (int) ($_GET['id'] ?? 0);
if ($employeeId <= 0) {
    header('Location: ' . BASE_URL . '/hr-employees');
    exit;
}

$stmt = $conn->prepare("SELECT id, first_name, last_name, username FROM nobleuserlist WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$employee) {
    header('Location: ' . BASE_URL . '/hr-employees');
    exit;
}

/* =========================================================
   HANDLE POST (CREATE / UPDATE / DELETE)
   ========================================================= */
$flashError = null;
$flashSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    // Fetch existing record (if any) to know what we're replacing/removing
    $stmt = $conn->prepare("SELECT * FROM nobleuser_orientation WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($action === 'delete') {
        if ($existing) {
            deleteFileIfExists($PHOTO_DIR, $existing['photo_path']);
            deleteFileIfExists($HANDBOOK_DIR, $existing['handbook_path']);

            $stmt = $conn->prepare("DELETE FROM nobleuser_orientation WHERE id = ?");
            $stmt->bind_param("i", $existing['id']);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ' . BASE_URL . '/hr-orientation?id=' . $employeeId . '&deleted=1');
        exit;
    }

    if ($action === 'save') {
        $orientationDate = trim($_POST['orientation_date'] ?? '') ?: null;
        $notes = trim($_POST['notes'] ?? '') ?: null;

        $photoFilename    = $existing['photo_path']    ?? null;
        $handbookFilename = $existing['handbook_path'] ?? null;
        $handbookOriginal = $existing['handbook_original_name'] ?? null;

        $uploadError = null;

        // --- Photo (convert to webp) ---
        if (!empty($_FILES['photo']['name'])) {
            $newPhoto = convertUploadToWebp($_FILES['photo'], $PHOTO_DIR, $ALLOWED_PHOTO_MIME, $MAX_PHOTO_BYTES, $uploadError);
            if ($newPhoto === null) {
                $flashError = $uploadError;
            } else {
                deleteFileIfExists($PHOTO_DIR, $photoFilename);
                $photoFilename = $newPhoto;
            }
        }

        // --- Handbook (stored as-is) ---
        if ($flashError === null && !empty($_FILES['handbook']['name'])) {
            $result = storeUploadedHandbook($_FILES['handbook'], $HANDBOOK_DIR, $ALLOWED_HANDBOOK_MIME, $MAX_HANDBOOK_BYTES, $uploadError);
            if ($result === null) {
                $flashError = $uploadError;
            } else {
                deleteFileIfExists($HANDBOOK_DIR, $handbookFilename);
                [$handbookFilename, $handbookOriginal] = $result;
            }
        }

        if ($flashError === null) {
            $isNewRecord = !$existing;

            if ($existing) {
                $stmt = $conn->prepare(
                    "UPDATE nobleuser_orientation
                     SET orientation_date = ?, photo_path = ?, handbook_path = ?, handbook_original_name = ?, notes = ?
                     WHERE id = ?"
                );
                $stmt->bind_param(
                    "sssssi",
                    $orientationDate,
                    $photoFilename,
                    $handbookFilename,
                    $handbookOriginal,
                    $notes,
                    $existing['id']
                );
                $stmt->execute();
                $stmt->close();
            } else {
                $createdBy = currentUserId();
                $stmt = $conn->prepare(
                    "INSERT INTO nobleuser_orientation
                        (user_id, orientation_date, photo_path, handbook_path, handbook_original_name, notes, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "isssssi",
                    $employeeId,
                    $orientationDate,
                    $photoFilename,
                    $handbookFilename,
                    $handbookOriginal,
                    $notes,
                    $createdBy
                );
                $stmt->execute();
                $stmt->close();
            }

            // ---------- Notify the employee (reuses existing nobleportalnotification table) ----------
            $notifTitle = $isNewRecord ? 'Orientation Record Added' : 'Orientation Record Updated';
            $notifMessage = $isNewRecord
                ? 'HR has completed your orientation record. Check your orientation photo and Employee Handbook.'
                : 'HR has updated your orientation record.';
            $notifLink = BASE_URL . '/page-3';

            $stmt = $conn->prepare(
                "INSERT INTO nobleportalnotification (for_role, for_position, for_user_id, recipient_type, title, message, link, is_read, created_at)
                 VALUES (NULL, NULL, ?, 'user', ?, ?, ?, 0, NOW())"
            );
            $stmt->bind_param("isss", $employeeId, $notifTitle, $notifMessage, $notifLink);
            $stmt->execute();
            $stmt->close();

            header('Location: ' . BASE_URL . '/hr-orientation?id=' . $employeeId . '&saved=1');
            exit;
        }
    }
}

/* =========================================================
   LOAD CURRENT RECORD FOR DISPLAY
   ========================================================= */
$stmt = $conn->prepare("SELECT * FROM nobleuser_orientation WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$orientation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (isset($_GET['saved']))   { $flashSuccess = 'Orientation record saved.'; }
if (isset($_GET['deleted'])) { $flashSuccess = 'Orientation record removed.'; }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Orientation</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
</head>

<body class="bg-[#F5F6F7] font-['Inter']">

    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

    <div id="mainContent" class="transition-all duration-300 ease-in-out md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-3xl mx-auto">

            <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                Human Resources
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[26px] uppercase text-[#0B2540] mb-1">
                Employee Orientation
            </h1>
            <p class="text-[14px] text-[#6B7785] mb-6">
                <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                <span class="text-[#9AA2AA]">(<?= htmlspecialchars($employee['username']) ?>)</span>
                &middot;
                <a href="<?= BASE_URL ?>/hr-employees?id=<?= $employeeId ?>" class="text-[#0B2540] font-semibold hover:underline">Back to 201 File</a>
            </p>

            <div class="bg-white border border-black/5 rounded-xl p-5 md:p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="save">

                    <!-- Orientation Photo -->
                    <div>
                        <label class="block text-[12px] font-semibold tracking-[0.06em] uppercase text-[#6B7785] mb-2">
                            Orientation Photo
                        </label>
                        <div class="flex items-start gap-4">
                            <div class="w-28 h-28 rounded-lg border border-[#E8EAEC] bg-[#F5F6F7] overflow-hidden flex items-center justify-center shrink-0">
                                <?php if (!empty($orientation['photo_path'])): ?>
                                    <img src="<?= $PHOTO_URL . htmlspecialchars($orientation['photo_path']) ?>"
                                        alt="Orientation photo" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-[11px] text-[#9AA2AA] text-center px-2">No photo uploaded</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="block w-full text-[13px] text-[#6B7785] file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-[13px] file:font-semibold file:bg-[#0B2540] file:text-white hover:file:bg-[#0B2540]/90">
                                <p class="text-[12px] text-[#9AA2AA] mt-1">JPG, PNG, GIF, or WEBP. Automatically converted to WEBP on save. Max 5MB.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Handbook -->
                    <div>
                        <label class="block text-[12px] font-semibold tracking-[0.06em] uppercase text-[#6B7785] mb-2">
                            Employee Handbook <span class="normal-case text-[#9AA2AA] font-normal">(uploaded by HR)</span>
                        </label>
                        <?php if (!empty($orientation['handbook_path'])): ?>
                            <div class="mb-2 text-[13px]">
                                <a href="<?= $HANDBOOK_URL . htmlspecialchars($orientation['handbook_path']) ?>" target="_blank"
                                    class="text-[#0B2540] font-semibold hover:underline">
                                    <?= htmlspecialchars($orientation['handbook_original_name'] ?? 'View current handbook') ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="handbook" accept=".pdf,.doc,.docx"
                            class="block w-full text-[13px] text-[#6B7785] file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-[13px] file:font-semibold file:bg-[#0B2540] file:text-white hover:file:bg-[#0B2540]/90">
                        <p class="text-[12px] text-[#9AA2AA] mt-1">PDF, DOC, or DOCX. Max 15MB. Leave empty to keep the current file.</p>
                    </div>

                    <!-- Orientation Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[12px] font-semibold tracking-[0.06em] uppercase text-[#6B7785] mb-2">
                                Orientation Date
                            </label>
                            <input type="date" name="orientation_date"
                                value="<?= htmlspecialchars($orientation['orientation_date'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[14px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-[12px] font-semibold tracking-[0.06em] uppercase text-[#6B7785] mb-2">
                            Notes
                        </label>
                        <textarea name="notes" rows="3"
                            class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[14px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors"
                            placeholder="Optional notes about the orientation session"><?= htmlspecialchars($orientation['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-[#E8EAEC]">
                        <button type="submit"
                            class="bg-[#0B2540] text-white text-[13px] font-semibold px-4 py-2.5 rounded-md hover:bg-[#0B2540]/90 transition-colors">
                            <?= $orientation ? 'Save Changes' : 'Create Orientation Record' ?>
                        </button>

                        <?php if ($orientation): ?>
                            <span class="text-[12px] text-[#9AA2AA]">
                                Last updated <?= htmlspecialchars($orientation['updated_at']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($orientation): ?>
                    <form method="POST" class="mt-4 pt-4 border-t border-[#E8EAEC]"
                        onsubmit="return confirm('Remove this orientation record? This will also delete the uploaded photo and handbook file. This cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="text-[13px] font-semibold text-red-600 hover:underline">
                            Remove Orientation Record
                        </button>
                    </form>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php if ($flashError || $flashSuccess): ?>
        <div id="orientationToast"
            class="fixed right-4 bottom-4 z-50 w-[calc(100%-2rem)] max-w-sm relative
                   flex items-start gap-3 rounded-xl bg-white shadow-lg border border-black/5
                   pl-4 pr-3 py-3.5 overflow-hidden
                   opacity-0 translate-y-2 transition-all duration-300 ease-out">

            <span class="absolute left-0 top-0 bottom-0 w-1 <?= $flashError ? 'bg-red-500' : 'bg-green-500' ?>"></span>

            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                         <?= $flashError ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' ?>">
                <?php if ($flashError): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                <?php endif; ?>
            </span>

            <div class="flex-1 min-w-0 pt-1">
                <p class="text-[13px] font-semibold <?= $flashError ? 'text-red-700' : 'text-[#1B2733]' ?>">
                    <?= $flashError ? 'Something went wrong' : 'Success' ?>
                </p>
                <p class="text-[12.5px] text-[#6B7785] mt-0.5">
                    <?= htmlspecialchars($flashError ?: $flashSuccess) ?>
                </p>
            </div>

            <button type="button" onclick="document.getElementById('orientationToast').remove()"
                class="shrink-0 -mt-1 -mr-1 w-6 h-6 flex items-center justify-center rounded-md text-[#9AA2AA] hover:bg-[#F5F6F7] hover:text-[#1B2733] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <script>
            (function () {
                const toast = document.getElementById('orientationToast');
                if (!toast) return;

                // Remove ?saved=1 / ?deleted=1 from the URL so a page refresh
                // doesn't re-show this toast.
                const url = new URL(window.location.href);
                url.searchParams.delete('saved');
                url.searchParams.delete('deleted');
                window.history.replaceState({}, '', url.pathname + url.search);

                requestAnimationFrame(() => {
                    toast.classList.remove('opacity-0', 'translate-y-2');
                });
                setTimeout(() => {
                    toast.classList.add('opacity-0', 'translate-y-2');
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            })();
        </script>
    <?php endif; ?>

</body>

</html>