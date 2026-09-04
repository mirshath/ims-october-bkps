<?php
include 'database/connection.php';

if (isset($_POST['programme_id'], $_POST['batch_id'])) {
    $programme_id = $_POST['programme_id'];
    $batch_id = $_POST['batch_id'];

    $query = "SELECT p.program_name, p.cetegory, b.batch_name, pba.* 
              FROM payment_batch_allocation pba
              JOIN program_table p ON p.program_code = pba.programme_id
              JOIN batch_table b ON b.id = pba.batch_id
              WHERE pba.programme_id = ? AND pba.batch_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $programme_id, $batch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $is_final_year = ($row['cetegory'] === 'final_year');
        $final_year_installments = [];

        if ($is_final_year) {
            $installment_query = "SELECT id, installment_no, instalment_date, instalment_amount 
                                  FROM final_yeat_instalment_data 
                                  WHERE program_id = ? AND batch_id = ? 
                                  ORDER BY installment_no ASC";
            $installment_stmt = $conn->prepare($installment_query);
            $installment_stmt->bind_param("ii", $programme_id, $batch_id);
            $installment_stmt->execute();
            $installment_result = $installment_stmt->get_result();
            while ($inst_row = $installment_result->fetch_assoc()) {
                $final_year_installments[] = $inst_row;
            }
            $installment_stmt->close();
        }

        // Build table headers and cells conditionally
        $thead = '<tr>';
        $tbody = '<tr>';
        
        // Course Fee (LKR) - always show
        $thead .= '<th>Course Fee (LKR)</th>';
        $tbody .= '<td>' . number_format($row['course_fee_lkr'], 2) . '</td>';
        
        // Uni Fee (GBP) - show only if > 0
        if ($row['uni_fee_gbp'] > 0) {
            $thead .= '<th>Uni Fee (GBP)</th>';
            $tbody .= '<td>' . number_format($row['uni_fee_gbp'], 2) . '</td>';
        }
        
        // Uni Fee (USD) - show only if > 0
        if ($row['uni_fee_usd'] > 0) {
            $thead .= '<th>Uni Fee (USD)</th>';
            $tbody .= '<td>' . number_format($row['uni_fee_usd'], 2) . '</td>';
        }
        
        // Uni Fee (EUR) - show only if > 0
        if ($row['uni_fee_euro'] > 0) {
            $thead .= '<th>Uni Fee (EUR)</th>';
            $tbody .= '<td>' . number_format($row['uni_fee_euro'], 2) . '</td>';
        }
        
        // Register Date - always show
        $thead .= '<th>Register Date</th>';
        $tbody .= '<td>' . htmlspecialchars($row['register_date']) . '</td>';
        
        // Installments - always show
        $thead .= '<th>Installments</th>';
        $tbody .= '<td>' . (int)$row['installment_no'] . '</td>';
        
        // Installment Interval - always show
        $thead .= '<th>Installment Interval (Months)</th>';
        $tbody .= '<td>' . (isset($row['Installment_Interval']) ? htmlspecialchars($row['Installment_Interval']) : (isset($row['installment_interval']) ? htmlspecialchars($row['installment_interval']) : '1')) . '</td>';
        
        // Registration Fee - always show
        $thead .= '<th>Registration Fee</th>';
        $tbody .= '<td>' . number_format($row['registration_fee'], 2) . '</td>';
        
        // Only Course Fee - always show
        $thead .= '<th>Only Course Fee</th>';
        $tbody .= '<td>' . number_format($row['only_course_fee'], 2) . '</td>';
        
        $thead .= '</tr>';
        $tbody .= '</tr>';
        
        $table_html = '<table class="table table-bordered">
                <thead>' . $thead . '</thead>
                <tbody>' . $tbody . '</tbody>
              </table>';

        // Add final year installments table if applicable
        if ($is_final_year && !empty($final_year_installments)) {
            $table_html .= '<h5 class="mt-4">final year Program Payment details</h5>';
            $table_html .= '<table class="table table-bordered">';
            $table_html .= '<thead><tr><th>Installment No</th><th>Due Date</th><th>Amount (LKR)</th></tr></thead>';
            $table_html .= '<tbody>';
            foreach ($final_year_installments as $inst) {
                $table_html .= '<tr>';
                $table_html .= '<td>' . htmlspecialchars($inst['installment_no']) . '</td>';
                $table_html .= '<td>' . htmlspecialchars($inst['instalment_date']) . '</td>';
                $table_html .= '<td>' . number_format($inst['instalment_amount'], 2) . '</td>';
                $table_html .= '</tr>';
            }
            $table_html .= '</tbody></table>';
        }

        echo json_encode([
            'table' => $table_html,
            'data' => [
                'programme_id' => $programme_id,
                'batch_id' => $batch_id,
                'program_name' => $row['program_name'],
                'batch_name' => $row['batch_name'],
                'course_fee_lkr' => $row['course_fee_lkr'],
                'uni_fee_gbp' => $row['uni_fee_gbp'],
                'uni_fee_usd' => $row['uni_fee_usd'],
                'uni_fee_euro' => $row['uni_fee_euro'],
                'register_date' => $row['register_date'],
                'installment_no' => $row['installment_no'],
                'installment_interval' => (isset($row['Installment_Interval']) ? $row['Installment_Interval'] : (isset($row['installment_interval']) ? $row['installment_interval'] : null)),
                'registration_fee' => $row['registration_fee'],
                'only_course_fee' => $row['only_course_fee'],
                'is_final_year' => $is_final_year,
                'final_year_installments' => $final_year_installments,
            ],
        ]);
    } else {
        echo json_encode([
            'table' => '<div class="alert alert-warning">No payment details found.</div>',
            'data' => null
        ]);
    }
    $stmt->close();
}
