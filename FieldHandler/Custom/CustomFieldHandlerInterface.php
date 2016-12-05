<?php

namespace Netgen\Bundle\InformationCollectionBundle\FieldHandler\Custom;

use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\FieldType\Value;
use Netgen\Bundle\InformationCollectionBundle\Value\LegacyHandledFieldValue;

interface CustomFieldHandlerInterface
{
    /**
     * Checks if given Value can be handled
     *
     * @param Value $value
     *
     * @return boolean
     */
    public function supports(Value $value);

    /**
     * Transforms field value object to string
     *
     * @param Value $value
     * @param FieldDefinition $fieldDefinition
     *
     * @return string
     */
    public function toString(Value $value, FieldDefinition $fieldDefinition);
}