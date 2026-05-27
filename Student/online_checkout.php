<?php
session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

$ctx = evsu_student_init($conn);

$user_id = $ctx['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request');
}

$payment_method = $_POST['payment_method'] ?? 'cash';
$notes = trim($_POST['notes'] ?? '');

//
// GET CART ITEMS
//

$stmt = $conn->prepare("
    SELECT *
    FROM cart_items
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$cart_items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {

    $row['unit_price'] = (float)$row['unit_price'];
    $row['quantity'] = (int)$row['quantity'];

    $cart_items[] = $row;

    $total += $row['unit_price'] * $row['quantity'];
}

$stmt->close();

if (empty($cart_items)) {
    die("Cart is empty.");
}

//
// CASH PAYMENT
//

if ($payment_method === 'cash') {

    $_SESSION['checkout_data'] = [
        'notes' => $notes,
        'payment_method' => 'cash'
    ];

    header("Location: checkout.php");
    exit;
}

//
// ONLINE PAYMENT → PAYMONGO
//

$secretKey = "sk_test_1yseSc94ii5tdCStHN3uejEc";

$line_items = [];

foreach ($cart_items as $item) {

    $line_items[] = [
        "currency" => "PHP",
        "amount" => $item['unit_price'] * 100,
        "name" => $item['product_name'],
        "quantity" => $item['quantity']
    ];
}

$data = [
    "data" => [
        "attributes" => [

            "billing" => [
                "name" => $ctx['user_name'],
                "email" => $ctx['email'] ?? 'student@evsu.edu.ph'
            ],

            "send_email_receipt" => true,

            "show_description" => true,

            "show_line_items" => true,

            "description" => "EVSU Reserve Order",

            "line_items" => $line_items,

            "payment_method_types" => [
                "gcash",
                "paymaya",
                "card"
            ],

            "success_url" =>
            "http://localhost/EvsuReserve/Student/payment_success.php",

            "cancel_url" =>
            "http://localhost/EvsuReserve/Student/student_cart.php"
        ]
    ]
];

$payload = json_encode($data);

$curl = curl_init();

curl_setopt_array($curl, [

    CURLOPT_URL =>
    "https://api.paymongo.com/v1/checkout_sessions",

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_POST => true,

    CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "content-type: application/json",
        "authorization: Basic " .
        base64_encode($secretKey . ":")
    ],

    CURLOPT_POSTFIELDS => $payload
]);

$response = curl_exec($curl);

curl_close($curl);

$result = json_decode($response, true);

if (isset($result['data'])) {

    $_SESSION['pending_order'] = [
        'notes' => $notes,
        'payment_method' => 'online'
    ];

    $checkout_url =
    $result['data']['attributes']['checkout_url'];

    header("Location: " . $checkout_url);
    exit;
}

echo "<pre>";
print_r($result);
echo "</pre>";