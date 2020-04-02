<?php
namespace core\utils {

    class StatsUtils
    {
        const Z80 = 1.28;
        const Z90 = 1.645;
        const Z95 = 1.96;

        static public function getStandardDeviation ($pWinrate, $pCount) {
            return round(self::Z90*sqrt($pWinrate*(100-$pWinrate)/$pCount), 1);
        }
    }
}