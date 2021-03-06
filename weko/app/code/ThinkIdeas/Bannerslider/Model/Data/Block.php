<?php
/**
* Copyright 2016 thinkIdeas. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace ThinkIdeas\Bannerslider\Model\Data;

use ThinkIdeas\Bannerslider\Api\Data\BlockInterface;
use Magento\Framework\Api\AbstractExtensibleObject;
use ThinkIdeas\Bannerslider\Api\Data\BlockExtensionInterface;

/**
 * Block data model
 * @codeCoverageIgnore
 */
class Block extends AbstractExtensibleObject implements BlockInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBanner()
    {
        return $this->_get(self::BANNER);
    }

    /**
     * {@inheritdoc}
     */
    public function setBanner($banner)
    {
        return $this->setData(self::BANNER, $banner);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlides()
    {
        return $this->_get(self::SLIDES);
    }

    /**
     * {@inheritdoc}
     */
    public function setSlides($slides)
    {
        return $this->setData(self::SLIDES, $slides);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensionAttributes(BlockExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
