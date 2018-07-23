<?php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Service\CheckDate;

class CheckDateTest extends WebTestCase
{
    public function testCheck()
    {
        $checkDate = new CheckDate();
        $dateVisit = new \DateTime('2018-07-23');
        $today = new \DateTime('now');
        $result = $checkDate->check($dateVisit, $today);

        $this->assertSame(true, $result);
    }
}