<?php

class operatorMapper{
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getOperatorFromId(int $id){

        $requestTO = $this->pdo->prepare("SELECT tour_operator.* FROM tour_operator JOIN destination ON FIND_IN_SET(tour_operator.id, destination.operator) WHERE destination.id = :id;");
        $requestTO->execute([
            "id" => $id
        ]);
        
        $TO = $requestTO->fetchAll();
        return $TO;    
    }
}
