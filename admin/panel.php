<section class="admin-panel" aria-labelledby="admin-title">
    <div class="admin-panel-heading">
        <div>
            <p class="admin-eyebrow">Espace administration</p>
            <h1 id="admin-title">Gestion des tour-opérateurs</h1>
        </div>
    </div>

    <?php if ($adminMessage): ?>
        <p class="admin-notice" role="status"><?= htmlspecialchars($adminMessage) ?></p>
    <?php endif; ?>
    <?php if ($adminError): ?>
        <p class="admin-notice admin-notice-error" role="alert"><?= htmlspecialchars($adminError) ?></p>
    <?php endif; ?>

    <div class="admin-forms">
        <form class="admin-card" method="post">
            <h2>Ajouter un tour-opérateur</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["admin_csrf_token"]) ?>">
            <input type="hidden" name="action" value="create_operator">
            <label>
                Nom
                <input type="text" name="name" maxlength="250" required>
            </label>
            <label>
                Site web
                <input type="url" name="link" maxlength="300" placeholder="https://exemple.fr" required>
            </label>
            <fieldset class="admin-destination-options">
                <legend>Destinations à associer (facultatif)</legend>
                <?php foreach ($destinationCatalog as $destination): ?>
                    <label>
                        <input type="checkbox" name="destinations[]" value="<?= htmlspecialchars($destination[0]) ?>">
                        <?= htmlspecialchars($destination[1]) ?> — <?= htmlspecialchars($destination[2]) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <button type="submit">Ajouter le tour-opérateur</button>
        </form>

        <form class="admin-card" method="post">
            <h2>Ajouter des destinations à un TO</h2>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["admin_csrf_token"]) ?>">
            <input type="hidden" name="action" value="assign_destinations">
            <label>
                Tour-opérateur
                <select name="operator_id" required <?= $operators === [] ? "disabled" : "" ?>>
                    <option value="">Choisir un tour-opérateur</option>
                    <?php foreach ($operators as $operator): ?>
                        <option value="<?= (int) $operator["id"] ?>"><?= htmlspecialchars($operator["name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <fieldset class="admin-destination-options">
                <legend>Destinations disponibles</legend>
                <?php foreach ($destinationCatalog as $destination): ?>
                    <label>
                        <input type="checkbox" name="destinations[]" value="<?= htmlspecialchars($destination[0]) ?>">
                        <?= htmlspecialchars($destination[1]) ?> — <?= htmlspecialchars($destination[2]) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <button type="submit" <?= $operators === [] ? "disabled" : "" ?>>Ajouter les destinations</button>
        </form>
    </div>

    <div class="admin-list">
        <h2>Tour-opérateurs enregistrés</h2>
        <?php if ($operators === []): ?>
            <p>Aucun tour-opérateur à afficher.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Destinations</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operators as $operator): ?>
                            <tr>
                                <td>
                                    <a href="<?= htmlspecialchars($operator["link"]) ?>" target="_blank" rel="noopener noreferrer">
                                        <?= htmlspecialchars($operator["name"]) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($operator["destinations"] === [] ? "Aucune" : implode(", ", $operator["destinations"])) ?></td>
                                <td><?= (int) $operator["is_premium"] === 1 ? "Premium" : "Standard" ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["admin_csrf_token"]) ?>">
                                        <input type="hidden" name="action" value="toggle_premium">
                                        <input type="hidden" name="operator_id" value="<?= (int) $operator["id"] ?>">
                                        <button class="admin-secondary-button" type="submit">
                                            <?= (int) $operator["is_premium"] === 1 ? "Retirer premium" : "Passer premium" ?>
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Retirer ce tour-opérateur ? Ses destinations et ses avis seront également supprimés.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["admin_csrf_token"]) ?>">
                                        <input type="hidden" name="action" value="delete_operator">
                                        <input type="hidden" name="operator_id" value="<?= (int) $operator["id"] ?>">
                                        <button class="admin-secondary-button admin-danger-button" type="submit">Retirer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
