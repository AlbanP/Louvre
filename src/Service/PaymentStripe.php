<?php
namespace App\Service;

use App\Service\Mailer;
use App\Entity\Ticket;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class PaymentStripe
{
    private $secretKey = 'sk_test_lZReZR3lqdyyQmSsCnmAUOtQ';
    private $current = 'eur';
    private $description = 'TicketLouvre';
    private $mailer;
    private $session;
    private $router;

    public function __construct(Mailer $mailer, SessionInterface $session, RouterInterface $router)
    {
        $this->mailer = $mailer;
        $this->session = $session;
        $this->router = $router;
    }

    public function charge($token, $ticket, $array)
    {
        try {
            \Stripe\Stripe::setApiKey($this->secretKey);
            $response = \Stripe\Charge::create(array(
                        "amount" => $ticket->getPrice(),
                        "currency" => $this->current,
                        "source" => $token,
                        "description" => $this->description,
                        "metadata" => $array));
            
            return $response;
            
        } catch(\Stripe\Error\Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $response = $e->getMessage();
        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            $response = $e->getMessage();
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            $response = $e->getMessage();
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $response = $e->getMessage();
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            $response = $e->getMessage();
        } catch (\Stripe\Error\Base $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            $response = $e->getMessage();
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            $response = $e->getMessage();
        }

        $this->mailer->sendError($response, $ticket);
        $this->session->getFlashBag()->add('notice', "Sorry, but an error occurred, try again");

        return null;
        // new RedirectResponse($this->router->generate('payment'));
    }
}