<?php
// step-1.php — kasama sa /ui/page/page-1/
// Umaasa sa mga variables na galing sa main.php: $old, $targetUser
?>
<!-- ==== STEP 1: BASIC INFORMATION ==== -->
<div data-step="1" class="flex flex-col gap-5">

    <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2 sm:col-span-1">
            <label
                class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">First
                Name</label>
            <input type="text" name="first_name" placeholder="JUAN"
                value="<?= old('first_name', $old) ?>" required
                class="uppercase-input w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] uppercase outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
        </div>
        <div class="col-span-2 sm:col-span-1">
            <label
                class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Middle
                Name</label>
            <input type="text" name="middle_name" placeholder="SANTOS"
                value="<?= old('middle_name', $old) ?>"
                class="uppercase-input w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] uppercase outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
        </div>
        <div class="col-span-2 sm:col-span-1">
            <label
                class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Last
                Name</label>
            <input type="text" name="last_name" placeholder="DELA CRUZ"
                value="<?= old('last_name', $old) ?>" required
                class="uppercase-input w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] uppercase outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
        </div>
        <div class="col-span-2 sm:col-span-1">
            <label
                class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">
                Extension Name <span class="normal-case text-[#9AA2AA]">(Jr., Sr., III —
                    optional)</span>
            </label>
            <input type="text" name="extension_name" placeholder="JR."
                value="<?= old('extension_name', $old) ?>"
                class="uppercase-input w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] uppercase outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label
                class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Birthdate</label>
            <input type="date" name="birthdate" id="birthdate"
                value="<?= old('birthdate', $old) ?>" required
                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
        </div>
        <div>
            <label
                class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Age</label>
            <input type="number" name="age" id="age" placeholder="0"
                value="<?= old('age', $old) ?>" readonly required
                class="w-full bg-[#F5F6F7] border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] outline-none">
        </div>
    </div>

    <div>
        <label
            class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-2">Gender</label>
        <div class="flex gap-5">
            <label class="flex items-center gap-2 text-sm text-[#1B2733]">
                <input type="radio" name="gender" value="MALE" <?= (($old['gender'] ?? '') === 'MALE') ? 'checked' : '' ?> required class="accent-[#0B2540]"> Male
            </label>
            <label class="flex items-center gap-2 text-sm text-[#1B2733]">
                <input type="radio" name="gender" value="FEMALE" <?= (($old['gender'] ?? '') === 'FEMALE') ? 'checked' : '' ?> required class="accent-[#0B2540]"> Female
            </label>
        </div>
    </div>

    <div>
        <label
            class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Birthplace</label>
        <input type="text" name="birthplace" placeholder="e.g. Quezon City, Metro Manila"
            value="<?= old('birthplace', $old) ?>" required
            class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
    </div>

    <div class="flex justify-end pt-2">
        <button type="button" data-next
            class="px-6 py-2.5 bg-[#0B2540] text-white font-['Barlow_Condensed'] font-bold text-sm tracking-[0.06em] uppercase rounded-md hover:bg-[#0B2540]/90 transition-colors">
            Next
        </button>
    </div>
</div>