<?php
namespace App\LocalClass;

use Phalcon\Di\DiInterface;

abstract class Data{
    /**
     * @var $container DiInterface
    */
    protected $container;
    function __construct(DiInterface $container
    ) {
        $this->container = $container;
    }

}