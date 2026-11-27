<link rel="stylesheet" href="comment_style.css">
<?php require_once 'charging_comment.php'?>
<div class="comment-section">
    <div class="commentary">
        <div class="flex_shit">
            <input type="text" id="fname" name="fname" class="pseudo-writing" placeholder="Votre nom ici ...">
            </input>
            <button class="button-submit">
                submit
            </button>
            <span class="stars" aria-label="Note <?= $rating ?> sur 5">
                ☆☆☆☆☆
            </span>
            <br>
        </div>
        <textarea type="text" class="text-field" placeholder="Votre commentaire ici ..."></textarea>
    </div>
    <?php foreach ($reviews as $review) {?>
    <div class="commentary">
        <div class="flex_shit">
            <?php echo $review["author"] . " : ";?>
            <span class="stars" aria-label="Note <?= $rating ?> sur 5">
                <?php echo $review["star_review"] . "/5 <br> <br>";?>
            </span>
        </div>
        <br>
        <?php echo $review["message"] . "<br>";?>
    </div>
    <?php  } ?>
</div>