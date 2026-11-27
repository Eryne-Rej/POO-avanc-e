<?php

class destinationMapper{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllDestinations(): array
    {
        return $this->pdo
            ->query("SELECT * FROM destination ORDER BY id")
            ->fetchAll();
    }

    public function getInfosFromId(int $id): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM destination WHERE id = ?");
        $statement->execute([$id]);
        $destination = $statement->fetch();

        return $destination === false ? null : $destination;
    }

}