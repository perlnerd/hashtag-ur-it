<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class HashtagController extends Controller
{
    private $twitter;
    private $hashtag;
    private $dateRange;

    /**
     * Sets the value of dateRange.
     *
     * @param mixed $dateRange the date range
     *
     * @return self
     */
    private function setDateRange($dateRange)
    {
        $this->dateRange = $dateRange;

        return $this;
    }

    /**
     * Sets the value of hashtag.
     *
     * @param mixed $hashtag the hashtag
     *
     * @return self
     */
    public function setHashtag($hashtag)
    {
        $this->hashtag = $hashtag;

        return $this;
    }

    /**
     * Display most recent tweets containing the specified hashtag
     * @param  string   $hashtag hashtag to search for
     * @return Resource Symfony\Component\HttpFoundation\Response
     * @Route("/hashtag/{hashtag}")
     */
    public function hashtagAction(Request $request, $hashtag = 'Toronto')
    {
        $searchResponseParsed = $this->searchCommon($request, $hashtag);

        return $this->render('default/hashtag.html.twig', $this->fetchTwigParamList($searchResponseParsed));
    }

    /**
     * Display most recent tweets containing the specified hashtag,  Only render the tweets.
     * @param  string   $hashtag hashtag to search for
     * @return Resource Symfony\Component\HttpFoundation\Response
     * @Route("/mini/hashtag/{hashtag}")
     */
    public function minimalHashtagAction(Request $request, $hashtag = 'Toronto')
    {
        $searchResponseParsed = $this->searchCommon($request, $hashtag);

        return $this->render('default/minimal.hashtag.html.twig', $this->fetchTwigParamList($searchResponseParsed));
    }

    /**
     * process the 'n' get variable and the hashtag
     *
     * @param  string  $hashtag twitter hashtag
     * @return self
     */
    private function processInput($request, $hashtag)
    {
        $num   = $request->query->get('n');
        $range = $request->query->get('r');

        if (is_string($range)) {
            $this->setDateRange(urlencode($range));
            $this->twitter->setDateRange($this->dateRange);
        }

        if (ctype_digit($num)) {
            $this->twitter->setMaxRecords($num);
        }

        $this->numRecords = $this->twitter->getMaxRecords();

        $this->setHashtag(trim($hashtag, '#'));

        return $this;
    }

    /**
     * Calls utils\Twitter::twitterHashtagSearch()
     *
     * @return mixed An array of statuses on success false on failure.
     */
    private function doSearch()
    {
        $searchResponse = $this->twitter->twitterHashtagSearch($this->hashtag);
        
        return $searchResponse;
    }

    private function fetchTwigParamList($statuses)
    {
        return array(
            'base_dir'     => realpath($this->container->getParameter('kernel.root_dir').'/..'),
            'statuses'     => $statuses,
            'numRecords'   => $this->numRecords,
            'hashtag'      => $this->hashtag,
            'refreshUrl'   => '/mini/hashtag/' . $this->hashtag . '?n=' . $this->numRecords,
        );
    }

    /**
     * Steps shared by the minimal and standard search actions.
     * @param  Object $request Symfony\Component\HttpFoundation\Request
     * @param  string $hashtag hashtag to search for
     * @return mixed           search response tidied for display or false if no results.
     */
    private function searchCommon($request, $hashtag)
    {
        $searchResponseParsed = false;
        $this->twitter        = $this->get('util.twitter');
        
        $this->processInput($request, $hashtag);

        $result               = $this->doSearch();

        if (false !== $result) {
            $searchResponseParsed = $this->twitter->tidyResponse($result['statuses']);
        }

        return $searchResponseParsed;
    }
}
