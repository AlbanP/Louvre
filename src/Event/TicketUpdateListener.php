<?php
namespace App\Event;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Service\TicketMailer;
use App\Entity\Ticket;

class TicketUpdateListener
{
  /**
   * @var TicketMailer
   */
    private $ticketMailer;

    public function __construct(TicketMailer $ticketMailer)
    {
        $this->ticketMailer = $ticketMailer;
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Ticket) {
            return;
        }

        $this->ticketMailer->sendNewNotification($entity);
    }
}
