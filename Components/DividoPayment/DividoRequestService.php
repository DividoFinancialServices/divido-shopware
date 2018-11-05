<?php

namespace DividoPayment\Components\DividoPayment;

class DividoRequestService
{
    private $request = [];

    public function setRequestField($key,$value){
        $cloned = clone $this;
        $cloned->request[$key] = $value;
        return $cloned;
    }

    public function getRequestField($key){
        return (isset($this->request[$key])) ? $this->request[$key] : false;
    }

    public function setRequestFieldsByArray(Array $fields){
        $cloned = clone $this;
        $cloned->request = array_merge($cloned->request,$fields);
        return $cloned;
    }

    public function makeRequest(){
        $response = \Divido_CreditRequest::create($this->request);
        return $response;
    }

    public function dumpRequest(){
        var_dump($this->request);
    }
}