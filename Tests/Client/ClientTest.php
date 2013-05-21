<?php

namespace  MikePearce\EasybacklogApiBundle\Tests\Controller;
use MikePearce\EasybacklogApiBundle\Client\Client;

class ClientClientTest extends \Guzzle\Tests\GuzzleTestCase
{
    public $ebclient;
    
    public function setup() {    

        // Guzzle
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new \Guzzle\Http\Message\Response(200));
        $this->mockedClient = new \Guzzle\Service\Client();
        $this->mockedClient->addSubscriber($plugin);
        $this->setMockBasePath(__DIR__ . DIRECTORY_SEPARATOR . '../TestData');
         
    }
    
    /**
     * Create the mock object
     * @param string $response_file
     */
    public function getMocks($response_file = 'json_response') {
        $this->setMockResponse($this->mockedClient, $response_file);
        
        // Mock memcache
        $memcache = $this->getMock('memcache', array('get', 'set'));
        $memcache->expects($this->any())
                 ->method('get')
                 ->will($this->returnValue($this->getMockResponse($response_file)->getBody()));
        $memcache->expects($this->any())
                 ->method('set')
                 ->will($this->returnValue(true));

        $this->ebclient = new Client(
            $memcache, 
            $this->mockedClient, 
            'xxxxxxxxxx', 
            '123'
        );                
    }

    public function testSetBacklogReturnsObject() {   
        $this->getMocks();
        $ebclient = $this->ebclient->setBacklog(array('0'))
                                   ->setBacklog('123');
        $this->assertInstanceOf('MikePearce\EasybacklogApiBundle\Client\Client', $ebclient);
    }

    public function testGetJsonFromApiReturnsJson() {
        $this->getMocks();
        $this->assertNotNull(
            json_decode($this->ebclient->getJsonFromApi('http://www.google.com'))
        );
    }

    public function testGetDataApiData() {
        $this->getMocks();
        $this->assertNotNull(
            json_decode($this->ebclient->getJsonFromApi('http://www.google.com'))
        );  
    }
    
    public function testGetThemes() {
        $this->getMocks('json_response_themes');
        $this->ebclient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebclient->getThemes())
        );
        $this->assertTrue(
            is_array($this->ebclient->getThemes(true))
        );
    }
    
    public function testGetSprints() {
        $this->getMocks('json_response_sprints');
        $this->ebclient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebclient->getSprints())
        );
        $this->assertTrue(
            is_array($this->ebclient->getSprints(true))
        );
    }    
    
    public function testGetVelocityStats() {
        $this->getMocks('json_response_stats');
        $this->ebclient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebclient->getVelocityStats())
        );
        $this->assertArrayHasKey(
            'velocity_stats', $this->ebclient->getVelocityStats()
        );
        $this->assertArrayHasKey(
            'velocity_complete', $this->ebclient->getVelocityStats()
        );
    }   
    
    public function testGetStoriesFromTheme() {
        $this->getMocks('json_response_themes');
        $this->ebclient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebclient->getStoriesFromTheme())
        );
        
    }
    
    public function testGetStory() {
        $this->getMocks('json_response_themes');
        $this->ebclient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebclient->getStory(rand(1, 202323)))
        );
        
    }    
}
