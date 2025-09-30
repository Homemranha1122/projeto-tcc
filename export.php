<?php
@include 'conexao.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=eventos_exportados.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['ID', 'Tipo', 'Local', 'Cidade', 'UF', 'Latitude', 'Longitude', 'Intensidade', 'Observacoes', 'Data']);

if (isset($conn) && $conn) {
    $res = $conn->query("SELECT * FROM eventos ORDER BY data_evento DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($output, $row);
        }
    }
}

fclose($output);
exit();
?>