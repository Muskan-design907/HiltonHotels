<?php
require 'db.php';
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
 
$room_id = $_POST['room_id'] ?? 0;
$checkin = $_POST['checkin'] ?? '';
$checkout = $_POST['checkout'] ?? '';
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
 
if (!$room_id || !$checkin || !$checkout || strtotime($checkin) >= strtotime($checkout) || !$full_name || !$email || !$phone) {
    die('Please fill in all required fields correctly.');
}
 
// Check if room is still available for those dates
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND available_from <= ? AND available_to >= ?");
$stmt->execute([$room_id, $checkin, $checkout]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$room) {
    die('Room is no longer available for the selected dates.');
}
 
// Check if user already exists by email, else create user
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
 
if ($user) {
    $user_id = $user['id'];
} else {
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, '')");
    $stmt->execute([$full_name, $email, $phone]);
    $user_id = $pdo->lastInsertId();
}
 
// Insert booking
$stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out) VALUES (?, ?, ?, ?)");
$stmt->execute([$user_id, $room_id, $checkin, $checkout]);
 
$booking_id = $pdo->lastInsertId();
 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Booking Confirmation - Hilton Hotels Clone</title>
<style>
  body { font-family: Arial, sans-serif; background:#f7f7f7; margin: 0; }
  header { background:#003580; color:#fff; padding: 15px; text-align: center; font-size: 24px; font-weight: bold;}
  .container { max-width: 600px; background:#fff; margin: 40px auto; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);}
  h1 { color: #003580; }
  p { font-size: 18px; }
  a.button {
    display: inline-block;
    margin-top: 20px;
    background: #003580;
    color: white;
    text-decoration: none;
    padding: 10px 25px;
    border-radius: 5px;
  }
  a.button:hover {
    background: #002255;
  }
</style>
</head>
<body>
<header>Booking Confirmed</header>
<div class="container">
  <h1>Thank you, <?=htmlspecialchars($full_name)?>!</h1>
  <p>Your booking has been confirmed.</p>
  <p><strong>Booking ID:</strong> <?=$booking_id?></p>
  <p><strong>Hotel Room:</strong> <?=$room['room_type']?></p>
  <p><strong>Check-in:</strong> <?=$checkin?></p>
  <p><strong>Check-out:</strong> <?=$checkout?></p>
  <p>A confirmation email would normally be sent to <strong><?=htmlspecialchars($email)?></strong>.</p>
  <a href="index.php" class="button">Back to Home</a>
</div>
</body>
</html>
 
