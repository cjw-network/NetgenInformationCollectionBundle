<?php

namespace Netgen\Bundle\InformationCollectionBundle\Form\Captcha;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use Netgen\Bundle\InformationCollectionBundle\API\Service\CaptchaService as CaptchaServiceInterface;

class CaptchaService implements CaptchaServiceInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    protected $contentTypeService;

    /**
     * CaptchaService constructor.
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentTypeService
     * @param array $config
     */
    public function __construct(ContentTypeService $contentTypeService, $config = [])
    {
        $this->config = $config;
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(Location $location)
    {
        $config = $this->getConfig($location);
        dump($config);

        return $config['enabled'];
    }

    /**
     * @inheritdoc
     */
    public function getSiteKey(Location $location)
    {
        $config = $this->getConfig($location);

        return $config['site_key'];
    }

    /**
     * @inheritdoc
     */
    public function getCaptcha(Location $location)
    {
        $config = $this->getConfig($location);

        if ($config['enabled']) {
            $reCaptcha = new \ReCaptcha\ReCaptcha($config['secret']);

            if (!empty($config['options'])) {

                if (!empty($config['options']['hostname'])) {
                    $reCaptcha->setExpectedHostname($config['options']['hostname']);
                }
                if (!empty($config['options']['apk_package_name'])) {
                    $reCaptcha->setExpectedApkPackageName($config['options']['apk_package_name']);
                }
                if (!empty($config['options']['action'])) {
                    $reCaptcha->setExpectedAction($config['options']['action']);
                }
                if (!empty($config['options']['score_threshold'])) {
                    $reCaptcha->setScoreThreshold($config['options']['score_threshold']);
                }
                if (!empty($config['options']['challenge_timeout'])) {
                    $reCaptcha->setChallengeTimeout($config['options']['challenge_timeout']);
                }
            }

            return new ReCaptcha($reCaptcha);
        }

        return new NullObject();
    }

    /**
     * Returns filtered config for current Location
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     *
     * @return array
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    protected function getConfig(Location $location)
    {
        $contentTypeConfig = $this->getConfigForContentType(
            $this->getContentType($location)
        );

        return array_replace($this->config, $contentTypeConfig);
    }

    /**
     * Returns filtered config for current ContentType
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     *
     * @return array
     */
    protected function getConfigForContentType(ContentType $contentType)
    {
        if ($this->hasConfigForContentType($contentType)) {
            return $this->config['override_by_type'][$contentType->identifier];
        }

        return [];
    }

    /**
     * Checks if override exist for given ContentType
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     *
     * @return bool
     */
    protected function hasConfigForContentType(ContentType $contentType)
    {
        if (!empty($this->config['override_by_type'])) {
            if (in_array($contentType->identifier, array_keys($this->config['override_by_type']))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method for retrieving ContentType from Location
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     *
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentType
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    protected function getContentType(Location $location)
    {
        return $this->contentTypeService
            ->loadContentType($location->contentInfo->contentTypeId);
    }
}
