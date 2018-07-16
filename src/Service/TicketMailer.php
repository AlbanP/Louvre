<?php
namespace App\Service;

use App\Entity\Ticket;

class TicketMailer
{
    /**
    * @var \Swift_Mailer
    */
    private $mailer;
    private $templating;

    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $templating)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    public function sendNewNotification(Ticket $ticket)
    {
        $message = (new \Swift_Message('Ticket Louvre'))
        ->setFrom('a.painchault@gmail.com')
        ->setTo($ticket->getEmail())
        ->setBody(
            $this->templating->render(
            'emails/ticket.html.twig',
            array('ticket' => $ticket)
        ),
        'text/html');

        $this->mailer->send($message);
    }
}
