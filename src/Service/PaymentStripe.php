<?php
namespace App\Service;

use App\Service\ErrorStripeMailer;
use App\Entity\Ticket;

class PaymentStripe
{
    private $secretKey = 'sk_test_lZReZR3lqdyyQmSsCnmAUOtQ';
    private $current = 'eur';
    private $description = 'TicketLouvre';
    private $mailer;

    public function __construct(ErrorStripeMailer $mailer)
    {
        $this->mailer = $mailer;
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
            
        } catch(\Stripe\Error\Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $response = $this->actionError($e, $ticket);
        } catch (\Stripe\Error\RateLimit $e) {
            // Too many requests made to the API too quickly
            $response = $this->actionError($e, $ticket);
        } catch (\Stripe\Error\InvalidRequest $e) {
            // Invalid parameters were supplied to Stripe's API
            $response = $this->actionError($e, $ticket);
        } catch (\Stripe\Error\Authentication $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $response = $this->actionError($e, $ticket);
        } catch (\Stripe\Error\ApiConnection $e) {
            // Network communication with Stripe failed
            $response = $this->actionError($e, $ticket);
        } catch (\Stripe\Error\Base $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            $response = $this->actionError($e, $ticket);
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            $response = $this->actionError($e, $ticket);
        }

          
        return $response;
    }

    protected function actionError($e, $ticket) {
        $this->mailer->sendError($e, $ticket);
        $error = $e->getMessage();

        return $error;
    }
}