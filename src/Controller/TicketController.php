<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PaymentStripe;
use App\Service\CalculVisitors;
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
    public function index(Request $request, CheckDate $checkDate)
    {
        //Define DateTime Paris and Offset / UTC
        $locale = $request->getLocale();
        $dateTimeParis = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $timeZoneOffsetParis = $dateTimeParis->getOffset();

        // If the visitor has slecteted dateVisit and day or half-day
        if ($request->isMethod('POST')) {
            $dateVisit = $request->request->get('date-select');
            $half_day = $request->request->get('half-day-button');

            if (! empty($dateVisit)){
                $dateVisit = new \DateTime($dateVisit);
                if (!$checkDate->check($dateVisit, $dateTimeParis)){

                    return $this->redirectToRoute('home');
                }
                $ticket = new Ticket();
                // Add date visit to ticket
                $ticket->setDateVisit($dateVisit);
                // If visitor select half-day -> true
                if (isset($half_day)) {
                    $ticket->setHalfDay(true);
                }
                // Save ticket to session 
                $request->getSession()->set('ticket', $ticket);
                
                return $this->redirectToRoute('selectVisitor');
            }
            $this->addFlash('notice', "Please choose a date");
        }

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
        
        $repository = $this->getDoctrine()->getRepository(Calendar::class);
        $listDays = $repository->showDays();

        $easterDate = $checkDate->getEasterDateYearCurrent();

        return $this->render('ticket/index.html.twig', array(
            'local' => $locale,
            'timeZoneOffset' => $timeZoneOffsetParis,
            'dateVisit' => $dateVisit,
            'list_rate' => $listRate,
            'list_days' => $listDays,
            'easter_date'=> $easterDate
        ));
    }
    /**
     * @Route({"en" : "/visitors", "fr" : "/visiteurs"}, name="selectVisitor", requirements={"_locale": "en|fr"})
    */
    //Route("/visiteurs", name="selectVisitor")
    public function selectVisitor(Request $request, CalculVisitors $calculVisitors)
    {
        $locale = $request->getLocale();
        $ticket = $request->getSession()->get('ticket');

        if (! $ticket->getDateVisit()) {
            return $this->redirectToRoute('home');
        }
        // Create form for the visitors
        $form = $this->get('form.factory')->create(TicketType::class, $ticket);
        // When the visitor select pay
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Check if have 1 or more visitor(s)
            if ($ticket->getVisitors()->isEmpty()) {
                return $this->redirectToRoute('selectVisitor');
            }
            // Calcul price Ticket for each visitor
            $ticketVisitor = $calculVisitors->getCalculVisitors($ticket);   
            $request->getSession()->set('ticket', $ticketVisitor);

            return $this->redirectToRoute('payment');
        }

        return $this->render('ticket/selectVisitor.html.twig', array(
            'form' => $form->createView(),
            'ticket' => $ticket,
            'local' => $locale));
    }
    /**
     * @Route({"en" : "/payment", "fr" : "/paiement"}, name="payment", requirements={"_locale": "en|fr"})
    */
    public function payment(Request $request, PaymentStripe $payment)
    {
        $locale = $request->getLocale();
        $ticket = $request->getSession()->get('ticket');
        if (is_null($ticket->getNbVisitor())) {
            return $this->redirectToRoute('selectVisitor');
        }

        if ($request->isMethod('POST')) {
            $token = $request->request->get('stripeToken');
            $email = $request->request->get('email');

            $ticket->setEmail($email);
            $dateVisit = $ticket->getDateVisit();
            $nbVisitor = $ticket->getNbVisitor();
            // Service payment Stripe
            $response = $payment->charge($token, $ticket, array(
                "Email"             => $email,
                "Nombre de Ticket"  => $nbVisitor,
                "Date de visite"    => $dateVisit->format('d/m/Y')
            ));
            if (is_null($response)){

                return $this->redirectToRoute('payment');
            }
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
        return $this->render('ticket/payment.html.twig', array(
            'local'     => $locale,
            'ticket'    => $ticket));
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
            'local'     => $locale,
            'ticket'    => $ticket));
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
