<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once __DIR__ . '/includes/db.php';

// Get the selected submission IDs from the form
if (isset($_POST['ids'])) {
    $ids = $_POST['ids'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Fetch the selected submissions from the database
    $stmt = $pdo->prepare("SELECT submissions.*, forms.form_name FROM submissions JOIN forms ON submissions.form_id = forms.id WHERE submissions.id IN ($placeholders)");
    $stmt->execute($ids);
    $submissions = $stmt->fetchAll();
    
    // Export to CSV or Excel based on query parameter
    if ($_GET['type'] == 'csv') {
        // Export CSV
        $filename = 'submissions_selected_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Submission ID', 'Form Name', 'Entry Data', 'User IP', 'User Agent', 'Submitted At']);
        foreach ($submissions as $submission) {
            fputcsv($output, [
                $submission['id'],
                $submission['form_name'],
                $submission['entry_data'],
                $submission['user_ip'],
                $submission['user_agent'],
                $submission['submitted_at'],
            ]);
        }
        fclose($output);
    } else {
        // Export Excel (CSV format works here too)
        $filename = 'submissions_selected_' . date('Y-m-d_H-i-s') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Submission ID', 'Form Name', 'Entry Data', 'User IP', 'User Agent', 'Submitted At']);
        foreach ($submissions as $submission) {
            fputcsv($output, [
                $submission['id'],
                $submission['form_name'],
                $submission['entry_data'],
                $submission['user_ip'],
                $submission['user_agent'],
                $submission['submitted_at'],
            ]);
        }
        fclose($output);
    }
} else {
    echo "No submissions selected for export.";
}
exit;
?>
