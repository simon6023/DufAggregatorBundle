<?php
namespace Duf\AggregatorBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\ORM\EntityManager as EntityManager;

use GuzzleHttp\Client as GuzzleClient;

use Duf\AggregatorBundle\Entity\AggregatorOauth;
use Duf\AggregatorBundle\Entity\AggregatorPost;

class DufAggregatorInstagram
{
    private $container;
    private $router;
    private $em;

    protected $api_baseurl;
    protected $account;

    public function __construct(Container $container, Router $router, EntityManager $em)
    {
        $this->container        = $container;
        $this->router           = $router;
        $this->em               = $em;

        $this->api_baseurl      = 'https://api.instagram.com/v1/';
    }

    public function refresh($account, $parameters)
    {
        $this->account  = $account;

        $client_id      = $this->getInstagramConfig('client_id');
        $client_secret  = $this->getInstagramConfig('client_secret');

        if (null === ($previous_oauth = $this->getPreviousOauth('access_token'))) {
            // remove previous oauth
            $this->removePreviousOauth();

            // return login url
            if (!isset($parameters['code'])) {
                $query_parameters = array(
                    'client_id'     => $client_id,
                    'redirect_uri'  => $this->getRedirectUri(),
                    'response_type' => 'code',
                    'scope'         => 'public_content',
                );

                return 'https://api.instagram.com/oauth/authorize/?' . http_build_query($query_parameters);
            }
            // get access token
            else {
                $query_parameters = array(
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => $this->getRedirectUri(),
                    'code'          => $parameters['code'],
                );

                $client     = new GuzzleClient();
                $request    = $client->request('POST', 'https://api.instagram.com/oauth/access_token', array(
                        'form_params' => $query_parameters,
                    )
                );

                if (200 === $request->getStatusCode()) {
                    $data           = (string)$request->getBody();
                    $access_token   = json_decode($data, true);

                    if (isset($access_token['access_token'])) {
                        $access_token = $access_token['access_token'];

                        // save access token in database
                        $this->saveAccessToken($access_token);
                    }
                }
            }
        }
        else {
            $access_token = $previous_oauth->getValue();
        }

        if (isset($access_token)) {
            // get instagram user id
            if (null !== ($user_id = $this->getInstagramUserId($access_token))) {
                $endpoint   = $this->api_baseurl . 'users/' . $user_id . '/media/recent/?access_token=' . $access_token;
                $client     = new GuzzleClient();
                $request    = $client->request('GET', $endpoint);
                $data       = (string)$request->getBody();
                $data       = json_decode($data, true);

                if (isset($data['data'])) {
                    foreach ($data['data'] as $instagram_post) {
                        // check if post exists
                        $aggregator_post = $this->em->getRepository('DufAggregatorBundle:AggregatorPost')->findOneBy(
                            array(
                                'postId'    => $instagram_post['id'],
                                'service'   => $account->getService(),
                            )
                        );

                        // do not import twice same post
                        if (!empty($aggregator_post))
                            continue;

                        // only import images
                        if ($instagram_post['type'] !== 'image')
                            continue;

                        $aggregator_post = $this->getAggregatorPost($account, $instagram_post);

                        $this->em->persist($aggregator_post);
                    }

                    $this->em->flush();
                }
            }
        }

        return true;
    }

    private function getInstagramConfig($config_node)
    {
        return $this->container->get('duf_aggregator.dufaggregatorconfig')->getOauthConfig($this->account, $config_node);
    }

    private function getRedirectUri()
    {
        $url = $this->router->generate('duf_admin_aggregator_refresh', array('account_id' => 'null'), UrlGeneratorInterface::ABSOLUTE_URL);
        $url .= '?account_id=' . $this->account->getId();

        return $url;
    }

    private function saveAccessToken($access_token)
    {
        $expire = new \DateTime();
        $expire->add(new \DateInterval('P30D'));

        $aggregator_oauth = new AggregatorOauth();
        $aggregator_oauth->setValue($access_token);
        $aggregator_oauth->setService($this->account->getService());
        $aggregator_oauth->setName('access_token');
        $aggregator_oauth->setExpiresAt($expire);

        $this->em->persist($aggregator_oauth);
        $this->em->flush();
    }

    private function getPreviousOauth()
    {
        return $this->em->getRepository('DufAggregatorBundle:AggregatorOauth')->findByPreviousOauth($this->account->getService());
    }

    private function removePreviousOauth()
    {
        $previous_oauth = $this->em->getRepository('DufAggregatorBundle:AggregatorOauth')->findBy(
            array(
                'service'   => $this->account->getService(),
            )
        );

        foreach ($previous_oauth as $oauth) {
            $this->em->remove($oauth);
        }

        $this->em->flush();
    }

    private function getInstagramUserId($access_token)
    {
        $endpoint   = $this->api_baseurl . 'users/search?access_token=' . $access_token . '&q=' . $this->account->getAccountId();
        $client     = new GuzzleClient();
        $request    = $client->request('GET', $endpoint);
        $data       = (string)$request->getBody();
        $data       = json_decode($data, true);

        if (isset($data['data'])) {
            foreach ($data['data'] as $user) {
                if ($user['username'] === $this->account->getAccountId())
                    return $user['id'];
            }
        }

        return null;
    }

    private function getAggregatorPost($account, $instagram_post)
    {
        $post               = new AggregatorPost();
        $aggregator_config  = $this->container->get('duf_aggregator.dufaggregatorconfig');
        $enabled            = $aggregator_config->isAutomaticallyApproved($account);

        $post->setService($account->getService());
        $post->setAccount($account);
        $post->setPostId($instagram_post['id']);
        $post->setEnabled($enabled);

        // caption
        if (isset($instagram_post['caption']))
            $post->setCaption($instagram_post['caption']);

        // post picture
        if (isset($instagram_post['images']) && !empty($instagram_post['images'])) {
            foreach ($instagram_post['images'] as $image_size => $instagram_image) {
                if ($image_size !== 'standard_resolution')
                    continue;

                $post->setImage($instagram_image['url']);
            }
        }

        // post date
        $post_datetime = new \DateTime();
        $post_datetime->setTimestamp($instagram_post['created_time']);

        $post->setPostDate($post_datetime);

        // user
        if (isset($instagram_post['user'])) {
            if (isset($instagram_post['user']['id'])) {
                $instagram_post['user']['name']     = (!empty($instagram_post['user']['full_name'])  && strlen($instagram_post['user']['full_name']) > 1) ? $instagram_post['user']['full_name']: $account->getAccountId();
                $instagram_post['user']['link']     = 'https://www.instagram.com/' . $account->getAccountId();
                $instagram_post['user']['picture']  = $instagram_post['user']['profile_picture'];
            }

            $post->setPostUser(json_encode($instagram_post['user']));
        }

        // post URL
        $post->setLink($instagram_post['link']);

        return $post;
    }
}