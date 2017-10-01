<?php

namespace Test\Mock\Service;

class TwoMethodService
{
    public function add($i, $j)
    {
        return $i + $j;
    }

    public function retYes()
    {
        return 'yes';
    }

    public static function staticRet()
    {
        return 'static-yes';
    }

    protected static function staticProtectedRet()
    {
        return 'static-protected-yes';
    }

    private static function staticPrivateRet()
    {
        return 'static-private-yes';
    }

    private function privateRet()
    {
        return 'private';
    }

    protected function protectedRet()
    {
        return 'protected';
    }
}