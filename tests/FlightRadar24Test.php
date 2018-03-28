<?php

use Jawish\FlightRadar24\FlightRadar24;

class FlightRadar24Test extends PHPUnit_Framework_TestCase
{

    public function testObjectCanBeConstructedForValidConstructorArguments()
    {
        $fr24 = new FlightRadar24();

        $this->assertInstanceOf('Jawish\\FlightRadar24\\FlightRadar24', $fr24);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     */
    public function testGetLoadBalancers()
    {
        $fr24 = new FlightRadar24();
        $loadBalancers = $fr24->getLoadBalancers();

        $this->assertGreaterThan(0, count($loadBalancers));
        $this->assertContains('data-live.flightradar24.com', $loadBalancers);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     * @depends testGetLoadBalancers
     */
    public function testSelectLoadBalancerByIndex()
    {
        $fr24 = new FlightRadar24();
        $selectedLoadBalancer = $fr24->selectLoadBalancer(0)->getSelectedLoadBalancer();

        $this->assertEquals(0, $selectedLoadBalancer['index']);
        $this->assertNotEmpty($selectedLoadBalancer['host']);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     * @depends testGetLoadBalancers
     */
    public function testSelectLoadBalancerByHostname()
    {
        $fr24 = new FlightRadar24();
        $selectedLoadBalancer = $fr24->selectLoadBalancer('data-live.flightradar24.com')->getSelectedLoadBalancer();

        $this->assertEquals('data-live.flightradar24.com', $selectedLoadBalancer['host']);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     * @depends testGetLoadBalancers
     */
    public function testSelectLoadBalancerByLatency()
    {
        $fr24 = new FlightRadar24();
        $selectedLoadBalancer = $fr24->selectLoadBalancer('latency')->getSelectedLoadBalancer();

        $this->assertNotEmpty($selectedLoadBalancer['host']);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     * @depends testGetLoadBalancers
     */
    public function testSelectLoadBalancerByRandom()
    {
        $fr24 = new FlightRadar24();
        $selectedLoadBalancer = $fr24->selectLoadBalancer('random')->getSelectedLoadBalancer();
        
        $this->assertNotEmpty($selectedLoadBalancer['host']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     */
    public function testSelectLoadBalancerException()
    {
        $fr24 = new FlightRadar24();
        $selectedLoadBalancer = $fr24->selectLoadBalancer(100)->getSelectedLoadBalancer();

        $this->assertNull($selectedLoadBalancer);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     */
    public function testGetAirports()
    {
        $fr24 = new FlightRadar24();
        $airports = $fr24->getAirports();

        $this->assertGreaterThan(0, count($airports));
        $this->assertContains(
            [ 
                'name' => 'London Heathrow Airport',
                'iata' => 'LHR',
                'icao' => 'EGLL',
                'lat' => '51.477501',
                'lon' => '-0.461380',
                'country' => 'United Kingdom',
                'alt' => '83'
            ], 
            $airports
        );
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     */
    public function testGetAirlines()
    {
        $fr24 = new FlightRadar24();
        $airlines = $fr24->getAirlines();

        $this->assertGreaterThan(0, count($airlines));
        $this->assertContains(
            [ 
                'Name' => 'British Airways',
                'Code' => 'BA',
                'ICAO' => 'BAW' 
            ], 
            $airlines
        );
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     */
    public function testGetZones()
    {
        $fr24 = new FlightRadar24();
        $zones = $fr24->getZones();

        $this->assertGreaterThan(0, count($zones));
        $this->assertArrayHasKey('europe', $zones);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     */
    public function testGetZoneNames()
    {
        $fr24 = new FlightRadar24();
        $zoneNames = $fr24->getZoneNames();

        $this->assertGreaterThan(0, count($zoneNames));
        $this->assertContains('europe', $zoneNames);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     * @depends testGetZones
     */
    public function testSelectZone()
    {
        $fr24 = new FlightRadar24();
        $selectedZoneName = $fr24->selectZone('europe')->getSelectedZone();

        $this->assertEquals('europe', $selectedZoneName);
    }

    /**
     * @depends testObjectCanBeConstructedForValidConstructorArguments
     * @depends testSelectLoadBalancer
     * @depends testSelectZone
     */
    public function testGetAircrafts()
    {
        $fr24 = new FlightRadar24();
        $aircrafts = $fr24->selectLoadBalancer(0)->selectZone('europe')->getAircrafts();
        list($sampleAircraftFlightId, $sampleAircraftData) = each($aircrafts);

        
        $this->assertNotEmpty($sampleAircraftFlightId);
        $this->assertGreaterThan(0, count($aircrafts));
        
        $expectedKeys = [ 
            'aircraft_id', 
            'latitude', 
            'longitude', 
            'track', 
            'altitude', 
            'speed', 
            'swquawk', 
            'radar_id', 
            'type', 
            'registration', 
            'last_update', 
            'origin', 
            'destination', 
            'flight', 
            'onground', 
            'vspeed', 
            'callsign', 
            'reserved',
            'airline',
        ];

        foreach($expectedKeys as $key) {
            $this->assertArrayHasKey('aircraft_id', $sampleAircraftData);
        }
    }
}