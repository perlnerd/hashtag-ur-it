<?php
namespace AppBundle\Utils;

use \DateTime;
use \DateInterval;

class Twitter
{
    private $authToken;
    private $hashtag;
    private $maxRecords;
    private $twitterAuthUrl;
    private $twitterSearchUrlFormat;
    private $postAuthVar;
    private $ch;
    private $dateRange;

    /**
     * Sets the value of dateRange.
     *
     * @param mixed $dateRange the date range
     *
     * @return self
     */
    public function setDateRange($dateRange)
    {
        $this->dateRange = $dateRange;

        return $this;
    }
    
    /**
     * Sets the value of twitterAuthUrl.
     *
     * @param string $twitterAuthUrl the twitter auth url
     *
     * @return self
     */
    public function setTwitterAuthUrl($twitterAuthUrl)
    {
        $this->twitterAuthUrl = $twitterAuthUrl;

        return $this;
    }

    /**
     * Sets the value of twitterSearchUrlFormat.
     *
     * @param string $twitterSearchUrlFormat the twitter auth url
     *
     * @return self
     */
    public function setTwitterSearchUrlFormat($twitterSearchUrlFormat)
    {
        $this->twitterSearchUrlFormat = $twitterSearchUrlFormat;

        return $this;
    }


    /**
     * Sets the value of postAuthVar.
     *
     * @param string $postAuthVar the post auth var
     *
     * @return self
     */
    public function setPostAuthVar($postAuthVar)
    {
        $this->postAuthVar = $postAuthVar;

        return $this;
    }

     /**
     * Sets the value of authToken.
     *
     * @param string $authToken the auth token
     *
     * @return self
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;

        return $this;
    }

    /**
     * Sets the value of hashtag.
     *
     * @param string $hashtag the hashtag
     *
     * @return self
     */
    public function setHashtag($hashtag)
    {
        $this->hashtag = $hashtag;

        return $this;
    }

    /**
     * Sets the value of maxRecords.
     *
     * @param int $numRecords the num records
     *
     * @return self
     */
    public function setMaxRecords($num)
    {
        $this->maxRecords = $num;

        return $this;
    }

    /**
     * Gets the value of maxRecords.
     *
     * @return mixed
     */
    public function getMaxRecords()
    {
        return $this->maxRecords;
    }

    /**
     * calls self::fetchBearerToken() and self::fetchSearchResults()
     * @param  string $hashtag hashtag to search for
     * @return mixed           array of results on success or false.
     */
    public function twitterHashtagSearch($hashtag)
    {
        $this->setHashtag($hashtag);

        $bearerToken = $this->fetchBearerToken();
        if (false === $bearerToken) {
            return false;
            error_log('App Authentication Failed');
        }

        $results     = $this->fetchSearchResults($bearerToken);

        if (count($results['statuses']) == 0) {
            return false;
        }

        return $results;
    }

    /**
     * Performs app only authentication to the twoitter API.  https://dev.twitter.com/oauth/application-only
     * @return mixed bearer token or false on failure
     */
    private function fetchBearerToken()
    {
        $this->ch = $this->fetchCurlHandle();
        $headers  = $this->fetchCurlHeaders();

        $this->defineDefaultCurlOpts($headers)
            ->defineBearerTokenCurlOpts($headers);

        $authOutputJSON = curl_exec($this->ch);

        curl_close($this->ch);

        if (false === $authOutputJSON) {
            return false;
        }

        $bearerTokenList = json_decode($authOutputJSON, true);

        if (true === array_key_exists('access_token', $bearerTokenList)) {
            return $bearerTokenList['access_token'];
        }

        return false;
    }

    /**
     * Performs the actual search against the twitter API.
     * @param  string $bearerToken auth token to be passed with search request
     * @return mixed               search results array on success, false on failure.
     */
    private function fetchSearchResults($bearerToken)
    {
        $this->setAuthToken($bearerToken);

        $this->ch = $this->fetchCurlHandle();
        $headers  = $this->fetchCurlHeaders('Bearer');
        $hashtag  = isset($this->dateRange) ? $this->hashtag . $this->dateRange : $this->hashtag;
        $url      = sprintf($this->twitterSearchUrlFormat, $this->maxRecords, '%23', $hashtag);

        $this->defineDefaultCurlOpts($headers);
        $this->defineSearchCurlOpts($url);

        $searchOutputJSON = curl_exec($this->ch);

        //$pre = '<pre>' . var_export(json_decode($searchOutputJSON,true),true) . '</pre>';
        //var_export($pre);

        curl_close($this->ch);

        if (false === $searchOutputJSON) {
            return false;
        }

        $resultList = json_decode($searchOutputJSON, true);

        return $resultList;
    }

    /**
     * return a curl resource
     * @return resource handle curl
     */
    private function fetchCurlHandle()
    {
        return curl_init();
    }

    /**
     * create an array of http headers to be passed with the request
     * @return array http headers
     */
    private function fetchCurlHeaders($authType = 'Basic')
    {
        $headers   = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
        $headers[] = 'Authorization: ' . $authType . ' ' . $this->authToken;
        $headers[] = 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0';

        return $headers;
    }

    /**
     * configure curl options
     * @return self;
     */
    private function defineBearerTokenCurlOpts()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->twitterAuthUrl);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->postAuthVar);

        return $this;
    }

    /**
     * configure curl options
     * @return self;
     */
    private function defineSearchCurlOpts($url)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        
        return $this;
    }

    /**
     * configure default curl options
     * @return self;
     */
    private function defineDefaultCurlOpts($headers)
    {
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);

        return $this;
    }

    /**
     * Gather only the data we need from the API response.  Turn @ mentions, hastags, and URLs into links.
     * @param  array $statuses The statuses return by the API call.
     * @return array           array with trimmed down and beautified results.
     */
    public function tidyResponse($statuses)
    {
        foreach ($statuses as $key => $value) {
            $value['text'] = preg_replace("/#([a-z_0-9]+)/i", "<a href=\"http://twitter.com/search/$1\">$0</a>", $value['text']);
            $value['text'] = preg_replace("/@(\w+)/", "<a href=http://twitter.com/$1>@$1</a>", $value['text']);
            $linkified     = array();

            foreach ($value['entities']['urls'] as $url) {
                $linkUrl    = $url['url'];
                $displayUrl = $url['display_url'];

                if (in_array($url, $linkified)) {
                    continue;
                }

                $linkified[]   = $linkUrl;
                $value['text'] = str_replace($linkUrl, sprintf('<a href="%1$s">%1$s</a>', $linkUrl, $displayUrl), $value['text']);
            }

            $searchResponseParsed[$key] = array
                (
                    'name'        => $value['user']['name'],
                    'screen_name' => $value['user']['screen_name'],
                    'date'        => date('l jS \of F Y \a\t h:i:s', strtotime($value['created_at'])),
                    'tweet'       => $value['text'],
                    'avatar'      => $value['user']['profile_image_url'],
                );
        }

        return $searchResponseParsed;
    }
}
