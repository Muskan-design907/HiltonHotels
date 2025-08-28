<?php
require 'db.php';
 
$location = $_GET['location'] ?? '';
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$rating_min = $_GET['rating_min'] ?? '';
$amenities = $_GET['amenities'] ?? ''; // comma separated amenities filter
$sort = $_GET['sort'] ?? 'price_asc';
 
// Validate dates (simple)
if (!$location || !$checkin || !$checkout || strtotime($checkin) >= strtotime($checkout)) {
    die('Please provide valid search parameters.');
}
 
// Build query for rooms joined with hotels matching search
$sql = "SELECT r.*, h.name as hotel_name, h.location, h.rating, h.image as hotel_image, h.amenities as hotel_amenities
        FROM rooms r 
        JOIN hotels h ON r.hotel_id = h.id 
        WHERE h.location LIKE :location 
          AND r.available_from <= :checkin AND r.available_to >= :checkout";
 
// Price filter
$params = [
    ':location' => "%$location%",
    ':checkin' => $checkin,
    ':checkout' => $checkout
];
 
if ($price_min !== '') {
    $sql .= " AND r.price >= :price_min ";
    $params[':price_min'] = $price_min;
}
if ($price_max !== '') {
    $sql .= " AND r.price <= :price_max ";
    $params[':price_max'] = $price_max;
}
if ($rating_min !== '') {
    $sql .= " AND h.rating >= :rating_min ";
    $params[':rating_min'] = $rating_min;
}
if ($amenities) {
    $ams = explode(',', $amenities);
    foreach ($ams as $index => $amen) {
        $amen = trim($amen);
        $sql .= " AND h.amenities LIKE :amenity$index ";
        $params[":amenity$index"] = "%$amen%";
    }
}
 
// Sorting
switch ($sort) {
    case 'price_desc': $sql .= " ORDER BY r.price DESC"; break;
    case 'rating_desc': $sql .= " ORDER BY h.rating DESC"; break;
    case 'rating_asc': $sql .= " ORDER BY h.rating ASC"; break;
    default: $sql .= " ORDER BY r.price ASC"; // price_asc default
}
 
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
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
 
