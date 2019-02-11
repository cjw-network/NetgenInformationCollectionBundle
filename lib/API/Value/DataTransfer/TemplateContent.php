<?php

declare(strict_types=1);

namespace Netgen\InformationCollection\API\Value\DataTransfer;

use eZ\Publish\API\Repository\Values\Content\Content;
use Netgen\Bundle\InformationCollectionBundle\Event\InformationCollected;
use Netgen\InformationCollection\API\Value\ValueObject;
use Twig_TemplateWrapper;

class TemplateContent extends ValueObject
{
    /**
     * @var InformationCollected
     */
    protected $event;

    /**
     * @var Content
     */
    protected $content;

    /**
     * @var Twig_TemplateWrapper
     */
    protected $templateWrapper;

    /**
     * TemplateData constructor.
     *
     * @param InformationCollected $event
     * @param Content $content
     * @param Twig_TemplateWrapper $templateWrapper
     */
    public function __construct(InformationCollected $event, Content $content, Twig_TemplateWrapper $templateWrapper)
    {
        $this->event = $event;
        $this->content = $content;
        $this->templateWrapper = $templateWrapper;
    }

    /**
     * @return InformationCollected
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return Twig_TemplateWrapper
     */
    public function getTemplateWrapper()
    {
        return $this->templateWrapper;
    }
}
