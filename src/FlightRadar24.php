<?php

/**
 * This file is part of jawish/flightradar24.
 *
 * (c) Jawish Hameed <jawish@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jawish\FlightRadar24;

/**
 * FlightRadar24 API library
 *
 * @package   org.jawish.flightradar24
 * @author    Jawish Hameed <jawish@gmail.com>
 * @copyright 2014 Jawish Hameed
 * @license   http://www.opensource.org/licenses/MIT The MIT License
 */
class FlightRadar24
{
    public static $apiBaseUrl = 'http://www.flightradar24.com';

    const PATH_LOAD_BALANCER = '/balance.json';
    const PATH_AIRPORTS = '/_json/airports.php';
    const PATH_AIRLINES = '/_json/airlines.php';
    const PATH_ZONES = '/js/zones.js.php';
    const PATH_ZONE_AIRCRAFTS = '/zones/%s_all.json';
    const PATH_ALL_AIRCRAFTS = '/zones/full_all.json';
    const PATH_AIRCRAFT_DETAILS = '/_external/planedata_json.1.3.php?f=%s';

    protected $loadBalancers = [];
    protected $selectedLoadBalancer = null;
    protected $airports = [];
    protected $airlines = [];
    protected $zones = [];
    protected $selectedZone = null;
    protected $aircrafts = [];

    /**
     * Fetches and returns the load balancers for aircraft API calls
     *
     * @param   $refresh      Boolean       Whether to refetch data from API or not
     * @return  array                       Load balancers
     */
    public function getLoadBalancers($refresh = false)
    {
        if (empty($this->loadBalancers) || TRUE == $refresh) {

            try {
                $this->loadBalancers = array_keys(
                    $this->api(self::$apiBaseUrl . self::PATH_LOAD_BALANCER)
                );
            }
            catch (\Exception $e) {
                throw new \Exception('An error occurred while fetching and parsing load balancers.');
            }
        }

        return $this->loadBalancers;
    }

    /**
     * Make the load balancer at the given index as the default.
     *
     * @param   $index          Load balancer index to use
     *
     * @return  $this
     */
    public function selectLoadBalancer($index = 0)
    {
        if (isset($this->getLoadBalancers()[$index])) {
            $this->selectedLoadBalancer = $index;
        }
        else {
            $this->selectedLoadBalancer = null;

            throw new \InvalidArgumentException(sprintf('Load balancer %d is undefined.', $index));
        }

        return $this;
    }

    /**
     * Get the selected load balancer index number.
     *
     * @return array            An array with keys index and host
     */
    public function getSelectedLoadBalancer()
    {
        return array(
            'index' => $this->selectedLoadBalancer, 
            'host' => $this->loadBalancers[$this->selectedLoadBalancer]
        );
    }

    public function getAirports($refresh = false)
    {
        if (empty($this->airports) || true == $refresh) {

            try {
                $this->airports = $this->api(self::$apiBaseUrl . self::PATH_AIRPORTS)['rows'];
            }
            catch (\Exception $e) {
                throw new \Exception('An error occurred while fetching and parsing airports.');
            }
        }

        return $this->airports;
    }

    public function getAirlines($refresh = false)
    {
        if (empty($this->airlines) || true == $refresh) {

            try {
                $this->airlines = $this->api(self::$apiBaseUrl . self::PATH_AIRLINES)['rows'];
            }
            catch (\Exception $e) {
                throw new \Exception('An error occurred while fetching and parsing airlines.');
            }
        }

        return $this->airlines;
    }

    public function getZones($refresh = false)
    {
        if (empty($this->zones) || true == $refresh) {

            try {
                $this->zones = $this->api(self::$apiBaseUrl . self::PATH_ZONES);
                unset($this->zones['version']);

                $this->zoneNames = [];
            }
            catch (\Exception $e) {
                throw new \Exception('An error occurred while fetching and parsing zones.');
            }
        }

        return $this->zones;
    }

    public function getZoneNames($refresh = false)
    {
        if (empty($this->zoneNames) || true == $refresh) {
            $zoneNames = $this->getZones($refresh);
            $this->zoneNames = $this->buildZoneNames($zoneNames);
        }

        return $this->zoneNames;
    }

