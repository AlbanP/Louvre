<?php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Service\CalculVisitors;
use App\Entity\Ticket;

class CalculVisitorsTest extends WebTestCase
{
    public function testGetCalculVisitors()
    {
        $calculVisitors = new CalculVisitors();
        $ticket = new Ticket();
        $DateVisit = new \DateTime();
        $ticket->setDateVisit($DateVisit);

        $result = $calculVisitors->getCalculVisitors($ticket);

        $this->assertSame(true, $result);
    }
}