<?php

namespace  MikePearce\EasybacklogApiBundle\Tests\Controller;
use MikePearce\EasybacklogApiBundle\Client\Client;

class ClientClientTest extends \Guzzle\Tests\GuzzleTestCase
{
    protected $ebClient;
    
    protected $mockedGuzzle;
    
    public function setup() {
        // Guzzle
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new \Guzzle\Http\Message\Response(200));
        $this->mockedGuzzle = new \Guzzle\Service\Client();
        $this->mockedGuzzle->addSubscriber($plugin);
        $this->setMockBasePath(__DIR__ . DIRECTORY_SEPARATOR . '../TestData');
    }
    
    /**
     * Create the mock object
     * @param string $response_file
     */
    public function getMocks($response_file = 'json_response') {
        $this->setMockResponse($this->mockedGuzzle, $response_file);
        
        // Mock memcache
        $memcache = $this->getMock('memcache', array('get', 'set'));
        $memcache->expects($this->any())
                 ->method('get')
                 ->will($this->returnValue($this->getMockResponse($response_file)->getBody()));
        $memcache->expects($this->any())
                 ->method('set')
                 ->will($this->returnValue(true));

        $this->ebClient = new Client(
            $memcache, 
            $this->mockedGuzzle, 
            'xxxxxxxxxx', 
            '123'
        );                
    }

    public function testSetBacklogReturnsObject() {   
        $this->getMocks();
        $ebClient = $this->ebClient->setBacklog(array('0'))
                                   ->setBacklog('123');
        $this->assertInstanceOf('MikePearce\EasybacklogApiBundle\Client\Client', $ebClient);
    }

    public function testGetJsonFromApiReturnsJson() {
        $this->getMocks();
        $this->assertNotNull(
            json_decode($this->ebClient->getJsonFromApi('http://www.google.com'))
        );
    }

    public function testGetDataApiData() {
        $this->getMocks();
        $this->assertNotNull(
            json_decode($this->ebClient->getJsonFromApi('http://www.google.com'))
        );  
    }
    
    public function testGetThemes() {
        $this->getMocks('json_response_themes');
        $this->ebClient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebClient->getThemes())
        );
        $this->assertTrue(
            is_array($this->ebClient->getThemes(true))
        );
    }
    
    public function testGetSprints() {
        $this->getMocks('json_response_sprints');
        $this->ebClient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebClient->getSprints())
        );
        $this->assertTrue(
            is_array($this->ebClient->getSprints(true))
        );
    }    
    
    public function testGetVelocityStats() {
        $this->getMocks('json_response_stats');
        $this->ebClient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebClient->getVelocityStats())
        );
        $this->assertArrayHasKey(
            'velocity_stats', $this->ebClient->getVelocityStats()
        );
        $this->assertArrayHasKey(
            'velocity_complete', $this->ebClient->getVelocityStats()
        );
    }   
    
    public function testGetStoriesFromTheme() {
        $this->getMocks('json_response_themes');
        $this->ebClient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebClient->getStoriesFromTheme())
        );
        
    }
    
    public function testGetStory() {
        $this->getMocks('json_response_themes');
        $this->ebClient->setBacklog(rand(1, 202323));
        $this->assertTrue(
            is_array($this->ebClient->getStory(rand(1, 202323)))
        );
        
    }    
}
