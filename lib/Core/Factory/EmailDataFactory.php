<?php

declare(strict_types=1);

namespace Netgen\InformationCollection\Core\Factory;

use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\FieldType\BinaryFile\Value as BinaryFile;
use Ibexa\Core\Helper\FieldHelper;
use Ibexa\Core\Helper\TranslationHelper;
use Netgen\InformationCollection\API\ConfigurationConstants;
use Netgen\InformationCollection\API\Constants;
use Netgen\InformationCollection\API\Exception\MissingEmailBlockException;
use Netgen\InformationCollection\API\Exception\MissingValueException;
use Netgen\InformationCollection\API\Value\DataTransfer\EmailContent;
use Netgen\InformationCollection\API\Value\DataTransfer\TemplateContent;
use Netgen\InformationCollection\API\Value\Event\InformationCollected;
use Netgen\InformationCollection\Core\Action\EmailAction;
use Twig\Environment;
use function array_filter;
use function array_key_exists;
use function explode;
use function filter_var;
use function trim;
use const FILTER_VALIDATE_EMAIL;

class EmailDataFactory extends BaseEmailDataFactory
{
    /**
     * @var array
     */
    protected $configResolver;

    /**
     * @var \Ibexa\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var \Ibexa\Core\Helper\FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    public function __construct(
        ConfigResolverInterface $configResolver,
        TranslationHelper $translationHelper,
        FieldHelper $fieldHelper,
        Environment $twig
    ) {
        $this->configResolver = $configResolver;
        $this->config = $this->configResolver->getParameter('action_config', 'netgen_information_collection')[EmailAction::$defaultName];
        $this->translationHelper = $translationHelper;
        $this->fieldHelper = $fieldHelper;
        $this->twig = $twig;
    }

    /**
     * Factory method.
     */
    public function build(InformationCollected $value): EmailContent
    {
        $contentType = $value->getContentType();

        $template = $this->resolveTemplate($contentType->identifier);

        $templateWrapper = $this->twig->load($template);
        $data = new TemplateContent($value, $templateWrapper);

        $body = $this->resolveBody($data);

        return new EmailContent(
            $this->resolveEmail($data, Constants::FIELD_RECIPIENT),
            $this->resolveEmail($data, Constants::FIELD_SENDER),
            $this->resolve($data, Constants::FIELD_SUBJECT),
            $body,
            $this->resolveAttachments($contentType->identifier, $value->getInformationCollectionStruct()->getFieldsData())
        );
    }

    /**
     * Returns resolved parameter.
     *
     * @param string $field
     * @param string $property
     *
     * @return string
     */
    protected function resolve(TemplateContent $data, $field, $property = Constants::FIELD_TYPE_TEXT)
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
        if (array_key_exists($field, $content->fields)
            && !$this->fieldHelper->isFieldEmpty($content, $field)
        ) {
            $fieldValue = $this->translationHelper->getTranslatedField($content, $field);

            if ($fieldValue instanceof Field) {
                return $fieldValue->value->{$property};
            }
        }

        if (!empty($this->config[ConfigurationConstants::DEFAULT_VARIABLES][$field])) {
            return $this->config[ConfigurationConstants::DEFAULT_VARIABLES][$field];
        }

        throw new MissingValueException($field);
    }

    /**
     * Returns resolved email parameter.
     *
     * @param string $field
     *
     * @return array
     */
    protected function resolveEmail(TemplateContent $data, $field)
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
            $emails = explode(',', $rendered);

            $emails = array_filter($emails, static function ($var) {
                return filter_var($var, FILTER_VALIDATE_EMAIL);
            });

            if (!empty($emails)) {
                return $emails;
            }
        }

        $content = $data->getContent();
        if (array_key_exists($field, $content->fields)
            && !$this->fieldHelper->isFieldEmpty($content, $field)
        ) {
            $fieldValue = $this->translationHelper->getTranslatedField($content, $field);

            if ($fieldValue instanceof Field) {
                return [$fieldValue->value->email];
            }
        }

        if (!empty($this->config[ConfigurationConstants::DEFAULT_VARIABLES][$field])) {
            return [$this->config[ConfigurationConstants::DEFAULT_VARIABLES][$field]];
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
     * @throws MissingEmailBlockException
     *
     * @return string
     */
    protected function resolveBody(TemplateContent $data)
    {
        if ($data->getTemplateWrapper()->hasBlock(Constants::BLOCK_EMAIL)) {
            return $data->getTemplateWrapper()
                ->renderBlock(
                    Constants::BLOCK_EMAIL,
                    [
                        'event' => $data->getEvent(),
                        'collected_fields' => $data->getEvent()->getInformationCollectionStruct()->getFieldsData(),
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
     * @return BinaryFile[]
     */
    protected function resolveAttachments(string $contentTypeIdentifier, array $collectedFields)
    {
        if (empty($this->config[ConfigurationConstants::ATTACHMENTS])) {
            return [];
        }

        if (array_key_exists($contentTypeIdentifier, $this->config[ConfigurationConstants::ATTACHMENTS][ConfigurationConstants::CONTENT_TYPES])) {
            $send = $this->config[ConfigurationConstants::ATTACHMENTS][ConfigurationConstants::CONTENT_TYPES][$contentTypeIdentifier];
        } else {
            $send = $this->config[ConfigurationConstants::ATTACHMENTS][ConfigurationConstants::SETTINGS_DEFAULT];
        }

        if (!$send) {
            return [];
        }

        return $this->getBinaryFileFields($collectedFields);
    }

    /**
     * @return BinaryFile[]
     */
    protected function getBinaryFileFields(array $collectedFields)
    {
        $filtered = [];
        foreach ($collectedFields as $identifier => $value) {
            if ($value instanceof BinaryFile) {
                $filtered[] = $value;
            }
        }

        return empty($filtered) ? [] : $filtered;
    }
}
