<?php
namespace App\Service;

use App\Entity\Ticket;

class Mailer
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

    public function sendError($e ,Ticket $ticket)
    {
        $date = new \DateTime();
        $text    = "<h3>Ticket Louvre - Erreur Stripe</h3>";
        $text   .="<p>Message erreur : <b>" . $e ."</b></p>";
        $text   .="<p>Date erreur : " . $date->format('d/m/Y H:i') ."</p>";
        $text   .="<p>Email utilisateur : " . $ticket->getEmail() ."</p>";

        $message = (new \Swift_Message('Ticket Louvre - Erreur Stripe'))
        ->setFrom('a.painchault@gmail.com')
        ->setTo('a.painchault@gmail.com')
        ->setBody(
            $this->templating->render(
            'emails/error.html.twig',
            array(
                'ticket' => $ticket,
                'error' => $e
            )),
        'text/html');

        $this->mailer->send($message);
    }

    public function sendTicket(Ticket $ticket)
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