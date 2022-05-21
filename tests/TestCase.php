<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function assertArrayHasObjectWithElementValue($array, $object, $element)
    {
        foreach ($array as $arrayItem) {
            if ($arrayItem->$element === $object->$element) {
                return true;
            }
        }
        return false;
    }
}
