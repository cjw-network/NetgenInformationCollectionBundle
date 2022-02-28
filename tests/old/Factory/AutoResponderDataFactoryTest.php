<?php

namespace Netgen\Bundle\InformationCollectionBundle\Tests\Factory;

use Netgen\Bundle\InformationCollectionBundle\Factory\AutoResponderDataFactory;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Core\FieldType\EmailAddress\Value as EmailValue;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\Helper\FieldHelper;
use Ibexa\Core\Helper\TranslationHelper;
use Ibexa\Core\Repository\ContentService;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Netgen\Bundle\IbexaFormsBundle\Form\DataWrapper;
use Netgen\Bundle\IbexaFormsBundle\Form\Payload\InformationCollectionStruct;
use Netgen\Bundle\InformationCollectionBundle\Event\InformationCollected;
use Netgen\Bundle\InformationCollectionBundle\Factory\EmailDataFactory;
use Netgen\Bundle\InformationCollectionBundle\Value\EmailData;
use PHPUnit\Framework\TestCase;
use Twig_Environment;
use Twig_Loader_Array;
use Twig_TemplateWrapper;

class AutoResponderDataFactoryTest extends TestCase
{
    /**
     * @var \Netgen\Bundle\InformationCollectionBundle\Factory\AutoResponderDataFactory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translationHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentService;

    /**
     * @var \Ibexa\Core\Repository\Values\ContentType\ContentType
     */
    protected $contentType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $twig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $templateWrapper;

    /**
     * @var \Ibexa\Core\Repository\Values\ContentType\ContentType
     */
    protected $contentType2;

    /**
     * @var \Ibexa\Core\Repository\Values\Content\VersionInfo
     */
    protected $versionInfo;

