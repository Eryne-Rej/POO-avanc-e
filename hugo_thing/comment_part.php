<link rel="stylesheet" href="comment_style.css">
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
    <div class="commentary">
        <div class="flex_shit">
            Rémon oasar
            <span class="stars" aria-label="Note <?= $rating ?> sur 5">
                ★★★☆☆
            </span>
        </div>
        <br>
        <p>
            <?= include_once('charging_comment.php') ?>
        </p>
    </div>
</div>