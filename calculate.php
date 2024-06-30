<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cards = $_POST['cards'];
    $additional_payment = floatval($_POST['additional_payment']);
    $payment_strategy = $_POST['payment_strategy'];
    $custom_order = isset($_POST['custom_order']) ? array_map('trim', explode(',', $_POST['custom_order'])) : [];

    function calculate_payment_schedule($cards, $additional_payment, $strategy, $custom_order) {
        $schedule = [];

        if ($strategy === 'snowball') {
            usort($cards, function($a, $b) {
                return $a['balance'] - $b['balance'];
            });
        } elseif ($strategy === 'avalanche') {
            usort($cards, function($a, $b) {
                return $b['interest_rate'] - $a['interest_rate'];
            });
        } elseif ($strategy === 'custom' && !empty($custom_order)) {
            usort($cards, function($a, $b) use ($custom_order) {
                $indexA = array_search($a['name'], $custom_order);
                $indexB = array_search($b['name'], $custom_order);
                return $indexA - $indexB;
            });
        }

        foreach ($cards as $card) {
            $balance = floatval($card['balance']);
            $rate = floatval($card['interest_rate']) / 100 / 12; // Monthly interest rate
            $min_payment = floatval($card['min_payment']);

            $monthly_payment = $min_payment + $additional_payment;
            $month = 0;
            $total_paid = 0;
            $total_interest = 0;

            while ($balance > 0) {
                $interest = $balance * $rate;
                $balance += $interest;
                if ($balance < $monthly_payment) {
                    $total_paid += $balance;
                    $total_interest += $interest;
                    $balance = 0;
                } else {
                    $balance -= $monthly_payment;
                    $total_paid += $monthly_payment;
                    $total_interest += $interest;
                }
                $month++;
            }
            $schedule[] = [
                'card_name' => $card['name'],
                'months' => $month,
                'total_paid' => $total_paid,
                'total_interest' => $total_interest
            ];
        }

        return $schedule;
    }

    $results = calculate_payment_schedule($cards, $additional_payment, $payment_strategy, $custom_order);
    echo json_encode($results);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
