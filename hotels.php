<?php
require 'db.php';
 
$hotel_id = $_GET['id'] ?? 0;
$room_id = $_GET['room_id'] ?? null;
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
 
if (!$hotel_id || !$checkin || !$checkout || strtotime($checkin) >= strtotime($checkout)) {
    die('Invalid parameters.');
}
 
// Fetch hotel details
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$hotel) {
    die('Hotel not found.');
}
 
// Fetch rooms for this hotel
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE hotel_id = ? AND available_from <= ? AND available_to >= ?");
$stmt->execute([$hotel_id, $checkin, $checkout]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// If specific room_id is passed, fetch that room
$selectedRoom = null;
if ($room_id) {
    foreach ($rooms as $room) {
        if ($room['id'] == $room_id) {
            $selectedRoom = $room;
            break;
        }
    }
    if (!$selectedRoom) {
        die('Selected room not available for these dates.');
    }
}
 
function starRating($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? true : false;
    $stars = '';
    for ($i=0; $i < $fullStars; $i++) {
        $stars .= '★';
    }
    if ($halfStar) {
        $stars .= '☆';
    }
    return $stars;
}
 
function amenitiesList($text) {
    return explode(',', $text);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?=htmlspecialchars($hotel['name'])?> - Hilton Hotels Clone</title>
<style>
  body { font-family: Arial, sans-serif; margin:0; background:#f7f7f7; }
  header { background:#003580; padding:15px; color:#fff; text-align:center; font-size:24px; font-weight:bold; }
  .container { max-width: 900px; margin: auto; padding: 20px; background:#fff; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
  .hotel-header { display: flex; flex-wrap: wrap; gap: 20px; }
  .hotel-image {
    width: 100%;
    max-width: 400px;
    height: 250px;
    object-fit: cover;
    border-radius: 8px;
  }
  .hotel-info {
    flex: 1;
  }
  .stars {
    color: #ffc107;
    font-size: 20px;
  }
  ul.amenities-list {
    list-style: none;
    padding: 0;
    display: flex;
    gap: 10px;
  }
  ul.amenities-list li {
    background: #003580;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
  }
  h2 {
    margin-top: 30px;
  }
  .room-list {
    margin-top: 20px;
  }
  .room-card {
    border: 1px solid #ccc;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    display: flex;
    gap: 20px;
    align-items: center;
  }
  .room-image {
    width: 180px;
    height: 130px;
    object-fit: cover;
    border-radius: 8px;
  }
  .room-info {
    flex: 1;
  }
  .price {
    font-size: 18px;
    font-weight: bold;
    color: #003580;
    margin-bottom: 10px;
  }
  .btn-select {
    background: #003580;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
  }
  .btn-select:hover {
    background: #002255;
  }
  form.booking-form {
    margin-top: 30px;
    background: #e9f0ff;
    padding: 20px;
    border-radius: 8px;
  }
  form.booking-form input, form.booking-form button {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 16px;
  }
  form.booking-form button {
    background: #003580;
    color: white;
    border: none;
    cursor: pointer;
  }
  form.booking-form button:hover {
    background: #002255;
  }
 
  @media(max-width: 700px) {
    .hotel-header {
      flex-direction: column;
      align-items: center;
    }
    .hotel-image {
      max-width: 100%;
      height: 200px;
    }
    .room-card {
      flex-direction: column;
      align-items: flex-start;
    }
    .room-image {
      width: 100%;
      height: 150px;
    }
  }
</style>
</head>
<body>
 
<header><?=htmlspecialchars($hotel['name'])?></header>
 
<div class="container">
 
  <div class="hotel-header">
    <img src="<?=htmlspecialchars($hotel['image'] ?: 'https://via.placeholder.com/400x250?text=No+Image')?>" alt="<?=htmlspecialchars($hotel['name'])?>" class="hotel-image" />
    <div class="hotel-info">
      <h1><?=htmlspecialchars($hotel['name'])?></h1>
      <div class="stars"><?=starRating($hotel['rating'])?></div>
      <p><?=htmlspecialchars($hotel['description'])?></p>
      <ul class="amenities-list">
        <?php foreach(amenitiesList($hotel['amenities']) as $am): ?>
          <li><?=htmlspecialchars(trim($am))?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
 
  <h2>Available Rooms</h2>
 
  <?php if (count($rooms) == 0): ?>
    <p>No rooms available for selected dates.</p>
  <?php else: ?>
    <?php foreach ($rooms as $room): ?>
      <div class="room-card">
        <img src="<?=htmlspecialchars($room['image'] ?: 'https://via.placeholder.com/180x130?text=No+Image')?>" alt="<?=htmlspecialchars($room['room_type'])?>" class="room-image" />
        <div class="room-info">
          <h3><?=htmlspecialchars($room['room_type'])?></h3>
          <p><?=htmlspecialchars($room['description'])?></p>
          <p class="price">$<?=number_format($room['price'],2)?> / night</p>
          <a href="hotel.php?id=<?=htmlspecialchars($hotel['id'])?>&room_id=<?=htmlspecialchars($room['id'])?>&checkin=<?=htmlspecialchars($checkin)?>&checkout=<?=htmlspecialchars($checkout)?>" class="btn-select">Select</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
 
  <?php if ($selectedRoom): ?>
    <h2>Booking Details</h2>
    <form method="post" class="booking-form" action="booking.php">
      <input type="hidden" name="room_id" value="<?=htmlspecialchars($selectedRoom['id'])?>" />
      <input type="hidden" name="checkin" value="<?=htmlspecialchars($checkin)?>" />
      <input type="hidden" name="checkout" value="<?=htmlspecialchars($checkout)?>" />
 
      <label>Full Name</label>
      <input type="text" name="full_name" placeholder="Your full name" required />
 
      <label>Email</label>
      <input type="email" name="email" placeholder="Your email" required />
 
      <label>Phone Number</label>
      <input type="tel" name="phone" placeholder="Your phone number" required />
 
      <button type="submit">Confirm Booking</button>
    </form>
  <?php endif; ?>
 
</div>
 
</body>
</html>
 