    public function setUp()
    {
        $this->config = array(
            'templates' => array(
                'default' => '@Acme/email.html.twig',
                'content_types' => array(
                    'test_content_type' => '@Acme/test_content_type.html.twig',
                )
            ),
            'default_variables' => array(
                'sender' => 'sender@example.com',
                'email_field_identifier' => 'email',
                'subject' => 'subject from configuration',
            ),
        );

        $this->translationHelper = $this->getMockBuilder(TranslationHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getTranslatedField'))
            ->getMock();

        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isFieldEmpty'))
            ->getMock();

        $this->contentService = $this->getMockBuilder(ContentService::class)
            ->disableOriginalConstructor()
            ->setMethods(array('loadContent'))
            ->getMock();

        $this->twig = $this->getMockBuilder(\Twig_Environment::class)
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();

        $this->contentType = new ContentType(array(
            'identifier' => 'test_content_type',
            'fieldDefinitions' => array(),
        ));

        $this->contentType2 = new ContentType(array(
            'identifier' => 'test_content_type2',
            'fieldDefinitions' => array(),
        ));

        $this->versionInfo = new VersionInfo(array(
            'contentInfo' => new ContentInfo(array(
                'contentTypeId' => 123,
            )),
        ));

        $this->factory = new AutoResponderDataFactory(
            $this->config,
            $this->translationHelper,
            $this->fieldHelper,
            $this->contentService,
            $this->twig
        );
        parent::setUp();
    }

    public function testBuildingWithSenderAndSubjectFromContent()
    {
        $twig = new Twig_Environment(
            new Twig_Loader_Array(
                array(
                    'index' => '{% block email %}{{ "email body" }}{% endblock %}',
                )
            )
        );

        $templateWrapper = new Twig_TemplateWrapper($twig, $twig->loadTemplate('index'));

        $this->factory = new AutoResponderDataFactory(
            $this->config,
            $this->translationHelper,
            $this->fieldHelper,
            $this->contentService,
            $this->twig
        );

        $senderField = new Field(array(
            'value' => new EmailValue('sender@test.com'),
            'languageCode' => 'eng_GB',
            'fieldDefIdentifier' => 'sender',
        ));

        $subjectField = new Field(array(
            'value' => new TextLineValue('subject test'),
            'languageCode' => 'eng_GB',
            'fieldDefIdentifier' => 'auto_responder_subject',
        ));

        $content = new Content(array(
            'internalFields' => array(
                $senderField, $subjectField,
            ),
            'versionInfo' => $this->versionInfo,
        ));

        $this->fieldHelper->expects($this->exactly(2))
            ->method('isFieldEmpty')
            ->withAnyParameters()
            ->willReturn(false);

        $this->translationHelper->expects($this->at(0))
            ->method('getTranslatedField')
            ->with($content, 'sender')
            ->willReturn($senderField);

        $this->translationHelper->expects($this->at(1))
            ->method('getTranslatedField')
            ->with($content, 'auto_responder_subject')
            ->willReturn($subjectField);

        $this->contentService->expects($this->once())
            ->method('loadContent')
            ->with(123)
            ->willReturn($content);

        $location = new Location(
            array(
                'id' => 12345,
                'contentInfo' => new ContentInfo(array('id' => 123)),
            )
        );

        $contentType = new ContentType(array(
            'identifier' => 'test',
            'fieldDefinitions' => array(),
        ));

        $informationCollectionStruct = new InformationCollectionStruct();
        $informationCollectionStruct->setCollectedFieldValue('my_value_1', new TextLineValue("My value 1"));
        $informationCollectionStruct->setCollectedFieldValue('email', new EmailValue("test@example.com"));
        $event = new InformationCollected(new DataWrapper($informationCollectionStruct, $contentType, $location));

        $this->twig->expects($this->once())
            ->method('load')
            ->willReturn($templateWrapper);

        $value = $this->factory->build($event);

        $this->assertInstanceOf(EmailData::class, $value);
        $this->assertEquals('test@example.com', $value->getRecipient());
        $this->assertEquals('sender@test.com', $value->getSender());
        $this->assertEquals('subject test', $value->getSubject());
        $this->assertEquals('email body', $value->getBody());
    }

    public function testBuildingWithSenderFromContentAndSubjectFromTemplate()
    {
        $twig = new Twig_Environment(
            new Twig_Loader_Array(
                array(
                    'index' => '{% block email %}{{ "email body" }}{% endblock %}{% block auto_responder_subject %}{{ "subject from template" }}{% endblock %}',
                )
            )
        );

        $templateWrapper = new Twig_TemplateWrapper($twig, $twig->loadTemplate('index'));

        $this->factory = new AutoResponderDataFactory(
            $this->config,
            $this->translationHelper,
            $this->fieldHelper,
            $this->contentService,
            $this->twig
        );

        $senderField = new Field(array(
            'value' => new EmailValue('sender@test.com'),
            'languageCode' => 'eng_GB',
            'fieldDefIdentifier' => 'sender',
        ));

        $subjectField = new Field(array(
            'value' => new TextLineValue('subject test'),
            'languageCode' => 'eng_GB',
            'fieldDefIdentifier' => 'auto_responder_subject',
        ));

        $content = new Content(array(
            'internalFields' => array(
                $senderField, $subjectField,
            ),
            'versionInfo' => $this->versionInfo,
        ));

        $this->fieldHelper->expects($this->exactly(1))
            ->method('isFieldEmpty')
            ->withAnyParameters()
            ->willReturn(false);

        $this->translationHelper->expects($this->at(0))
            ->method('getTranslatedField')
            ->with($content, 'sender')
            ->willReturn($senderField);

        $this->contentService->expects($this->once())
            ->method('loadContent')
            ->with(123)
            ->willReturn($content);

        $location = new Location(
            array(
                'id' => 12345,
                'contentInfo' => new ContentInfo(array('id' => 123)),
            )
        );

        $contentType = new ContentType(array(
            'identifier' => 'test',
            'fieldDefinitions' => array(),
        ));

        $informationCollectionStruct = new InformationCollectionStruct();
        $informationCollectionStruct->setCollectedFieldValue('my_value_1', new TextLineValue("My value 1"));
        $informationCollectionStruct->setCollectedFieldValue('email', new EmailValue("test@example.com"));
        $event = new InformationCollected(new DataWrapper($informationCollectionStruct, $contentType, $location));

        $this->twig->expects($this->once())
            ->method('load')
            ->willReturn($templateWrapper);

        $value = $this->factory->build($event);

        $this->assertInstanceOf(EmailData::class, $value);
        $this->assertEquals('test@example.com', $value->getRecipient());
        $this->assertEquals('sender@test.com', $value->getSender());
        $this->assertEquals('subject from template', $value->getSubject());
        $this->assertEquals('email body', $value->getBody());
    }
}