    private function buildZoneNames(array &$zones = array())
    {
        $zoneNames = [];

        foreach ($zones as $key => $value) {
            if (!in_array($key, [ 'tl_x', 'tl_y', 'br_x', 'br_y', 'subzones' ])) {
                $zoneNames[] = $key;

                if (isset($value['subzones']) && !empty($value['subzones'])) {
                    array_merge($zoneNames, $this->buildZoneNames($value['subzones']));
                }
            }
        };

        return $zoneNames;
    }

    public function selectZone($zoneName)
    {
        $zoneName = strtolower($zoneName);

        $this->selectedZone = in_array($zoneName, $this->getZoneNames()) ? $zoneName : null;

        return $this;
    }

    public function getSelectedZone()
    {
        return $this->selectedZone;
    }

    public function getAircrafts($refresh = false)
    {
        $loadBalancer = $this->getSelectedLoadBalancer();
        if (is_null($loadBalancer)) {
            throw new \Exception('Load balancer not selected.');
        }

        $zoneName = $this->getSelectedZone();
        if (is_null($zoneName)) {
            throw new \Exception('Zone not selected.');
        }
        
        if (empty($this->aircrafts) || true == $refresh) {
            try {
                $apiPath = ('all' == $zoneName) ? self::PATH_ALL_AIRCRAFTS : self::PATH_ZONE_AIRCRAFTS;
                
                $this->aircrafts = $this->api(
                    sprintf('http://' . $loadBalancer['host'] . $apiPath, $zoneName)
                );

                foreach ($this->aircrafts as $id => $data) {
                    if ($id != 'version' && $id != 'full_count') {

                        $this->aircrafts[$id] = array_combine(
                            [ 'aircraft_id', 'latitude', 'longitude', 'track', 'altitude', 'speed', 'swquawk', 'radar_id', 'type', 'registration', 'last_update', 'origin', 'destination', 'flight', 'onground', 'vspeed', 'callsign', 'reserved' ],
                            $data
                        );

                    }
                }
            }
            catch (\Exception $e) {
                throw new \Exception(sprintf('An error occurred while fetching and parsing aircrafts for zone %s.', $zoneName));
            }
        }

        return $this->aircrafts;
    }

    public function getAircraftDetailsByFlightId($flightId, $refresh = false)
    {
        $this->getAircrafts($refresh);

        $loadBalancer = $this->getSelectedLoadBalancer();
        if (is_null($loadBalancer)) {
            throw new \Exception('Load balancer not selected.');
        }

        $zoneName = $this->getSelectedZone();
        if (is_null($zoneName)) {
            throw new \Exception('Zone not selected.');
        }

        if (empty($this->aircrafts[$flightId]['details']) || true == $refresh) {
            $url = 'http://' . $loadBalancer['host'] . sprintf(self::PATH_AIRCRAFT_DETAILS, $flightId);

            try {
                $json = file_get_contents($url);
                if ($json) {
                    $this->aircrafts[$zoneName][$flightId]['details'] = json_decode($json, true);
                }
            }
            catch (\Exception $e) {
                throw new \Exception(sprintf('An error occurred while fetching and parsing aircrafts for zone %s.', $zoneName));
            }
        }

        return $this->aircrafts[$zoneName][$flightId];
    }

    public function findAircrafts($attribute, $regexp, $refresh = false)
    {
        $this->getAircrafts($refresh);

        $flightIds = [];

        foreach ($this->aircrafts as $id => $data) {
            if (preg_match($regexp, $data[$attribute])) {
                $flightIds[] = $id;
            }
        }

        return $flightIds;
    }

    public function getAircraftsByAttribute($attribute, $regexp, $refresh = false)
    {
        $flightIds = $this->findAircrafts($attribute, $regexp, $refresh);

        $aircrafts = [];

        foreach ($flightIds as $flightId) {
            $aircrafts[] = $this->aircrafts[$flightId];
        }

        return $aircrafts;
    }

    public function getAircraftDetailsByAttribute($attribute, $regexp, $refresh = false)
    {
        $flightIds = $this->findAircrafts($attribute, $regexp, $refresh);

        $aircraftDetails = [];

        foreach ($flightIds as $flightId) {
            $aircraftDetails[] = $this->getAircraftDetailsByFlightId($flightId, $refresh);
        }

        return $aircraftDetails;
    }

    protected function api($url)
    {
        try {
            $json = json_decode(file_get_contents($url), true);
        }
        catch (\Exception $e) {
            throw new \Exception(sprintf('An error occurred accessing API at %s.', $url));
        }

        return $json;
    }
}