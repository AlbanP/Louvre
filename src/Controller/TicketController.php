<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\DefineRate;
use App\Service\PaymentStripe;
use App\Service\CalculVisitors;
use App\Service\EasterDate;
use App\Service\CheckDate;
use App\Entity\Ticket;
use App\Entity\Rate;
use App\Entity\Bill;
use App\Entity\Calendar;
use App\Form\TicketType;
use App\Form\BillType;

class TicketController extends Controller
{
    /**
     * @Route({"en" : "/", "fr" : "/"}, name="home", requirements={"_locale": "en|fr"})
    */
    public function index(Request $request)
    {
        //Define DateTime Paris and Offset / UTC
        $dateTimeParis = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $timeZoneOffset = $dateTimeParis->getOffset();

        // If the visitor has slecteted dateVisit and day or half-day
        if ($request->isMethod('POST')) {
            $dateVisit = $request->request->get('date-select');
            if (! empty($dateVisit)){
                // Check valid date visit
                $dateVisit = new \DateTime($dateVisit);
                $checkDate = new CheckDate();
                if (!$checkDate->check($dateVisit, $dateTimeParis)){
                    echo 'test';
                    return $this->redirectToRoute('home');
                }
                $ticket = new Ticket();
                // Add date visit to ticket
                $ticket->setDateVisit($dateVisit);
                // If visitor select half-day -> true
                $half_day = $request->request->get('half-day-button');
                if (isset($half_day)) {
                    $ticket->setHalfDay(true);
                }
                // Save ticket to session 
                $request->getSession()->set('ticket', $ticket);
                
                return $this->redirectToRoute('selectVisitor');
            }
            $this->addFlash('notice', "Please choose a date");
        }

        $locale = $request->getLocale();
        // 
        $ticket = $request->getSession()->get('ticket');
        if (is_null($ticket)){
            $dateVisit = null;
        } else {
            $dateVisit = $ticket->getDateVisit();
        }
        // find rate and save to session
        $repository = $this->getDoctrine()->getRepository(Rate::class);
        $listRate = $repository->findAll();
        //$request->getSession()->set('listRate', $listRate);
        
        $repository = $this->getDoctrine()->getRepository(Calendar::class);
        $listDays = $repository->showDays();
        $easter = new EasterDate;
        $easterDate = $easter->getEasterDateYearCurrent();

        return $this->render('ticket/index.html.twig', array(
            'dateVisit' => $dateVisit,
            'list_rate' => $listRate,
            'list_days' => $listDays,
            'easter_date'=> $easterDate,
            'timeZoneOffset' => $timeZoneOffset,
            'local' => $locale
        ));
    }
    /**
     * @Route({"en" : "/visitors", "fr" : "/visiteurs"}, name="selectVisitor", requirements={"_locale": "en|fr"})
    */
    //Route("/visiteurs", name="selectVisitor")
    public function selectVisitor(Request $request)
    {
        // Load ticket and rate by session
        $ticket = $request->getSession()->get('ticket');
        if (! $ticket->getDateVisit()) {
            return $this->redirectToRoute('home');
        }
        // Create form for the visitors
        $form = $this->get('form.factory')->create(TicketType::class, $ticket);
        // When the visitor select pay
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Check if have 1 visitor
            if ($ticket->getVisitors()->isEmpty()) {
                return $this->redirectToRoute('selectVisitor');
            }
            // Calcul the rate visitor, the number of visitor and price Ticket
            //$ticketVisitor = $this->calculVisitor($ticket);
            $calculVisitors = new CalculVisitors();
            $ticketVisitor = $calculVisitors->getCalculVisitors($ticket);
            
            $request->getSession()->set('ticket', $ticketVisitor);
            //$request->getSession()->getFlashBag()->add('notice', 'Visiteur bien enregistré.');

            return $this->redirectToRoute('payment');
        }
        $locale = $request->getLocale();
        $listRate = $request->getSession()->get('listRate');

        return $this->render('ticket/selectVisitor.html.twig', array(
            'form' => $form->createView(),
            'ticket' => $ticket,
            'local' => $locale));
    }
    /**
     * @Route({"en" : "/payment", "fr" : "/paiement"}, name="payment", requirements={"_locale": "en|fr"})
    */
    public function payment(Request $request, \Swift_Mailer $mailer, PaymentStripe $payment)
    {
        $locale = $request->getLocale();
        $ticket = $request->getSession()->get('ticket');
        if (is_null($ticket->getNbVisitor())) {
            return $this->redirectToRoute('selectVisitor');
        }

        if ($request->isMethod('POST')) {
            $token = $request->request->get('stripeToken');
            $ticket->setEmail($request->request->get('email'));
            $dateVisit = $ticket->getDateVisit();
            $nbVisitor = $ticket->getNbVisitor();
            // Service payment Stripe
            $response = $payment->charge($token, $ticket, array(
                "Email" => $ticket->getEmail(),
                "Nombre de Ticket" => $nbVisitor,
                "Date de visite" => $dateVisit->format('d/m/Y')
            ));
            if (is_object($response)){
                // Save date and number visitor to calendar
                $repository = $this->getDoctrine()->getRepository(Calendar::class);
                $calendar = $repository->findOneByDay($dateVisit);
                if(is_null($calendar)) {
                    $calendar = new Calendar();
                    $calendar->setDay($dateVisit);
                    $calendar->setNbVisitor($nbVisitor);
                } else {
                    $nbVisitorDay = $calendar->getNbVisitor() + $nbVisitor ;
                    $calendar->setNbVisitor($nbVisitorDay);
                }

                // Save data response to bill entity
                $bill = new Bill();
                $bill->setTransactionId($response['id']);
                $dateBill = new \DateTime();
                $dateBill->setTimestamp($response['created']);
                $bill->setDateBill($dateBill);
                $bill->setPrice($response['amount']);
                $ticket->setBill($bill);

                $em = $this->getDoctrine()->getManager();
                $em->persist($ticket);
                $em->persist($calendar);
                // Save ticket to DB
                $em->flush();
            
                return $this->redirectToRoute('showTicket');
            }
            $this->addFlash('notice', "Sorry, but an error occurred, try again");
        }
        return $this->render('ticket/payment.html.twig', array(
            'ticket' => $ticket,
            'local' => $locale));
    }
    /**
     * @Route({"en" : "/ticket", "fr" : "/ticket"}, name="showTicket", requirements={"_locale": "en|fr"})
    */
    public function showTicket(Request $request)
    {
        $locale = $request->getLocale();
        $ticket = $request->getSession()->get('ticket');
        if (is_null($ticket->getBill())) {
            return $this->redirectToRoute('payment');
        }

        return $this->render('ticket/showTicket.html.twig', array(
            'ticket' => $ticket,
            'local' => $locale));
    }

    /*
     * Change the locale for the current user
     *
     * @param String $language
     * @return array
     *
     * @Route("/{language}", name="setlocale")
     * @Template()
    */
    public function setLocale($language = null)
    {
        if($language != null) {
            // On enregistre la langue en session
            $this->get('session')->set('_locale', $language);
        }
    
        // on tente de rediriger vers la page d'origine
        $url = $this->container->get('request')->headers->get('referer');
        if(empty($url))
        {
            $url = $this->container->get('router')->generate('index');
        }
    
        return new RedirectResponse($url);
   }
}
