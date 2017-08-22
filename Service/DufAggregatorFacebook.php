<?php
namespace Duf\AggregatorBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\ORM\EntityManager as EntityManager;

use Facebook\Facebook;
use Facebook\FacebookApp;
use Facebook\FacebookRequest;

use Duf\AggregatorBundle\Entity\AggregatorOauth;
use Duf\AggregatorBundle\Entity\AggregatorPost;

class DufAggregatorFacebook
{
    private $container;
    private $router;
    private $em;

    private $facebook_config;

    public function __construct(Container $container, Router $router, EntityManager $em)
    {
        $this->container        = $container;
        $this->router           = $router;
        $this->em               = $em;

        $this->facebook_config  = array();
    }

    public function refresh($account, $parameters)
    {
        // oauth to facebook
        $app_id         = $this->getFacebookConfig($account, 'app_id');
        $app_secret     = $this->getFacebookConfig($account, 'app_secret');

        if(null === $app_id || null === $app_secret)
            return false;

        $fb             = new Facebook(array(
                            'app_id'                => $app_id,
                            'app_secret'            => $app_secret,
                            'default_graph_version' => 'v2.2',
                          )
                        );
        $helper         = $fb->getRedirectLoginHelper();
        $permissions    = array();

        // check access token from previous login
        if (null === ($previous_oauth = $this->getPreviousOauth($account, 'access_token'))) {
            // remove previous oauth
            $this->removePreviousOauth($account);

            // login if is not callback action
            if (!isset($parameters['code'])) {
                $callback_url   = $this->router->generate('duf_admin_aggregator_refresh', array('account_id' => $account->getId()), UrlGeneratorInterface::ABSOLUTE_URL);
                $login_url      = $helper->getLoginUrl($callback_url, $permissions);

                return $login_url;
            }
            else {
                $access_token = $helper->getAccessToken();

                if (!$access_token->getValue())
                    exit('invalid access token');

                // exchange short lived access token for long lived access token
                $oAuth2Client   = $fb->getOAuth2Client();
                $tokenMetadata  = $oAuth2Client->debugToken($access_token);

                $tokenMetadata->validateAppId((string)$app_id);
                $tokenMetadata->validateExpiration();

                if (!$access_token->isLongLived())
                    $access_token = $oAuth2Client->getLongLivedAccessToken($access_token);

                // save access token in database
                $this->saveAccessToken($account, $access_token);
            }
        }
        else {
            $access_token = $previous_oauth->getValue();
        }

        // process to feed request
        if (isset($access_token)) {
            $fields         = array('created_time', 'message', 'link', 'full_picture', 'object_id', 'from');
            $endpoint       = '/' . $account->getAccountId() . '/posts?fields=' . implode(',', $fields);

            $fb_app         = new FacebookApp($app_id, $app_secret);
            $fb_request     = new FacebookRequest($fb_app, $access_token, 'GET', $endpoint);
            $fb_response    = $fb->getClient()->sendRequest($fb_request);

            if (200 === $fb_response->getHttpStatusCode()) {
                $data = $fb_response->getDecodedBody();

                if (isset($data['data'])) {
                    foreach ($data['data'] as $fb_post) {
                        // check if post exists
                        $aggregator_post = $this->em->getRepository('DufAggregatorBundle:AggregatorPost')->findOneBy(
                            array(
                                'postId'    => $fb_post['id'],
                                'service'   => $account->getService(),
                            )
                        );

                        if (!empty($aggregator_post))
                            continue;

                        $aggregator_post = $this->getAggregatorPost($account, $fb_post);

                        $this->em->persist($aggregator_post);
                    }

                    $this->em->flush();
                }
            }
        }

        return true;
    }

    private function getFacebookConfig($account, $config_node)
    {
        if (isset($this->facebook_config[$config_node]))
            return $this->facebook_config[$config_node];

        $this->facebook_config[$config_node] = $this->container->get('duf_aggregator.dufaggregatorconfig')->getOauthConfig($account, $config_node);

        return $this->facebook_config[$config_node];
    }

    private function getPreviousOauth($account)
    {
        return $this->em->getRepository('DufAggregatorBundle:AggregatorOauth')->findByPreviousOauth($account->getService());
    }

    private function saveAccessToken($account, $access_token)
    {
        $aggregator_oauth = new AggregatorOauth();
        $aggregator_oauth->setValue($access_token->getValue());
        $aggregator_oauth->setService($account->getService());
        $aggregator_oauth->setName('access_token');
        $aggregator_oauth->setExpiresAt($access_token->getExpiresAt());

        $this->em->persist($aggregator_oauth);
        $this->em->flush();
    }

    private function removePreviousOauth($account)
    {
        $previous_oauth = $this->em->getRepository('DufAggregatorBundle:AggregatorOauth')->findBy(
            array(
                'service'   => $account->getService(),
            )
        );

        foreach ($previous_oauth as $oauth) {
            $this->em->remove($oauth);
        }

        $this->em->flush();
    }

    private function getAggregatorPost($account, $fb_post)
    {
        $post               = new AggregatorPost();
        $aggregator_config  = $this->container->get('duf_aggregator.dufaggregatorconfig');
        $enabled            = $aggregator_config->isAutomaticallyApproved($account);

        $post->setService($account->getService());
        $post->setAccount($account);
        $post->setPostId($fb_post['id']);
        $post->setEnabled($enabled);

        // caption
        if (isset($fb_post['message']))
            $post->setCaption($fb_post['message']);

        // post picture
        if (isset($fb_post['full_picture']) && !empty($fb_post['full_picture']) && strlen($fb_post['full_picture']) > 1)
            $post->setImage($fb_post['full_picture']);

        // post date
        $post_datetime = new \DateTime($fb_post['created_time']);
        $post->setPostDate($post_datetime);

        // user
        if (isset($fb_post['from'])) {
            if (isset($fb_post['from']['id'])) {
                $fb_post['from']['link']    = 'https://www.facebook.com/' . $fb_post['from']['id'];
                $fb_post['from']['picture'] = 'https://graph.facebook.com/' . $fb_post['from']['id'] . '/picture?type=large';
            }

            $post->setPostUser(json_encode($fb_post['from']));
        }

        // post URL
        $post->setLink('https://www.facebook.com/' . $fb_post['id']);

        return $post;
    }
}