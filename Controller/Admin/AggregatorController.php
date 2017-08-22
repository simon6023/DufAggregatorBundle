<?php

namespace Duf\AggregatorBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class AggregatorController extends Controller
{
    public function viewPostsAction(Request $request, $account_id, $page = 1)
    {
    	$account = $this->getDoctrine()->getRepository('DufAggregatorBundle:AggregatorAccount')->findOneById($account_id);

    	if (empty($account))
    		exit('account not found');

    	$limit 		= 10;
    	$start 		= ($page === 1) ? 0: ($page * $limit) - $limit;

    	$_posts 	= $this->getDoctrine()->getRepository('DufAggregatorBundle:AggregatorPost')->findByPagination($account, $start, $limit);
    	$posts  	= $this->formatPostsForView($_posts);

        return $this->render('DufAggregatorBundle:Admin\Aggregator:view-posts.html.twig', array(
        		'account' 	=> $account,
        		'posts' 	=> $posts,
        		'ajax' 		=> ($request->isXmlHttpRequest()) ? true: false,
        		'limit' 	=> $limit,
        	)
        );
    }

    public function refreshAction($account_id)
    {
    	if ($account_id === 'null' && isset($_GET['account_id']))
    		$account_id = $_GET['account_id'];

    	$account = $this->getDoctrine()->getRepository('DufAggregatorBundle:AggregatorAccount')->findOneById($account_id);

    	if (empty($account))
    		exit('account not found');

        // check if service exists
    	$service_service_name = 'duf_aggregator.dufaggregator' . $account->getService();

        if (!$this->container->has($service_service_name))
        	exit('service not found');

        if (true !== ($refresh = $this->container->get($service_service_name)->refresh($account, $_GET)))
        	return $this->redirect($refresh);

    	// redirect to account posts view
    	return $this->redirect($this->generateUrl('duf_admin_aggregator_view_posts', array('account_id' => $account_id)));
    }

    public function updatePostStateAction($post_id, $state)
    {
    	$post = $this->getDoctrine()->getRepository('DufAggregatorBundle:AggregatorPost')->findOneById($post_id);

    	if (empty($post))
    		return new Response('post not found', 500);

    	$post->setEnabled($state);

    	$em = $this->getDoctrine()->getManager();
    	$em->persist($post);
    	$em->flush();

    	return new Response('done', 200);
    }

    private function formatPostsForView($_posts)
    {
    	$posts = array();

    	foreach ($_posts as $post) {
    		$post_array = array(
    			'id' 			=> $post->getId(),
    			'post_id' 		=> $post->getPostId(),
    			'account' 		=> $post->getAccount(),
    			'user' 			=> json_decode($post->getPostUser()),
    			'image' 		=> $post->getImage(),
    			'date' 			=> $post->getPostDate(),
    			'caption' 		=> $post->getCaption(),
    			'enabled' 		=> $post->getEnabled(),
    			'link' 			=> $post->getLink(),
    		);

    		$posts[] = $post_array;
    	}

    	return $posts;
    }
}