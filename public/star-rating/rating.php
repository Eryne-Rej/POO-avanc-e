<?php
require_once "db.php";
?>

<form action="save_rating.php" method="POST">

    <input type="hidden" name="product_id" value="1">

    <div class="stars">
        <input type="radio" name="rating" value="5" id="star5">
        <label for="star5">★</label>

        <input type="radio" name="rating" value="4" id="star4">
        <label for="star4">★</label>

        <input type="radio" name="rating" value="3" id="star3">
        <label for="star3">★</label>

        <input type="radio" name="rating" value="2" id="star2">
        <label for="star2">★</label>

        <input type="radio" name="rating" value="1" id="star1">
        <label for="star1">★</label>
    </div>

    <button type="submit">Envoyer ma note</button>

</form>