function amenitiesIcons($amenitiesText) {
    $icons = '';
    $ams = explode(',', strtolower($amenitiesText));
    foreach ($ams as $am) {
        $am = trim($am);
        if ($am == 'wifi') $icons .= '📶 ';
        elseif ($am == 'pool') $icons .= '🏊 ';
        elseif ($am == 'parking') $icons .= '🅿️ ';
        elseif ($am == 'gym') $icons .= '🏋️ ';
        elseif ($am == 'spa') $icons .= '💆 ';
        elseif ($am == 'restaurant') $icons .= '🍴 ';
        // add more icons as needed
    }
    return $icons;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Search Results - Hilton Hotels Clone</title>
<style>
  body { font-family: Arial, sans-serif; margin:0; background:#f7f7f7; }
  header { background:#003580; padding:15px; color:#fff; text-align:center; font-size:24px; font-weight:bold; }
  .container { max-width: 1100px; margin: auto; padding: 20px; }
 
  .filters {
    background:#fff; padding:15px; border-radius: 6px; margin-bottom: 20px;
    display: flex; flex-wrap: wrap; gap: 15px; align-items: center;
  }
  .filters label {
    font-weight: bold;
    margin-right: 5px;
  }
  .filters select, .filters input[type=number], .filters input[type=text] {
    padding: 6px; border: 1px solid #ccc; border-radius: 4px;
    width: 100px;
  }
  .filters button {
    background:#003580; color:#fff; border:none; padding: 8px 15px; border-radius: 4px; cursor:pointer;
    transition: background 0.3s;
  }
  .filters button:hover { background:#002255; }
 
  .hotel-list {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(300px,1fr));
    gap: 20px;
  }
  .hotel-card {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
    display: flex;
    flex-direction: column;
  }
  .hotel-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
  }
  .hotel-info {
    padding: 15px;
    flex-grow: 1;
  }
  .hotel-info h3 {
    margin: 0 0 8px;
  }
  .stars {
    color: #ffc107;
    font-size: 18px;
    margin-bottom: 6px;
  }
  .location {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
  }
  .amenities {
    font-size: 20px;
    margin-bottom: 10px;
  }
  .price {
    font-weight: bold;
    font-size: 18px;
    color: #003580;
  }
  .btn-book {
    background: #003580;
    color: #fff;
    border: none;
    padding: 10px;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    font-size: 16px;
    text-align: center;
    text-decoration: none;
  }
  .btn-book:hover {
    background: #002255;
  }
  @media(max-width: 600px) {
    .filters {
      flex-direction: column;
      gap: 10px;
    }
    .filters select, .filters input[type=number], .filters input[type=text] {
      width: 100%;
    }
  }
</style>
</head>
<body>
 
<header>Hilton Hotels Clone - Search Results</header>
 
<div class="container">
 
  <form method="get" class="filters">
    <input type="hidden" name="location" value="<?=htmlspecialchars($location)?>">
    <input type="hidden" name="checkin" value="<?=htmlspecialchars($checkin)?>">
    <input type="hidden" name="checkout" value="<?=htmlspecialchars($checkout)?>">
    <label>Price Min:</label>
    <input type="number" name="price_min" value="<?=htmlspecialchars($price_min)?>" min="0" step="1" />
    <label>Price Max:</label>
    <input type="number" name="price_max" value="<?=htmlspecialchars($price_max)?>" min="0" step="1" />
    <label>Rating Min:</label>
    <select name="rating_min">
      <option value="">Any</option>
      <?php for($r=1;$r<=5;$r++): ?>
        <option value="<?=$r?>" <?=($rating_min == $r) ? 'selected' : ''?>><?=$r?>+</option>
      <?php endfor; ?>
    </select>
    <label>Amenities:</label>
    <input type="text" name="amenities" placeholder="wifi,pool,etc" value="<?=htmlspecialchars($amenities)?>" />
    <label>Sort:</label>
    <select name="sort">
      <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : ''?>>Price Low to High</option>
      <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : ''?>>Price High to Low</option>
      <option value="rating_desc" <?= $sort == 'rating_desc' ? 'selected' : ''?>>Rating High to Low</option>
      <option value="rating_asc" <?= $sort == 'rating_asc' ? 'selected' : ''?>>Rating Low to High</option>
    </select>
    <button type="submit">Filter</button>
  </form>
 
  <?php if (count($results) == 0): ?>
    <p>No rooms found for your search criteria.</p>
  <?php else: ?>
    <div class="hotel-list">
      <?php foreach ($results as $room): ?>
        <div class="hotel-card">
          <img class="hotel-image" src="<?=htmlspecialchars($room['image'] ?: 'https://via.placeholder.com/300x180?text=No+Image')?>" alt="<?=htmlspecialchars($room['room_type'])?>" />
          <div class="hotel-info">
            <h3><?=htmlspecialchars($room['hotel_name'])?></h3>
            <div class="stars"><?=starRating($room['rating'])?></div>
            <div class="location"><?=htmlspecialchars($room['location'])?></div>
            <div class="amenities"><?=amenitiesIcons($room['hotel_amenities'])?></div>
            <p><strong>Room:</strong> <?=htmlspecialchars($room['room_type'])?></p>
            <p><?=htmlspecialchars($room['description'])?></p>
            <div class="price">$<?=number_format($room['price'],2)?> / night</div>
            <a href="hotel.php?id=<?=htmlspecialchars($room['hotel_id'])?>&room_id=<?=htmlspecialchars($room['id'])?>&checkin=<?=htmlspecialchars($checkin)?>&checkout=<?=htmlspecialchars($checkout)?>" class="btn-book">Book Now</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
 
</div>
 
</body>
</html>
 
