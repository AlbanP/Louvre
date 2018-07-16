<?php
namespace App\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Service\CreateTicket;
use App\Entity\Ticket;

class TicketCreationListener
{
  /**
   * @var CreateTicket
   */
    private $createTicket;

    public function __construct(CreateTicket $createTicket)
    {
        $this->createTicket = $createTicket;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getObjectManager();
        $entity = $args->getObject();

        if (!$entity instanceof Ticket) {
            return;
        }

        $this->createTicket->createCode($em, $entity);
    }
}
