<?php
namespace App\Service;

class DefineRate
{
    public function calculRate($birthday, $reduction, $dateVisit)
    {
        // Diff interval dateVisit-datebirthday
        $dateDiff = $dateVisit->diff($birthday);
        $yearDiff = $dateDiff->format('%y');
        $monthDiff = $dateDiff->format('%m');
        $dayDiff = $dateDiff->format('%d');
        $dateDiff = $yearDiff + ($monthDiff+$dayDiff)/100;
        // Calcul rate
        // Less and equal 4 years
        if ($dateDiff <= 4) {
            $price = 0;
        // Beetwen 4 and equal 12 years
        } elseif ($dateDiff <= 12) {
            $price = 800;
        // If reduction
        } elseif ($reduction == true) {
            $price = 1000;
        // More and equal 60 years
        } elseif ($dateDiff >= 60) {
            $price = 1200;
        // Standart price
        } else {
            $price = 1600;
        }

        return $price;
    }
}
