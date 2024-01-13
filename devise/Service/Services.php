<?php

namespace Xel\Devise\Service;

use Xel\Async\Router\Attribute\Router;

class Services
{
    #[Router('GET', '/example/{id}')]
    public function index(string $id): false|string
    {
        $data =  ["id" =>$id ,"name" => "yogi", "purpose"=>"testing data"];
        return json_encode($data);
    }
}