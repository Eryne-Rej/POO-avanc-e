<?php

class operatorMapper{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getOperatorFromId(int $id): array
    {
        $statement = $this->pdo->prepare(
            "SELECT tour_operator.*
            FROM tour_operator
            INNER JOIN destination ON FIND_IN_SET(tour_operator.id, destination.operator)
            WHERE destination.id = ?"
        );
        $statement->execute([$id]);

        return $statement->fetchAll();
    }
}
