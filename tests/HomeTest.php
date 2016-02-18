<?php
/**
 * Created by PhpStorm.
 * User: blackscorp
 * Date: 18.02.16
 * Time: 20:11
 */

namespace Logd\Core\Test;


class HomeTest extends \PHPUnit_Framework_TestCase
{
    public function testCanSeeHome(){
         ob_start();
        include __DIR__.'/../home.php';
        $result = ob_get_clean();
        echo $result;
    }
}