<?php
namespace Duf\AggregatorBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class DufAggregatorConfig
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Retrieves DufAggregator configuration from config.yml
     *
     * @param string $nodes optional name of the configuration node, including parent nodes
     * @return mixed content of configuration node
     *
    */
    public function getAggregatorConfig($nodes = null)
    {
        $node_string = '';
        if (null !== $nodes) {
            if (is_array($nodes)) {
                foreach ($nodes as $node) {
                    $node_string .= '.' . $node;
                }
            }
            else {
                $node_string = '.' . $nodes;
            }
        }

        return $this->container->getParameter('duf_aggregator' . $node_string);
    }

    public function getServices($enabled = false)
    {
        $_services  = $this->getAggregatorConfig(array('services'));
        $services   = array();

        foreach ($_services as $service_name => $service_config) {
            $service_config['id'] = $service_name;

            if (!$enabled) {
                $services[$service_name] = $service_config;
            }
            else {
                if ($service_config['enabled'])
                    $services[$service_name] = $service_config;
            }                
        }

        return $services;
    }

    public function getService($service)
    {
        $services = $this->getServices();

        foreach ($services as $service_name => $service_config) {
            if ($service_name === $service)
                return $service_config;
        }

        return null;
    }

    public function getOauthConfig($account, $config_node)
    {
        $services = $this->getAggregatorConfig('services');

        if (
            isset($services[$account->getService()]) 
            && isset($services[$account->getService()]['oauth']) 
            && isset($services[$account->getService()]['oauth'][$config_node]) 
            && !empty($services[$account->getService()]['oauth'][$config_node])
        )
            return $services[$account->getService()]['oauth'][$config_node];

        return null;
    }

    // TO DO : check if posts must be automatically approved during refresh or manually approved later
    public function isAutomaticallyApproved($account)
    {
        return true;
    }
}