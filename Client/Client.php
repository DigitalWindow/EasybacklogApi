<?php

namespace MikePearce\EasybacklogApiBundle\Client;

class Client {

    // HTTP Service
    private $guzzle;

    /**
     * Your easybacklog.com API key
     **/
    private $api_key;

    /**
     * You user id
     **/
    private $userid;

    /**
     * Your easybacklog.com account Id
     **/
    public $accountid;

    /**
     * Your easybacklog.com backlogs
     **/
    public $backlogs;

    /**
     * Memcached client
     **/
    private $memcached;

    /**
     * Should we cache?
     **/
    private $cockblock;


    /**
     * @param $memcached memcache - DI injected
     * @param $guzzle guzzle - DI injected $guzzle
     * @param $api_key string - Your easybacklog.com API key
     * @param $userid int - your user id
     * @return void
     **/
    public function __construct($memcached, $guzzle, $api_key, $userid) {
        
        $this->guzzle       = $guzzle;
        $this->api_key      = $api_key;
        $this->userid       = $userid;
        $this->memcached    = $memcached;
        $this->cockblock    = false;
    }

    /**
     * @param $id int - Easy Backlog account ID
     * @return $this object - returns itslf.
     **/
    public function setAccountId($id) {
        $this->accountid = $id;
        return $this;
    }

    /**
     * @param $backlog int|array - Either a backlog ID, or an array of said.
     * @return $this object - Return itself.
     **/
    public function setBacklog($backlog)
    {
        if (!is_array($backlog)) $backlog = array($backlog);

        $this->backlogs = $backlog;

        return $this;
    }

    /**
     * @param $path string - the URL endpoint
     * @return string - json
     **/
    public function getJsonFromApi($path) {
        
        $json =  $this->guzzle->get($path)
                              ->setAuth($this->userid, $this->api_key)
                              ->send()
                              ->getBody();  

        $this->addDataToCache(md5($path), $json);
        return $json;
    }

    /**
     * Whatever it is, construct the endpoint and return the json
     * @param $path string - The path of the call.
     * @return array - The Json as data
     **/
    private function getDataApiData($path = null) {

        // No json, get some
        if ($this->cockblock) {
            $json = false;
        }
        else {
            $json = $this->memcached->get(md5($path));    
        }
        
        if ($json == false) {
            $json =  $this->getJsonFromApi($path);
        }
        else {

            $data = json_decode($json, true);

            if (isset($data['date']) AND $data['date'] <= strtotime('-24 hours')) {
                $json =  $this->getJsonFromApi($path);
            }
    
        }

        return json_decode($json, true);
        
    }

    /**
     * Add data to the cache (either a file, or maybe mongo)
     * @param $key string - This will be the path, used as the key
     * @param $json string -
     * @return void
     **/
    private function addDataToCache($key, $json) {

        // If we're blocking
        if ($this->cockblock) return;

        // First, add a timestamp
        $data = json_decode($json, true);
        $data['date'] = time();
        $json = json_encode($data);

        // Then, add it to memcache
        
        $this->memcached->set($key, $json);
    }

    /**
     * @param $include_associated_data boo - 
     * @return array();
     */
    public function getThemes($include_associated_data = false) {
        $path = 'api/backlogs/{backlogid}/themes.json';
        if ($include_associated_data) $path .= '?include_associated_data=true';
        return $this->loopBacklogs($path);
        
    }

    /**
     * @param $include_associated_data boolean - whether we include the stories
     * @return array()
     **/
    public function getSprints($include_associated_data = false) {
        $path = 'api/backlogs/{backlogid}/sprints.json';

        if ($include_associated_data) $path .= '?include_associated_data=true';
        return $this->loopBacklogs($path);
    }

    /**
     * Get the backlog stats
     * @return array
     **/
    public function getBacklogStats() {
        return $this->loopBacklogs('api/accounts/{accountid}/backlogs/{backlogid}/stats.json');
    }

    /**
     * Extract just the velocity stuff from the backlog stats
     * @return array
     **/
    public function getVelocityStats() {
        $stats = $this->getBacklogStats();
        return array(
            'velocity_stats'    => $stats['velocity_stats'], 
            'velocity_complete' => $stats['velocity_completed']
        );
    }    

    /**
     * Loop through the array of backlogs
     * @param $path string - The path to loop on
     * @return array
     **/
    public function loopBacklogs($path) {

        $data = array();
        foreach($this->backlogs AS $backlog_id) {
            $data = array_merge($data, $this->getDataApiData(
                    str_replace(
                        array('{backlogid}', '{accountid}'), 
                        array($backlog_id, $this->accountid), 
                        $path
                   )
                )
               );
        }
        return $data;
    }

    /**
     * Pull all the stories from a theme (or all themes)
     * @return array
     **/
    public function getStoriesFromTheme() {
        
        $stories = array();                          
        foreach ($this->getThemes(true) AS $theme) {
            if (is_array($theme['stories'])) {
                $stories = array_merge($stories, $theme['stories']);    
            }
        }

        return $stories;
    }

    /**
     * Get a single story
     * @param $story_id int
     * @return array
     **/
    public function getStory($story_id) {
        $path = 'api/stories/'. $story_id .'.json?include_associated_data=true';
        return $this->loopBacklogs($path);
    }
    
}