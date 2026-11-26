<?php
    require_once "./Mappers/operatorMapper.php";

    $mapper = new operatorMapper($pdo);

    var_dump($_GET['id']);
    $mapper->getOperatorFromId($_GET['id'])

?>