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
    // Optional: the exact pieces that make up THIS program's own
    // Registration ID prefix (Program Code + Batch NO + Intake NO + Year,
    // e.g. IFDS + 05 + 05 + 26 -> "IFDS050526"). When supplied, the
    // range/missing-ID check below only considers IDs that literally start
    // with this prefix. This matters because a student who was batch
    // transferred into this program/batch keeps the Registration ID they
    // were originally issued - which was built from a DIFFERENT program
    // code / batch / intake / year - so it must not be mixed into this
    // program's own numbering sequence.
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
    // 1b. Check for a missing number in the student_registration_id
    //     (Program Registration ID) sequence (min -> max) for THIS
    //     program_code + batch_id combination.
    //     These IDs are auto-generated as: prog_code + batch_no + intake_no
    //     + year + 2-digit running suffix (e.g. IFDS05052601 -> suffix 01).
    //
    //     If $expected_prefix was supplied, only IDs that literally start
    //     with it are treated as belonging to this program's sequence.
    //     Anything else (e.g. a Registration ID carried over from a
    //     batch-transferred student, built with a different program code /
    //     batch / intake / year) is set aside in $transferred_reg_ids
    //     instead of corrupting the range/missing-ID check.
    //
    //     If $expected_prefix wasn't supplied (older caller), fall back to
    //     the previous behaviour of treating the last 2 chars as the suffix.
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

    // ------------------------------
    // 2b. Check for a missing number in the new_student_registration_id
    //     sequence (min -> max). Only pure numeric IDs (e.g. 26000001)
    //     are considered - manually formatted IDs (e.g. IFDS05052601)
    //     are ignored for this check.
    // ------------------------------
    $missing_new_id = null;

    $query2b = "SELECT DISTINCT new_student_registration_id 
                FROM allocate_programme 
                WHERE new_student_registration_id IS NOT NULL 
                  AND new_student_registration_id != ''
                  AND new_student_registration_id REGEXP '^[0-9]+$'";
    $result2b = $conn->query($query2b);

    $numeric_ids = [];   // [intValue => true]
    $id_width = 0;       // preserve leading-zero width, e.g. 8 for '26000001'

    while ($row = $result2b->fetch_assoc()) {
        $val = $row['new_student_registration_id'];
        $numeric_ids[intval($val)] = true;
        $id_width = max($id_width, strlen($val));
    }

    if (!empty($numeric_ids)) {
        $minId = min(array_keys($numeric_ids));
        $maxId = max(array_keys($numeric_ids));

        for ($i = $minId; $i <= $maxId; $i++) {
            if (!isset($numeric_ids[$i])) {
                $missing_new_id = str_pad($i, $id_width, '0', STR_PAD_LEFT);
                break; // smallest missing number in the range
            }
        }
    }

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
    $response['missing_new_id'] = $missing_new_id;               // first gap found in min->max range, or null
    $response['missing_reg_id'] = $missing_reg_id;                // first gap found in THIS program's own Reg ID sequence, or null
    $response['reg_id_range_min'] = $reg_id_range_min;            // lowest Reg ID matching this program's exact prefix
    $response['reg_id_range_max'] = $reg_id_range_max;            // highest Reg ID matching this program's exact prefix
    $response['transferred_reg_ids'] = $transferred_reg_ids;      // Reg IDs in this batch that DON'T match this program's prefix (batch-transferred students)
    $response['success'] = true;
}

echo json_encode($response);
