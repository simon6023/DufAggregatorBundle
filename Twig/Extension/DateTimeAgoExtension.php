<?php
    
namespace Duf\AggregatorBundle\Twig\Extension;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig_Extension;
use Twig_Filter_Method;

use \Smirik\PHPDateTimeAgo\DateTimeAgo as DateTimeAgo;

use Symfony\Component\HttpFoundation\RequestStack;

class DateTimeAgoExtension extends \Twig_Extension
{
    protected $container;
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
    
    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return array(
            'ago' => new Twig_Filter_Method($this, 'ago')
        );
    }

    public function ago($date)
    {
        $request    = $this->requestStack->getCurrentRequest();
        $locale     = $request->getLocale();

        if ($locale == 'ru') {
            $datetime_ago = new DateTimeAgo(new \Smirik\PHPDateTimeAgo\TextTranslator\RussianTextTranslator());
            $datetime_ago->setFormat('d.m.Y');
        }
        else {
            $datetime_ago = new DateTimeAgo(new \Smirik\PHPDateTimeAgo\TextTranslator\EnglishTextTranslator());
            $datetime_ago->setFormat('d/m/Y');
        }

        return $datetime_ago->get($date);
    }

    public function getName()
    {
        return 'ago.twig.extension';
    }   
}