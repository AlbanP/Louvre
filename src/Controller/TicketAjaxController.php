<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\DefineRate;

class TicketAjaxController extends Controller
{
    /**
     * @Route({"en" : "/calculRate", "fr" : "/calculRate"}, name="ajax_rate", requirements={"_locale": "en|fr"})
    */
    //Route("/calculRate", name="ajax_rate")
    public function calculRateAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $birthday = new \DateTime($request->get('birthday'));
            $reduction = $request->get('reduction');
            $dateVisit = new \DateTime($request->get('dateVisit'));
            $defineRate = new DefineRate;
            $price = $defineRate->calculRate($birthday, $reduction, $dateVisit);
            //$response = array($price, $birthday);

            return $this->json(array('price' => $price, 'birthday' => $birthday));
        }

        return new Response("Erreur : Is not Ajax request", 400);
    }
}
