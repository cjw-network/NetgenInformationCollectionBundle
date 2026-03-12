<?php

declare(strict_types=1);

namespace Netgen\InformationCollection\API\Value\DataTransfer;

use Netgen\InformationCollection\API\Value\ValueObject;

class EmailContent extends ValueObject
{
    protected array $recipients;

    protected array $cc;

    protected array $bcc;

    protected string $subject;

    protected array $sender;

    protected string $body;

    /**
     * @var \Ibexa\Core\FieldType\BinaryFile\Value[]
     */
    protected array $attachments = [];

    /**
     * @param \Ibexa\Core\FieldType\BinaryFile\Value[] $attachments
     */
    public function __construct(array $recipients, array $sender, string $subject, string $body, array $attachments = [], array $cc = [], array $bcc = [])
    {
        $this->recipients = $recipients;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->sender = $sender;
        $this->body = $body;
        $this->attachments = $attachments;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getSender(): array
    {
        return $this->sender;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * @return \Ibexa\Core\FieldType\BinaryFile\Value[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @return string
     */
    public function getCc() : array
    {
        return $this->cc;
    }

    /**
     * @return bool
     */
    public function hasCc() : bool
    {
        return !empty( $this->cc );
    }

    /**
     * @return array
     */
    public function getBcc() : array
    {
        return $this->bcc;
    }

    /**
     * @return bool
     */
    public function hasBcc() : bool
    {
        return !empty( $this->bcc );
    }
}
