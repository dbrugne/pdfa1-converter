<?php

namespace Eyefinity\Model;

use Eyefinity\Orm\EntityManager;

class ConversionManager extends EntityManager
{
    /** @var string */
    protected $table = 'conversions';

    /** @var string */
    protected $entityClass = "\\Eyefinity\\Model\\Conversion";
}