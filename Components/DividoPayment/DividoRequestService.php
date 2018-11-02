<?php

namespace DividoPayment\Components\DividoPayment;

class DividoRequestService
{
    private $request = [];

    public function setRequestField($key,$value){
        $this->request[$key] = $value;
    }

    public function getRequestField($key){
        return (isset($this->request[$key])) ? $this->request[$key] : false;
    }

    public function setRequestFieldsByArray(Array $fields){
        $this->request = array_merge($this->request,$fields);
    }

    public function makeRequest(){
        $response = \Divido_CreditRequest::create($this->request);
        return $response;
    }

    public function dumpRequest(){
        var_dump($this->request);
    }
}