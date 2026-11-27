<?php
if (!defined("GOFILM_ADMIN_VIEW")) {
    define("GOFILM_ADMIN_VIEW", true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$destinationCatalog = require __DIR__ . "/../Entities/destinations.php";
$destinationsById = [];
foreach ($destinationCatalog as $destination) {
    $destinationsById[$destination[0]] = $destination;
}
    
$adminMessage = $_SESSION["admin_message"] ?? null;
unset($_SESSION["admin_message"]);
$adminError = null;
$operators = [];
$pdo = null;

try {
    $pdo = require __DIR__ . "/../utils/database.php";
} catch (PDOException $exception) {
    error_log("GoFilm admin database connection failed: " . $exception->getMessage());
    $adminError = "Connexion à la base de données impossible. Vérifiez la configuration MySQL.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    if (!is_string($csrfToken) || !isset($_SESSION["admin_csrf_token"]) || !hash_equals($_SESSION["admin_csrf_token"], $csrfToken)) {
        $adminError = "La session a expiré. Rechargez la page puis réessayez.";
    } elseif ($pdo === null) {
        $adminError = "L'action est impossible tant que la base de données n'est pas disponible.";
    } else {
        try {
            $action = $_POST["action"] ?? "";
            if (!is_string($action)) {
                throw new InvalidArgumentException("Action invalide.");
            }

            if ($action === "create_operator") {
                $name = trim(is_string($_POST["name"] ?? null) ? $_POST["name"] : "");
                $link = trim(is_string($_POST["link"] ?? null) ? $_POST["link"] : "");
                $urlParts = parse_url($link);

                if ($name === "" || strlen($name) > 250) {
                    throw new InvalidArgumentException("Le nom est obligatoire et ne doit pas dépasser 250 caractères.");
                }
                if (strlen($link) > 300 || filter_var($link, FILTER_VALIDATE_URL) === false
                    || !in_array($urlParts["scheme"] ?? "", ["http", "https"], true)) {
                    throw new InvalidArgumentException("Saisissez une adresse web valide en HTTP ou HTTPS.");
                }

                $selectedIds = $_POST["destinations"] ?? [];
                if (!is_array($selectedIds)) {
                    throw new InvalidArgumentException("La sélection de destinations est invalide.");
                }
                $selectedDestinations = [];
                foreach ($selectedIds as $selectedId) {
                    if (!is_string($selectedId) || !isset($destinationsById[$selectedId])) {
                        throw new InvalidArgumentException("La sélection contient une destination inconnue.");
                    }
                    $selectedDestinations[$selectedId] = $destinationsById[$selectedId];
                }

                $pdo->beginTransaction();
                $insertOperator = $pdo->prepare(
                    "INSERT INTO tour_operator (name, link, grade_count, grade_total, is_premium) VALUES (?, ?, 0, 0, 0)"
                );
                $insertOperator->execute([$name, $link]);
                $operatorId = (int) $pdo->lastInsertId();

                $insertDestination = $pdo->prepare(
                    "INSERT INTO destination (location, price, tour_operator_id) VALUES (?, ?, ?)"
                );
                foreach ($selectedDestinations as $destination) {
                    $insertDestination->execute([
                        $destination[1],
                        (int) preg_replace("/\D/", "", $destination[3]),
                        $operatorId
                    ]);
                }
                $pdo->commit();
                $_SESSION["admin_message"] = "Le tour-opérateur a été ajouté.";
                header("Location: index.php");
                exit;
            }

            if ($action === "assign_destinations") {
                $operatorId = filter_var($_POST["operator_id"] ?? null, FILTER_VALIDATE_INT);
                $selectedIds = $_POST["destinations"] ?? [];
                if ($operatorId === false || $operatorId === null || $operatorId < 1 || !is_array($selectedIds)) {
                    throw new InvalidArgumentException("Sélectionnez un tour-opérateur et des destinations valides.");
                }
                if ($selectedIds === []) {
                    throw new InvalidArgumentException("Sélectionnez au moins une destination.");
                }

                $selectedDestinations = [];
                foreach ($selectedIds as $selectedId) {
                    if (!is_string($selectedId) || !isset($destinationsById[$selectedId])) {
                        throw new InvalidArgumentException("La sélection contient une destination inconnue.");
                    }
                    $selectedDestinations[$selectedId] = $destinationsById[$selectedId];
                }

                $pdo->beginTransaction();
                $operatorExists = $pdo->prepare("SELECT id FROM tour_operator WHERE id = ?");
                $operatorExists->execute([$operatorId]);
                if (!$operatorExists->fetch()) {
                    throw new InvalidArgumentException("Le tour-opérateur sélectionné n'existe pas.");
                }

                $findDestination = $pdo->prepare(
                    "SELECT id FROM destination WHERE tour_operator_id = ? AND location = ?"
                );
                $insertDestination = $pdo->prepare(
                    "INSERT INTO destination (location, price, tour_operator_id) VALUES (?, ?, ?)"
                );
                $addedCount = 0;
                foreach ($selectedDestinations as $destination) {
                    $findDestination->execute([$operatorId, $destination[1]]);
                    if ($findDestination->fetch()) {
                        continue;
                    }
                    $insertDestination->execute([
                        $destination[1],
                        (int) preg_replace("/\D/", "", $destination[3]),
                        $operatorId
                    ]);
                    $addedCount++;
                }
                $pdo->commit();
                $_SESSION["admin_message"] = $addedCount > 0
                    ? "{$addedCount} destination(s) ajoutée(s) au tour-opérateur."
                    : "Ces destinations sont déjà associées à ce tour-opérateur.";
                header("Location: index.php");
                exit;
            }

            if ($action === "toggle_premium") {
                $operatorId = filter_var($_POST["operator_id"] ?? null, FILTER_VALIDATE_INT);
                if ($operatorId === false || $operatorId === null || $operatorId < 1) {
                    throw new InvalidArgumentException("Tour-opérateur invalide.");
                }
                $updatePremium = $pdo->prepare("UPDATE tour_operator SET is_premium = 1 - is_premium WHERE id = ?");
                $updatePremium->execute([$operatorId]);
                if ($updatePremium->rowCount() !== 1) {
                    throw new InvalidArgumentException("Le tour-opérateur sélectionné n'existe pas.");
                }
                $_SESSION["admin_message"] = "Le statut premium a été mis à jour.";
                header("Location: index.php");
                exit;
            }

            if ($action === "delete_operator") {
                $operatorId = filter_var($_POST["operator_id"] ?? null, FILTER_VALIDATE_INT);
                if ($operatorId === false || $operatorId === null || $operatorId < 1) {
                    throw new InvalidArgumentException("Tour-opérateur invalide.");
                }

                $pdo->beginTransaction();
                $findOperator = $pdo->prepare("SELECT id FROM tour_operator WHERE id = ? FOR UPDATE");
                $findOperator->execute([$operatorId]);
                if (!$findOperator->fetch()) {
                    throw new InvalidArgumentException("Le tour-opérateur sélectionné n'existe pas.");
                }

                $deleteReviews = $pdo->prepare("DELETE FROM review WHERE tour_operator_id = ?");
                $deleteReviews->execute([$operatorId]);
                $deleteDestinations = $pdo->prepare("DELETE FROM destination WHERE tour_operator_id = ?");
                $deleteDestinations->execute([$operatorId]);
                $deleteOperator = $pdo->prepare("DELETE FROM tour_operator WHERE id = ?");
                $deleteOperator->execute([$operatorId]);
                $pdo->commit();

                $_SESSION["admin_message"] = "Le tour-opérateur, ses destinations et ses avis ont été supprimés.";
                header("Location: index.php");
                exit;
            }

            throw new InvalidArgumentException("Action inconnue.");
        } catch (InvalidArgumentException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $adminError = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("GoFilm admin database action failed: " . $exception->getMessage());
            $adminError = "L'opération a échoué. Vérifiez la base de données et réessayez.";
        }
    }
}

if ($pdo !== null) {
    try {
        $operators = $pdo->query(
            "SELECT id, name, link, is_premium FROM tour_operator ORDER BY name"
        )->fetchAll();
        $operatorDestinations = $pdo->query(
            "SELECT tour_operator_id, location FROM destination ORDER BY location"
        )->fetchAll();
        $operatorsById = [];
        foreach ($operatorDestinations as $operatorDestination) {
            $operatorsById[(int) $operatorDestination["tour_operator_id"]][] = $operatorDestination["location"];
        }
        foreach ($operators as &$operator) {
            $operator["destinations"] = $operatorsById[(int) $operator["id"]] ?? [];
        }
        unset($operator);
    } catch (PDOException $exception) {
        error_log("GoFilm admin data loading failed: " . $exception->getMessage());
        $adminError = "Impossible de charger les tour-opérateurs. Vérifiez la structure de la base de données.";
    }
}

if (!isset($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}

require __DIR__ . "/../index.php";
