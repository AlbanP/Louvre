<?php
namespace App\Service;

class CheckDate
{
    public function check($date, $today)
    {
        $start = $today;
        $start->add(new \DateInterval('PT18H'));
        // Check Before today at 18h
        if ($date < $start) {
            return false;
        }
        // Check After 6 month at midnight
        $end = $today;
        $end->add(new \DateInterval('P6M'));
        $end->setTime(23, 59, 59); 
        if ($date > $end) {
            return false;
        }
        // Check day is Sunday or Thesday
        $day = $date->format('l');
        if ($day == "Sunday" || $day == "Tuesday") {
            return false;
        }

        $this->getHolidays($date, $today);
        

        return true;
    }

    function getHolidays($date, $today)
    {
        if ($today->format('m') > 7) {
            $year = $today->format('Y') + 1;
        } else {
            $year = $today->format('Y');
        }
 
        $easterDate  = easter_date($year);
        $easterDay   = date('j', $easterDate);
        $easterMonth = date('n', $easterDate);
        $easterYear   = date('Y', $easterDate);
 
        $holidays = array(
            // Dates fixes
            mktime(0, 0, 0, 1,  1,  $year),  // 1er janvier
            mktime(0, 0, 0, 5,  1,  $year),  // Fête du travail
            mktime(0, 0, 0, 5,  8,  $year),  // Victoire des alliés
            mktime(0, 0, 0, 7,  14, $year),  // Fête nationale
            mktime(0, 0, 0, 8,  15, $year),  // Assomption
            mktime(0, 0, 0, 11, 1,  $year),  // Toussaint
            mktime(0, 0, 0, 11, 11, $year),  // Armistice
            mktime(0, 0, 0, 12, 5,  $year),  // 5 dec (close)
            mktime(0, 0, 0, 12, 25, $year),  // Noel
 
            // Dates variables
            mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear), //Lundi de Pâques
            mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear), //Jeudi de l'Ascension
            mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear), //Lundi de Pentecôte
        );

        foreach($holidays as $holiday) {
            $day = new \DateTime();
            $day->setTimestamp($holiday);
            if ($date == $day) {
                return false;
            }
        } 
    }
}