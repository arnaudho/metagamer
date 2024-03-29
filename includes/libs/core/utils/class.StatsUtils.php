<?php
namespace core\utils {

    class StatsUtils
    {
        const Z80 = 1.28;
        const Z90 = 1.645;
        const Z95 = 1.96;

        static public function getStandardDeviation ($pWinrate, $pCount, $pZ = self::Z90) {
            // use 0.5 instead of winrate to get the same deviation on extreme winrates
            return round($pZ*sqrt(50*(100-50)/$pCount), 1);
        }
    }
}