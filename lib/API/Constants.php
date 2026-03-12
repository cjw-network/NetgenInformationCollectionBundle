<?php

declare(strict_types=1);

namespace Netgen\InformationCollection\API;

final class Constants
{
    /**
     * Recipient field identifier.
     */
    public const string FIELD_RECIPIENT = 'recipient';

    /**
     * CC field identifier.
     */
    public const FIELD_CC = 'cc';

    /**
     * BCC field identifier.
     */
    public const FIELD_BCC = 'bcc';

    /**
     * Sender field identifier.
     */
    public const string FIELD_SENDER = 'sender';

    /**
     * Subject field identifier.
     */
    public const string FIELD_SUBJECT = 'subject';

    /**
     * Auto responder subject field identifier.
     */
    public const string FIELD_AUTO_RESPONDER_SUBJECT = 'auto_responder_subject';

    /**
     * Email field type.
     */
    public const string FIELD_TYPE_EMAIL = 'email';

    /**
     * Text field type.
     */
    public const string FIELD_TYPE_TEXT = 'text';

    /**
     * Block email.
     */
    public const string BLOCK_EMAIL = 'email';

    /**
     * Block recipient.
     */
    public const string BLOCK_RECIPIENT = 'recipient';

    /**
     * Block cc
     */
    public const BLOCK_CC = 'cc';

    /**
     * Block bcc
     */
    public const BLOCK_BCC = 'bcc';

    /**
     * Block sender.
     */
    public const string BLOCK_SENDER = 'sender';

    /**
     * Block subject.
     */
    public const string BLOCK_SUBJECT = 'subject';
}
