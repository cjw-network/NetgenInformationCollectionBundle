<?php

declare(strict_types=1);

namespace Netgen\InformationCollection\API\FieldHandler;

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\Value;

interface CustomFieldHandlerInterface
{
    /**
     * Checks if given Value can be handled.
     */
    public function supports(Value $value): bool;

    /**
     * Transforms field value object to string.
     */
    public function toString(Value $value, FieldDefinition $fieldDefinition): string;
}
