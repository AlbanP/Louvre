<?php
namespace App\Service;

use App\Entity\Ticket;
use App\Service\DefineRate;

class CalculVisitors
{
    public function getCalculVisitors(Ticket $ticket)
    {
        $visitors = $ticket->getVisitors();
        $dateVisit = $ticket->getDateVisit();
        $totalPrice = 0;
        $nbVisitor = 0;
        // Define for each visitor the rate and calul the number of visitor and total price
        foreach ($visitors->toArray() as $visitor) {
            $dateVisit = $ticket->getDateVisit();
            $birthday = $visitor->getBirthday();
            $reduction = $visitor->getReduction();
            // Service Define Rate
            $defineRate = new DefineRate;
            $price = $defineRate->calculRate($birthday, $reduction, $dateVisit);
            $visitor->setRate($price);
            // Update visitor to Ticket (add id ticket to Visitor )
            $ticket->updateVisitor($visitor);
            // Calcul ticket total price and increase number visitor
            $totalPrice += $visitor->getRate();
            $nbVisitor++;
        }
        // Save to ticket number visitor and total price
        $ticket->setNbVisitor($nbVisitor);
        $ticket->setPrice($totalPrice);

        return $ticket;
    }

}