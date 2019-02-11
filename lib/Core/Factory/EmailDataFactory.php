<?php

declare(strict_types=1);

namespace Netgen\InformationCollection\Core\Factory;

use function array_key_exists;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\Core\FieldType\BinaryFile\Value as BinaryFile;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\Core\Helper\TranslationHelper;
use Netgen\InformationCollection\API\Value\Event\InformationCollected;
use Netgen\InformationCollection\API\Exception\MissingEmailBlockException;
use Netgen\InformationCollection\API\Exception\MissingValueException;
use Netgen\InformationCollection\API\Value\DataTransfer\TemplateContent;
use Netgen\InformationCollection\API\Value\DataTransfer\EmailContent;
use Netgen\InformationCollection\API\Constants;
use function trim;
use Twig_Environment;

class EmailDataFactory
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var \eZ\Publish\Core\Helper\FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * EmailDataFactory constructor.
     *
     * @param array $config
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     * @param \eZ\Publish\Core\Helper\FieldHelper $fieldHelper
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \Twig_Environment $twig
     */
    public function __construct(
        array $config,
        TranslationHelper $translationHelper,
        FieldHelper $fieldHelper,
        ContentService $contentService,
        Twig_Environment $twig
    ) {
        $this->config = $config;
        $this->translationHelper = $translationHelper;
        $this->fieldHelper = $fieldHelper;
        $this->contentService = $contentService;
        $this->twig = $twig;
    }

    /**
     * Factory method.
     *
     * @param InformationCollected $value
     *
     * @return EmailData
     */
    public function build(InformationCollected $value)
    {
        $location = $value->getLocation();
        $contentType = $value->getContentType();
        $content = $this->contentService->loadContent($location->contentId);

        $template = $this->resolveTemplate($contentType->identifier);

        $templateWrapper = $this->twig->load($template);
        $data = new TemplateData($value, $content, $templateWrapper);

        $body = $this->resolveBody($data);

        return new EmailData(
            $this->resolveEmail($data, Constants::FIELD_RECIPIENT),
            $this->resolveEmail($data, Constants::FIELD_SENDER),
            $this->resolve($data, Constants::FIELD_SUBJECT),
            $body,
            $this->resolveAttachments($contentType->identifier, $value->getInformationCollectionStruct()->getCollectedFields())
        );
    }

    /**
     * Returns resolved parameter.
     *
     * @param TemplateData $data
     * @param string $field
     * @param string $property
     *
     * @return string
     */
    protected function resolve(TemplateData $data, $field, $property = Constants::FIELD_TYPE_TEXT)
    {
        $rendered = '';
        if ($data->getTemplateWrapper()->hasBlock($field)) {
            $rendered = $data->getTemplateWrapper()->renderBlock(
                $field,
                [
                    'event' => $data->getEvent(),
                    'collected_fields' => $data->getEvent()->getInformationCollectionStruct()->getCollectedFields(),
                    'content' => $data->getContent(),
                ]
            );

            $rendered = trim($rendered);
        }

        if (!empty($rendered)) {
            return $rendered;
        }

        $content = $data->getContent();
        if (array_key_exists($field, $content->fields) &&
            !$this->fieldHelper->isFieldEmpty($content, $field)
        ) {
            $fieldValue = $this->translationHelper->getTranslatedField($content, $field);

            return $fieldValue->value->{$property};
        }

        if (!empty($this->config[ConfigurationConstants::DEFAULT_VARIABLES][$field])) {
            return $this->config[ConfigurationConstants::DEFAULT_VARIABLES][$field];
        }

        throw new MissingValueException($field);
    }

    /**
     * Returns resolved email parameter.
     *
     * @param TemplateData $data
     * @param string $field
     *
     * @return string
     */
    protected function resolveEmail(TemplateData $data, $field)
    {
        $rendered = '';
        if ($data->getTemplateWrapper()->hasBlock($field)) {
            $rendered = $data->getTemplateWrapper()->renderBlock(
                $field,
                [
                    'event' => $data->getEvent(),
                    'collected_fields' => $data->getEvent()->getInformationCollectionStruct()->getCollectedFields(),
                    'content' => $data->getContent(),
                ]
            );

            $rendered = trim($rendered);
        }

        if (!empty($rendered) && filter_var($rendered, FILTER_VALIDATE_EMAIL)) {
            return $rendered;
        }

        $content = $data->getContent();
        if (array_key_exists($field, $content->fields) &&
            !$this->fieldHelper->isFieldEmpty($content, $field)
        ) {
            $fieldValue = $this->translationHelper->getTranslatedField($content, $field);

            return $fieldValue->value->email;
        }

        if (!empty($this->config[ConfigurationConstants::DEFAULT_VARIABLES][$field])) {
            return $this->config[ConfigurationConstants::DEFAULT_VARIABLES][$field];
        }

        throw new MissingValueException($field);
    }

    /**
     * Returns resolved template name.
     *
     * @param string $contentTypeIdentifier
     *
     * @return string
     */
    protected function resolveTemplate($contentTypeIdentifier)
    {
        if (array_key_exists($contentTypeIdentifier, $this->config[ConfigurationConstants::TEMPLATES][ConfigurationConstants::CONTENT_TYPES])) {
            return $this->config[ConfigurationConstants::TEMPLATES][ConfigurationConstants::CONTENT_TYPES][$contentTypeIdentifier];
        }

        return $this->config[ConfigurationConstants::TEMPLATES][ConfigurationConstants::SETTINGS_DEFAULT];
    }

    /**
     * Renders email template.
     *
     * @param TemplateData $data
     *
     * @throws MissingEmailBlockException
     *
     * @return string
     */
    protected function resolveBody(TemplateData $data)
    {
        if ($data->getTemplateWrapper()->hasBlock(Constants::BLOCK_EMAIL)) {
            return $data->getTemplateWrapper()
                ->renderBlock(
                    Constants::BLOCK_EMAIL,
                    [
                        'event' => $data->getEvent(),
                        'collected_fields' => $data->getEvent()->getInformationCollectionStruct()->getCollectedFields(),
                        'content' => $data->getContent(),
                        'default_variables' => !empty($this->config[ConfigurationConstants::DEFAULT_VARIABLES])
                            ? $this->config[ConfigurationConstants::DEFAULT_VARIABLES] : null,
                    ]
                );
        }

        throw new MissingEmailBlockException(
            $data->getTemplateWrapper()->getSourceContext()->getName(),
            $data->getTemplateWrapper()->getBlockNames()
        );
    }

    /**
     * @param $contentTypeIdentifier
     * @param array $collectedFields
     *
     * @return BinaryFile[]|null
     */
    protected function resolveAttachments($contentTypeIdentifier, array $collectedFields)
    {
        if (empty($this->config[ConfigurationConstants::ATTACHMENTS])) {
            return null;
        }

        if (array_key_exists($contentTypeIdentifier, $this->config[ConfigurationConstants::ATTACHMENTS][ConfigurationConstants::CONTENT_TYPES])) {
            $send = $this->config[ConfigurationConstants::ATTACHMENTS][ConfigurationConstants::CONTENT_TYPES][$contentTypeIdentifier];
        } else {
            $send = $this->config[ConfigurationConstants::ATTACHMENTS][ConfigurationConstants::SETTINGS_DEFAULT];
        }

        if (!$send) {
            return null;
        }

        return $this->getBinaryFileFields($collectedFields);
    }

    /**
     * @param array $collectedFields
     *
     * @return BinaryFile[]|null
     */
    protected function getBinaryFileFields(array $collectedFields)
    {
        $filtered = [];
        foreach ($collectedFields as $identifier => $value) {
            if ($value instanceof BinaryFile) {
                $filtered[] = $value;
            }
        }

        return empty($filtered) ? null : $filtered;
    }
}
