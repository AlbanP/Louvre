<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Ticket;

class CreateTicket
{
    private $prefix = 'TL';

    public function createCode(EntityManagerInterface $em, Ticket $ticket)
    {
        $id = $ticket->getId();
        $code = $this->prefix.$id;
        $ticket->setCode($code);
        //$em = $em->getDoctrine()->getManager();
        $em->persist($ticket);
        // Save ticket to DB
        $em->flush();
    }
}
