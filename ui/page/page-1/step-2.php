<?php
// step-2.php — kasama sa /ui/page/page-1/
// Umaasa sa mga variables na galing sa main.php: $old, $existingInfo
?>
<!-- ==== STEP 2: ADDITIONAL INFORMATION ==== -->
<div data-step="2" class="hidden flex-col gap-5">

    <div>
        <label
            class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Marital
            Status</label>
        <select name="marital_status" id="marital_status" required
            class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
            <?php $selectedMarital = $old['marital_status'] ?? ($existingInfo['marital_status'] ?? ''); ?>
            <option value="" disabled <?= $selectedMarital === '' ? 'selected' : '' ?>>Select
                marital status</option>
            <option value="SINGLE" <?= $selectedMarital === 'SINGLE' ? 'selected' : '' ?>>Single
            </option>
            <option value="MARRIED" <?= $selectedMarital === 'MARRIED' ? 'selected' : '' ?>>
                Married
            </option>
            <option value="WIDOWED" <?= $selectedMarital === 'WIDOWED' ? 'selected' : '' ?>>Widowed
            </option>
            <option value="SEPARATED" <?= $selectedMarital === 'SEPARATED' ? 'selected' : '' ?>>
                Separated
            </option>
            <option value="DIVORCED" <?= $selectedMarital === 'DIVORCED' ? 'selected' : '' ?>>
                Divorced
            </option>
        </select>
    </div>

    <div>
        <label
            class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Present
            Complete Address</label>
        <textarea name="present_address" rows="3"
            placeholder="House No., Street, Barangay, City/Municipality, Province" required
            class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors"><?= old('present_address', $old) ?></textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label
                class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Religion</label>
            <input type="text" name="religion" placeholder="e.g. Roman Catholic"
                value="<?= old('religion', $old) ?>" required
                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
        </div>
        <div>
            <label
                class="block text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-1.5">Citizenship</label>
            <input type="text" name="citizenship" placeholder="e.g. Filipino"
                value="<?= old('citizenship', $old, 'Filipino') ?>" required
                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
        </div>
    </div>

    <div class="flex justify-between pt-2">
        <button type="button" data-back
            class="px-6 py-2.5 bg-white border border-[#D8DBDE] text-[#4B5866] font-['Barlow_Condensed'] font-bold text-sm tracking-[0.06em] uppercase rounded-md hover:bg-[#F5F6F7] transition-colors">
            Back
        </button>
        <button type="button" data-next
            class="px-6 py-2.5 bg-[#0B2540] text-white font-['Barlow_Condensed'] font-bold text-sm tracking-[0.06em] uppercase rounded-md hover:bg-[#0B2540]/90 transition-colors">
            Next
        </button>
    </div>
</div>