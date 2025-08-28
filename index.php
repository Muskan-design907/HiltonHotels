<?php
require 'db.php';
 
// Fetch featured hotels (top 5 highest rating)
$stmt = $pdo->prepare("SELECT * FROM hotels ORDER BY rating DESC LIMIT 5");
$stmt->execute();
$featuredHotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
function starRating($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? true : false;
    $stars = '';
    for ($i=0; $i < $fullStars; $i++) {
        $stars .= '★';
    }
    if ($halfStar) {
        $stars .= '☆'; // Half star visually
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Hilton Hotels Clone - Home</title>
<style>
  /* Reset & basic styling */
  body { font-family: Arial, sans-serif; margin:0; background:#f7f7f7; }
  header { background:#003580; padding:15px; color:#fff; text-align:center; font-size:24px; font-weight:bold; }
  .container { max-width: 1100px; margin: auto; padding: 20px; }
  form.search-bar { display:flex; flex-wrap: wrap; gap:10px; justify-content: center; margin-bottom: 30px;}
  form input, form select, form button {
    padding: 10px; font-size: 16px; border: 1px solid #ccc; border-radius: 4px;
  }
  form button {
    background:#003580; color:#fff; border:none; cursor:pointer; transition: background 0.3s;
  }
  form button:hover { background:#002255; }
 
  /* Carousel */
  .carousel {
    display:flex;
    overflow-x: auto;
    gap: 20px;
    padding-bottom: 10px;
  }
  .carousel::-webkit-scrollbar { display: none; }
  .hotel-card {
    flex: 0 0 300px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
    overflow: hidden;
    cursor: pointer;
    transition: box-shadow 0.3s;
  }
  .hotel-card:hover {
    box-shadow: 0 6px 15px rgb(0 0 0 / 0.2);
  }
  .hotel-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
  }
  .hotel-info {
    padding: 15px;
  }
  .hotel-info h3 {
    margin: 0 0 8px;
  }
  .stars {
    color: #ffc107;
    font-size: 18px;
  }
  .location {
    color: #666;
    font-size: 14px;
  }
 
  /* Responsive */
  @media(max-width: 600px) {
    form.search-bar {
      flex-direction: column;
    }
    .hotel-card {
      flex: 0 0 90%;
    }
  }
</style>
</head>
<body>
 
<header>Hilton Hotels Clone</header>
 
<div class="container">
 
  <form class="search-bar" action="search_results.php" method="get" autocomplete="off">
    <input type="text" name="location" placeholder="Enter destination" required />
    <input type="date" name="checkin" placeholder="Check-in date" required min="<?=date('Y-m-d')?>" />
    <input type="date" name="checkout" placeholder="Check-out date" required min="<?=date('Y-m-d', strtotime('+1 day'))?>" />
    <button type="submit">Search</button>
  </form>
 
  <h2>Featured Hotels</h2>
  <div class="carousel">
    <?php foreach ($featuredHotels as $hotel): ?>
      <a href="hotel.php?id=<?=htmlspecialchars($hotel['id'])?>" class="hotel-card" title="<?=htmlspecialchars($hotel['name'])?>">
        <img src="<?=htmlspecialchars($hotel['image'] ?: 'https://via.placeholder.com/300x180?text=No+Image')?>" alt="<?=htmlspecialchars($hotel['name'])?>" class="hotel-image" />
        <div class="hotel-info">
          <h3><?=htmlspecialchars($hotel['name'])?></h3>
          <div class="stars"><?=starRating($hotel['rating'])?></div>
          <div class="location"><?=htmlspecialchars($hotel['location'])?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
 
</div>
 
</body>
</html>
 
