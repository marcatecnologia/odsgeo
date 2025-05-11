<?php

namespace App\Services;

use proj4php\Proj4php;
use proj4php\Point;

class CoordinateConversionService
{
    protected $proj4;

    public function __construct()
    {
        $this->proj4 = new Proj4php();
    }

    public function utmToDecimal($north, $east, $zone, $datum)
    {
        $utmProj = $this->getUTMProjection($zone, $datum);
        $wgs84Proj = $this->getWGS84Projection();

        $point = new Point($east, $north);
        $point = $this->proj4->transform($utmProj, $wgs84Proj, $point);

        return [
            'latitude_decimal' => $point->y,
            'longitude_decimal' => $point->x,
        ];
    }

    public function decimalToUTM($latitude, $longitude, $zone, $datum)
    {
        $wgs84Proj = $this->getWGS84Projection();
        $utmProj = $this->getUTMProjection($zone, $datum);

        $point = new Point($longitude, $latitude);
        $point = $this->proj4->transform($wgs84Proj, $utmProj, $point);

        return [
            'utm_north' => $point->y,
            'utm_east' => $point->x,
        ];
    }

    public function decimalToGMS($latitude, $longitude)
    {
        return [
            'latitude_gms' => $this->decimalToGMSString($latitude),
            'longitude_gms' => $this->decimalToGMSString($longitude),
        ];
    }

    public function gmsToDecimal($latitudeGMS, $longitudeGMS)
    {
        return [
            'latitude_decimal' => $this->gmsToDecimalValue($latitudeGMS),
            'longitude_decimal' => $this->gmsToDecimalValue($longitudeGMS),
        ];
    }

    protected function getUTMProjection($zone, $datum)
    {
        $datumDef = $this->getDatumDefinition($datum);
        return "+proj=utm +zone={$zone} +{$datumDef} +units=m +no_defs";
    }

    protected function getWGS84Projection()
    {
        return "+proj=longlat +datum=WGS84 +no_defs";
    }

    protected function getDatumDefinition($datum)
    {
        return match ($datum) {
            'SIRGAS2000' => 'datum=WGS84',
            'SAD69' => 'datum=GRS67',
            default => 'datum=WGS84',
        };
    }

    protected function decimalToGMSString($decimal)
    {
        $degrees = floor(abs($decimal));
        $minutes = floor((abs($decimal) - $degrees) * 60);
        $seconds = round(((abs($decimal) - $degrees) * 60 - $minutes) * 60, 2);

        $direction = $decimal >= 0 ? 'N' : 'S';
        if (abs($decimal) > 90) {
            $direction = $decimal >= 0 ? 'E' : 'W';
        }

        return sprintf("%d°%d'%.2f\"%s", $degrees, $minutes, $seconds, $direction);
    }

    protected function gmsToDecimalValue($gms)
    {
        preg_match('/(\d+)°(\d+)\'([\d.]+)"([NSEW])/', $gms, $matches);
        
        if (count($matches) !== 5) {
            throw new \InvalidArgumentException('Formato GMS inválido');
        }

        $degrees = (int) $matches[1];
        $minutes = (int) $matches[2];
        $seconds = (float) $matches[3];
        $direction = $matches[4];

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if (in_array($direction, ['S', 'W'])) {
            $decimal = -$decimal;
        }

        return $decimal;
    }
} 