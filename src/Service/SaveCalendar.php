<?php
namespace App\Service;

use App\Entity\Calendar;
use Doctrine\ORM\EntityManagerInterface;

class SaveCalendar
{
    private $em;
    
    public function __constructor(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function save($dateVisit, $nbVisitor)
    {
        $repository = $this->em->getRepository(Calendar::class);
        $calendar = $repository->findOneByDay($dateVisit);
        if(is_null($calendar)) {
            $calendar = new Calendar();
            $calendar->setDay($dateVisit);
            $calendar->setNbVisitor($nbVisitor);
        } else {
            $nbVisitorDay = $calendar->getNbVisitor() + $nbVisitor ;
            $calendar->setNbVisitor($nbVisitorDay);
        }
    }
}