<?php
namespace Duf\AggregatorBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\ORM\EntityManager as EntityManager;

use Abraham\TwitterOAuth\TwitterOAuth;

use Duf\AggregatorBundle\Entity\AggregatorOauth;
use Duf\AggregatorBundle\Entity\AggregatorPost;

class DufAggregatorTwitter
{
    private $container;
    private $router;
    private $em;

    protected $account;

    public function __construct(Container $container, Router $router, EntityManager $em)
    {
        $this->container        = $container;
        $this->router           = $router;
        $this->em               = $em;
    }

    public function refresh($account, $parameters)
    {
        $this->account  = $account;

        $connection     = new TwitterOAuth($this->getTwitterConfig('api_key'), $this->getTwitterConfig('api_secret'), $this->getTwitterConfig('access_token'), $this->getTwitterConfig('access_token_secret'));
        $content        = $connection->get('statuses/user_timeline', array(
                'screen_name'       => $account->getAccountId(),
                'exclude_replies'   => true,
                'include_rts'       => false,
            )
        );

        if (is_array($content)) {
            foreach ($content as $tweet) {
                // check if post exists
                $aggregator_post = $this->em->getRepository('DufAggregatorBundle:AggregatorPost')->findOneBy(
                    array(
                        'postId'    => $tweet->id,
                        'service'   => $account->getService(),
                    )
                );

                if (!empty($aggregator_post))
                    continue;

                $aggregator_post = $this->getAggregatorPost($account, $tweet);

                $this->em->persist($aggregator_post);
            }

            $this->em->flush();
        }

        return true;
    }

    private function getTwitterConfig($config_node)
    {
        return $this->container->get('duf_aggregator.dufaggregatorconfig')->getOauthConfig($this->account, $config_node);
    }

    private function getAggregatorPost($account, $tweet)
    {
        $post               = new AggregatorPost();
        $aggregator_config  = $this->container->get('duf_aggregator.dufaggregatorconfig');
        $enabled            = $aggregator_config->isAutomaticallyApproved($account);

        $post->setService($account->getService());
        $post->setAccount($account);
        $post->setPostId($tweet->id);
        $post->setEnabled($enabled);

        // caption
        if (isset($tweet->text))
            $post->setCaption($tweet->text);

        // post picture
        if (isset($tweet->entities) && isset($tweet->entities->media) && !empty($tweet->entities->media)) {
            $has_image = false;

            foreach ($tweet->entities->media as $tweet_entity) {
                // only get first image
                if ($has_image)
                    continue;

                // only get photos
                if ($tweet_entity->type !== 'photo')
                    continue;

                $post->setImage($tweet_entity->media_url_https);

                $has_image = true;
            }
        }

        // post date
        $post_datetime = new \DateTime($tweet->created_at);
        $post->setPostDate($post_datetime);

        // user
        if (isset($tweet->user)) {
            $user_json = json_encode(
                array(
                    'name'          => $tweet->user->name,
                    'screen_name'   => $tweet->user->screen_name,
                    'link'          => $tweet->user->url,
                    'picture'       => 'https://twitter.com/' . $tweet->user->screen_name . '/profile_image?size=bigger',
                )
            );

            $post->setPostUser($user_json);

            // post URL
            $post->setLink('https://twitter.com/' . $tweet->user->screen_name . '/status/' . $tweet->id);
        }

        return $post;
    }
}