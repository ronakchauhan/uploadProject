<?php
/**
* Copyright 2016 thinkIdeas. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace ThinkIdeas\Bannerslider\Ui\Component\Listing\Columns;

use ThinkIdeas\Bannerslider\Api\Data\BannerInterface;
use ThinkIdeas\Bannerslider\Api\SlideRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class Slides
 * @package ThinkIdeas\Bannerslider\Ui\Component\Listing\Columns
 */
class Slides extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var SlideRepositoryInterface
     */
    private $slideRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SlideRepositoryInterface $slideRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SlideRepositoryInterface $slideRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->slideRepository = $slideRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $banner) {
                if (is_array($banner[BannerInterface::SLIDE_IDS])) {
                    $slides = $this->slideRepository
                        ->getList($this->getSearchCriteria($banner[BannerInterface::SLIDE_IDS]))
                        ->getItems();
                    $slideLinks = [];
                    foreach ($slides as $slide) {
                        $slideLinks[] = [
                            'name' => $slide->getName(),
                            'url' => $this->getLink($slide->getId())
                        ];
                    }
                    $banner['slides'] = $slideLinks;
                }
            }
        }
        return $dataSource;
    }

    /**
     * Retrieve link for slide
     *
     * @param int $entityId
     * @return string
     */
    private function getLink($entityId)
    {
        return $this->context->getUrl('aw_bannerslider_admin/slide/edit', ['id' => $entityId]);
    }

    /**
     * Create SearchCriteria for slideIds
     *
     * @param array $slideIds
     * @return \Magento\Framework\Api\SearchCriteria
     */
    private function getSearchCriteria(array $slideIds)
    {
        return $this->searchCriteriaBuilder
            ->addFilter('id', $slideIds, 'in')
            ->create();
    }
}
