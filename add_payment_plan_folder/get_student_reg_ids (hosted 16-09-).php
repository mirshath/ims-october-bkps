<?php
include '../database/connection.php';

$response = [
    'success' => false,
    'student_reg_ids' => [],
    'new_student_reg_ids' => [],
    'last_entered_id' => null,
    'last_new_entered_id' => null,
    'missing_reg_id' => null,
    'reg_id_range_min' => null,
    'reg_id_range_max' => null,
    'transferred_reg_ids' => []
];

if (isset($_POST['program_code'], $_POST['batch_id'])) {
    $program_code = $_POST['program_code'];
    $batch_id = $_POST['batch_id'];

  
    // ------------------------------
    $prog_code = isset($_POST['prog_code']) ? trim($_POST['prog_code']) : '';
    $batch_no  = isset($_POST['batch_no']) ? trim($_POST['batch_no']) : '';
    $intake_no = isset($_POST['intake_no']) ? trim($_POST['intake_no']) : '';
    $year_no   = isset($_POST['year_no']) ? trim($_POST['year_no']) : '';

    $expected_prefix = null;
    if ($prog_code !== '' && $batch_no !== '' && $intake_no !== '' && $year_no !== '') {
        $expected_prefix = $prog_code . $batch_no . $intake_no . $year_no;
    }

    // ------------------------------
    // 1. Fetch student_registration_id (old IDs)
    // ------------------------------
    $query1 = "SELECT student_registration_id 
               FROM allocate_programme 
               WHERE programme_code = ? 
                 AND batch_id = ? 
                 AND student_registration_id IS NOT NULL 
                 AND student_registration_id != '' 
               ORDER BY student_registration_id DESC";

    $stmt1 = $conn->prepare($query1);
    $stmt1->bind_param("si", $program_code, $batch_id);
    $stmt1->execute();
    $result1 = $stmt1->get_result();

    $reg_ids = [];
    while ($row = $result1->fetch_assoc()) {
        $reg_ids[] = $row['student_registration_id'];
    }

    // ------------------------------
 
    // ------------------------------
    $missing_reg_id = null;
    $reg_id_range_min = null;
    $reg_id_range_max = null;
    $transferred_reg_ids = [];

    $reg_suffixes = [];   // [intValue => full id string]

    foreach ($reg_ids as $rid) {
        if ($rid === null || $rid === '') continue;

        if ($expected_prefix !== null) {
            if (strpos($rid, $expected_prefix) !== 0) {
                // Doesn't match this program's exact prefix - most likely
                // a transferred-in student's original Registration ID.
                $transferred_reg_ids[] = $rid;
                continue;
            }
            $suffix = substr($rid, strlen($expected_prefix));
        } else {
            if (strlen($rid) < 3) continue; // need at least a prefix + 2-digit suffix
            $suffix = substr($rid, -2);
        }

        if ($suffix === '' || !ctype_digit($suffix)) continue; // skip non-numeric-suffix (manual) IDs

        $suffixInt = intval($suffix);
        $reg_suffixes[$suffixInt] = $rid;
    }

    if (!empty($reg_suffixes)) {
        $maxReg = max(array_keys($reg_suffixes));

        // Prefix + zero-padding width used to rebuild an id.
        if ($expected_prefix !== null) {
            $reg_prefix = $expected_prefix;
            $suffix_width = max(2, strlen($reg_suffixes[$maxReg]) - strlen($expected_prefix));
        } else {
            // Fallback: assume every id in this batch shares the max entry's prefix.
            $reg_prefix = substr($reg_suffixes[$maxReg], 0, -2);
            $suffix_width = 2;
        }

        // Range always starts at the theoretical "01" for this program's
        // prefix - not just the lowest id that happens to exist - and ends
        // at the actual highest id found.
        $reg_id_range_min = $reg_prefix . str_pad(1, $suffix_width, '0', STR_PAD_LEFT);
        $reg_id_range_max = $reg_suffixes[$maxReg];

        // Scan the full 01 -> max range for gaps, so a missing id at the
        // very start of the sequence (e.g. 01 or 02 never used) is caught
        // too, not just gaps between the lowest and highest ids found.
        for ($i = 1; $i <= $maxReg; $i++) {
            if (!isset($reg_suffixes[$i])) {
                $missing_reg_id = $reg_prefix . str_pad($i, $suffix_width, '0', STR_PAD_LEFT);
                break; // smallest missing number in the range
            }
        }
    }

    // ------------------------------
    // 2. Fetch new_student_registration_id (continuous, ignore smaller manual changes)
    // ------------------------------
    $query2 = "SELECT new_student_registration_id 
               FROM allocate_programme 
               WHERE new_student_registration_id IS NOT NULL 
                 AND new_student_registration_id != '' 
               ORDER BY id DESC";

    $result2 = $conn->query($query2);

    $new_reg_ids = [];
    $maxSuffix = 0;
    $matched_new_id = null;

    while ($row = $result2->fetch_assoc()) {
        $id = $row['new_student_registration_id'];
        $suffix = intval(substr($id, -6)); // last 6 digits as number

        // Skip invalid suffix (smaller than max found)
        if ($suffix <= $maxSuffix) continue;

        $maxSuffix = $suffix;
        $matched_new_id = $id;
        $new_reg_ids[] = $id;
    }



    $missing_new_id = null;

    $running_start_no = 100; // running-number part of e.g. 26000100 -> adjust if needed

    // Normalize $year_no to 2 digits (in case it ever comes as "2025" instead of "25").
    $target_year_prefix = $year_no !== '' ? substr($year_no, -2) : date('y');

    $query2b = "SELECT DISTINCT new_student_registration_id 
                FROM allocate_programme 
                WHERE new_student_registration_id IS NOT NULL 
                  AND new_student_registration_id != ''
                  AND new_student_registration_id REGEXP '^[0-9]+$'";
    $result2b = $conn->query($query2b);

    $running_numbers = []; // [runningNumber => true] - pooled across ALL years
    $running_width = 6;    // fallback default width, e.g. 000100
    $found_width = 0;      // actual width detected from data

    while ($row = $result2b->fetch_assoc()) {
        $val = $row['new_student_registration_id'];

        if (strlen($val) < 3) continue; // need at least year(2) + running(1+)

        $running_part = substr($val, 2); // strip first 2 chars (year), e.g. "25000001" -> "000001"
        if ($running_part === '' || !ctype_digit($running_part)) continue;

        $running_numbers[intval($running_part)] = true;
        $found_width = max($found_width, strlen($running_part));
    }

    if ($found_width > 0) {
        $running_width = $found_width;
    }

    if (!empty($running_numbers)) {
        $minRunning = $running_start_no;
        $maxRunning = max(array_keys($running_numbers));

        if ($maxRunning >= $minRunning) {
            for ($i = $minRunning; $i <= $maxRunning; $i++) {
                if (!isset($running_numbers[$i])) {
                    // Always use the FORM's year ($target_year_prefix), not
                    // whatever year the surrounding ids in the database had.
                    $missing_new_id = $target_year_prefix . str_pad($i, $running_width, '0', STR_PAD_LEFT);
                    break; // smallest missing running number in the pooled range
                }
            }
        }
    }

    // ------------------------------------------------------------------------------------------------------------------------
    // ------------------------------
    // 3. Handle empty results
    // ------------------------------
    if (empty($reg_ids)) $reg_ids[] = null;
    if (empty($new_reg_ids)) {
        $new_reg_ids = [];
        $matched_new_id = null;
    }

    // ------------------------------
    // 4. Prepare response
    // ------------------------------
    $response['last_entered_id'] = $reg_ids[0] ?? null;          // latest old ID
    $response['last_new_entered_id'] = $matched_new_id;          // latest valid continuous ID
    $response['student_reg_ids'] = array_reverse($reg_ids);
    $response['new_student_reg_ids'] = array_reverse($new_reg_ids);
    $response['missing_new_id'] = $missing_new_id;               // first gap found in current year's running-number range, or null
    $response['missing_reg_id'] = $missing_reg_id;                // first gap found in THIS program's own Reg ID sequence, or null
    $response['reg_id_range_min'] = $reg_id_range_min;            // lowest Reg ID matching this program's exact prefix
    $response['reg_id_range_max'] = $reg_id_range_max;            // highest Reg ID matching this program's exact prefix
    $response['transferred_reg_ids'] = $transferred_reg_ids;      // Reg IDs in this batch that DON'T match this program's prefix (batch-transferred students)
    $response['success'] = true;
}

echo json_encode($response);
