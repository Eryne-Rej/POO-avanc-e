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

}