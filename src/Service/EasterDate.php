<?php
namespace App\Service;

class EasterDate
{
    public function getEasterDateYearCurrent()
    {
        $today = new \datetime();
        if ($today->format('n') > 7) {
            $easterDate = easter_date($today->format('Y') + 1) + 43200;
        } else {
            $easterDate = easter_date($today->format('Y')) + 43200;
        }

        return $easterDate;
    }
}