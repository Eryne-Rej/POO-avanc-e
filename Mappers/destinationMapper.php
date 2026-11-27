<?php

class destinationMapper{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAllDestinations(){
        $requestDestination = $this->pdo->query('SELECT * FROM destination');
        $destinations = $requestDestination->fetchAll();

        return $destinations;
    }

    public function getLocations(): array{
        $requestLocations = $this->pdo->query('SELECT DISTINCT location FROM destination ORDER BY location');

        return $requestLocations->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getIdFromLocation(string $location): ?int{
        $requestId = $this->pdo->prepare('SELECT id FROM destination WHERE location = :location LIMIT 1');
        $requestId->execute([
            'location' => $location
        ]);

        $id = $requestId->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function getInfosFromId(int $id){
        $requestInfos = $this->pdo->query("SELECT * FROM destination WHERE id = $id");
        $infos = $requestInfos->fetch();

        return $infos;
    }

}