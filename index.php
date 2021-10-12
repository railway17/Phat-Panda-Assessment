<?php

class Travel {
    private $id = NULL;
    private $createdAt = NULL;
    private $employeeName = NULL;
    private $departure = NULL;
    private $destination = NULL;
    private $price = 0;
    private $companyId = NULL;
    
    function __construct($data) {
        $this->id = $data->id;
        $this->createdAt = $data->createdAt;
        $this->employeeName = $data->employeeName;
        $this->departure = $data->departure;
        $this->destination = $data->destination;
        $this->price = $data->price;
        $this->companyId = $data->companyId;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getPrice() {
        return $this->price;
    }
    
    public function getCompanyId() {
        return $this->companyId;
    }
}
class Company {
    private $id = NULL;
    private $createdAt = NULL;
    private $name = "";
    private $parentId = NULL;
    private $cost = 0;
    private $children = [];
    
    function __construct($data) {
        $this->id = $data->id;
        $this->createdAt = $data->createdAt;
        $this->name = $data->name;
        $this->parentId = $data->parentId;
        $this->cost = 0;
    }
    public function getId() {
        return $this->id;
    }
    
    public function getParentId() {
        return $this->parentId;
    }
    
    public function addCost($price) {
        $this->cost += $price;
    }
    
    public function setChildren($children) {
        $this->children = $children;
    }
    
    public function getCost() {
        return $this->cost;
    }
    
    public function jsonSerialize() {
        $childrenData = array();
        
        if (count($this->children) > 0) {
            for($i = 0; $i < count($this->children); $i ++) {
                $childrenData[] = $this->children[$i]->jsonSerialize();
            }
        }
        
        return array("id" => $this->id, "name" => $this->name, "parentId" => $this->parentId, "cost" => $this->cost, "children" => $childrenData);
    }
    
    public function calcChildrenCost() {
        if (count($this->children) > 0) {
            $cost = $this->cost;
            for($i = 0; $i < count($this->children); $i ++) {
                $cost += $this->children[$i]->calcChildrenCost();
            }
            
            $this->cost = $cost;
            
            return $cost;
        }
        
        return $this->cost;
    }
}
class TestScript {
    public function buildTree($flatList) {
        $grouped = [];
        foreach ($flatList as $node){
            $grouped[$node->getParentId()][] = $node;
        }
    
        $fnBuilder = function($siblings) use (&$fnBuilder, $grouped) {
            foreach ($siblings as $k => $sibling) {
                $id = $sibling->getId();
                if(isset($grouped[$id])) {
                    $sibling->setChildren($fnBuilder($grouped[$id]));
                }
                $siblings[$k] = $sibling;
            }
            return $siblings;
        };
    
        return $fnBuilder($grouped[0]);    
    }
    
    public function execute() {
        $travels = file_get_contents('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels');
        $companies = file_get_contents('https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies');
        $jsonCompanies = json_decode($companies);
        $companyList = [];
        for($i = 0; $i < count($jsonCompanies); $i ++) {
            $companyList[] = new Company($jsonCompanies[$i]);
        }
        
        $jsonTravel = json_decode($travels);
        $travelList = [];
        
        $travelCost = 0;
        for($i = 0; $i < count($jsonTravel); $i ++) {
            $travelList[] = new Travel($jsonTravel[$i]);
        
            $travelCost +=$jsonTravel[$i]->price;
        }

        for($i = 0; $i < count($companyList); $i ++) {
            for($j = 0; $j < count($travelList); $j ++) {
                if ($companyList[$i]->getId() == $travelList[$j]->getCompanyId()) {
                    $companyList[$i]->addCost($travelList[$j]->getPrice());
                }
            }
        }

        $tree = $this->buildTree($companyList);

        if (count($tree) > 0) {
            $tree[0]->calcChildrenCost();
            echo json_encode($tree[0]->jsonSerialize());
        } else {
            echo json_encode(array());
        }
    }
}
(new TestScript())->execute();


