<?php

namespace Dwin\EasybacklogApi;

class Client {

    /**
     * @var \Guzzle\Service\Client
     */
    private $guzzle;

    /**
     * Your easybacklog.com API key
     *
     * @var string
     */
    private $apiKey;

    /**
     * Your user id
     *
     * @var int
     */
    private $userId;

    /**
     * Memcached client
     *
     * @var \Memcached
     */
    private $memcached;

    /**
     * @param \Guzzle\Service\Client $guzzle
     * @param $apiKey
     * @param $userId
     * @param \Memcached $memcached
     */
    public function __construct(\Guzzle\Service\Client $guzzle, $apiKey, $userId, \Memcached $memcached = null) {
        
        $this->guzzle = $guzzle;
        $this->apiKey = $apiKey;
        $this->userId = $userId;
        $this->memcached = $memcached;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getJsonFromApi($path) {
        $json = $this->guzzle->get($path)
                             ->setAuth($this->userId, $this->apiKey)
                             ->send()
                             ->getBody();

        if ($this->memcached) {
            $this->addDataToCache(md5($path), $json);
        }
        return $json;
    }

    /**
     * Whatever it is, construct the endpoint and return the json
     * @param $path string - The path of the call.
     * @return array - The Json as data
     **/
    private function getDataApiData($path = null) {
        if ($this->memcached && ($json = $this->memcached->get(md5($path)))) {
            $data = json_decode($json, true);

            if (isset($data['date']) && $data['date'] >= strtotime('-24 hours')) {
                return $data;
            }
        }

        $json = $this->getJsonFromApi($path);

        return json_decode($json, true);
    }

    /**
     * Add data to the cache (either a file, or maybe mongo)
     * @param $key string - This will be the path, used as the key
     * @param $json string -
     * @return void
     **/
    private function addDataToCache($key, $json) {
        // If we're not caching - should throw exception really
        if (! $this->memcached) return;

        // First, add a timestamp
        $data = json_decode($json, true);
        $data['date'] = time();
        $json = json_encode($data);

        // Then, add it to memcached
        $this->memcached->set($key, $json);
    }

    /**
     * Get the backlogs for the given account
     *
     * @param int $accountId
     * @param bool $includeArchived
     * @return array
     */
    public function getBacklogs($accountId, $includeArchived = false)
    {
        $path = 'api/accounts/' . $accountId . '/backlogs.json';

        if ($includeArchived) {
            $path .= '?include_archived=true';
        }

        return $this->getDataApiData($path);
    }

    /**
     * Fetch the backlog stats
     *
     * @param int $accountId
     * @param int $backlogId
     * @return array
     */
    public function getBacklogStats($accountId, $backlogId) {
        $path = 'api/accounts/' . $accountId . '/backlogs/' . $backlogId .'/stats.json';

        return $this->getDataApiData($path);
    }

    /**
     * Get the Themes for a given backlog
     *
     * @param int $backlogId
     * @param bool $includeStories
     * @return array
     */
    public function getThemes($backlogId, $includeStories = false) {
        $path = 'api/backlogs/' . $backlogId . '/themes.json';

        if ($includeStories) {
            $path .= '?include_associated_data=true';
        }

        return $this->getDataApiData($path);
    }

    /**
     * Fetch the sprints for a given backlog
     *
     * @param int $backlogId
     * @param bool $includeStories
     * @return array
     */
    public function getSprints($backlogId, $includeStories = false)
    {
        $path = 'api/backlogs/' . $backlogId . '/sprints.json';

        if ($includeStories) {
          $path .= '?include_associated_data=true';
        }

        return $this->getDataApiData($path);
    }

    /**
     * Fetch a single sprint
     *
     * @param int $backlogId
     * @param int $sprintId
     * @param bool $includeStories
     * @return array
     */
    public function getSprint($backlogId, $sprintId, $includeStories = false)
    {
        $path = 'api/backlogs/' . $backlogId . '/sprints/' . $sprintId . '.json';

        if ($includeStories) {
            $path .= '?include_associated_data=true';
        }

        return $this->getDataApiData($path);
    }

    /**
     * Fetch Sprint Stories (meta data about the story in a sprint) for a given sprint
     *
     * @param int $sprintId
     * @return array
     */
    public function getSprintStories($sprintId)
    {
        $path = 'api/sprints/' . $sprintId . '/sprint-stories.json';

        return $this->getDataApiData($path);
    }

    /**
     * Fetch Sprint Story (meta data about the story in a sprint) for a given sprint and story
     *
     * @param int $storyId
     * @param int $sprintId
     * @return array
     */
    public function getSprintStory($sprintId, $storyId)
    {
        $path = 'api/sprints/' . $sprintId . '/sprint-stories/' . $storyId .'.json';

        return $this->getDataApiData($path);
    }

    /**
     * Get a single story
     *
     * @param int $storyId
     * @param bool $includeAcceptanceCriteria
     * @return array
     */
    public function getStory($storyId, $includeAcceptanceCriteria = false) {
        $path = 'api/stories/'. $storyId .'.json';

        if ($includeAcceptanceCriteria) {
            $path .= '?include_associated_data=true';
        }

        return $this->getDataApiData($path);
    }

    /**
     * Extract just the velocity stuff from the backlog stats
     *
     * @param int $accountId
     * @param int $backlogId
     * @return array
     **/
    public function getVelocityStats($accountId, $backlogId) {
        $stats = $this->getBacklogStats($accountId, $backlogId);
        return array(
            'velocity_stats'    => $stats['velocity_stats'], 
            'velocity_complete' => $stats['velocity_completed']
        );
    }

    /**
     * Pull all the stories from a theme (or all themes)
     * @return array
     **/
    public function getStoriesFromTheme() {
        
        $stories = array();                          
        foreach ($this->getThemes(true) AS $theme) {
            if (is_array($theme['stories'])) {
                foreach ($theme['stories'] as &$story) {
                    $story['score_50'] = empty($story['score_50'])?null:(int)$story['score_50'];
                    $story['theme_unique_id'] = $theme['code'] . $story['unique_id'];
                }
                $stories = array_merge($stories, $theme['stories']);    
            }
        }

        return $stories;
    }
}