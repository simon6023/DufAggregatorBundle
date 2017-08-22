<?php

namespace Duf\AggregatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('DufAggregatorBundle:Default:index.html.twig');
    }
}
