<?php
/**
* Copyright 2016 thinkIdeas. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace ThinkIdeas\Bannerslider\Model\ResourceModel;

use Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use ThinkIdeas\Bannerslider\Api\BannerRepositoryInterface;
use ThinkIdeas\Bannerslider\Api\BlockRepositoryInterface;
use ThinkIdeas\Bannerslider\Api\SlideRepositoryInterface;
use ThinkIdeas\Bannerslider\Api\StatisticRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
use ThinkIdeas\Bannerslider\Model\Rule\Validator;
use ThinkIdeas\Bannerslider\Api\Data\BlockSearchResultsInterfaceFactory;
use ThinkIdeas\Bannerslider\Api\Data\BlockInterfaceFactory;
use ThinkIdeas\Bannerslider\Model\CustomerStatistic\Manager as CustomerStatisticManager;
use ThinkIdeas\Bannerslider\Api\Data\BannerInterface;
use ThinkIdeas\Bannerslider\Model\Source\Status;
use ThinkIdeas\Bannerslider\Api\Data\SlideInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime as StdlibDateTime;
use ThinkIdeas\Bannerslider\Api\Data\BlockSearchResultsInterface;
use ThinkIdeas\Bannerslider\Api\Data\BlockInterface;

/**
 * Class BlockRepository
 *
 * @package ThinkIdeas\Bannerslider\Model\ResourceModel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BlockRepository implements BlockRepositoryInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var BannerRepositoryInterface
     */
    private $bannerRepository;

    /**
     * @var SlideRepositoryInterface
     */
    private $slideRepository;

    /**
     * @var StatisticRepositoryInterface
     */
    private $statisticRepository;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var BlockSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var BlockInterfaceFactory
     */
    private $blockFactory;

    /**
     * @var CustomerStatisticManager
     */
    private $customerStatisticManager;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param BannerRepositoryInterface $bannerRepository
     * @param SlideRepositoryInterface $slideRepository
     * @param StatisticRepositoryInterface $statisticRepository
     * @param SortOrderBuilder $sortOrderBuilder
     * @param Validator $validator
     * @param BlockSearchResultsInterfaceFactory $searchResultsFactory
     * @param BlockInterfaceFactory $blockFactory
     * @param CustomerStatisticManager $customerStatisticManager
     * @param DateTime $dateTime
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Session $customerSession,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        BannerRepositoryInterface $bannerRepository,
        SlideRepositoryInterface $slideRepository,
        StatisticRepositoryInterface $statisticRepository,
        SortOrderBuilder $sortOrderBuilder,
        Validator $validator,
        BlockSearchResultsInterfaceFactory $searchResultsFactory,
        BlockInterfaceFactory $blockFactory,
        CustomerStatisticManager $customerStatisticManager,
        DateTime $dateTime
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->bannerRepository = $bannerRepository;
        $this->slideRepository = $slideRepository;
        $this->statisticRepository = $statisticRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->validator = $validator;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->blockFactory = $blockFactory;
        $this->customerStatisticManager = $customerStatisticManager;
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getList($blockType, $blockPosition, $allBlocks = true, $updateViewsStatistic = true)
    {
        $this->searchCriteriaBuilder
            ->addFilter(BannerInterface::STATUS, Status::STATUS_ENABLED)
            ->addFilter(BannerInterface::PAGE_TYPE, $blockType)
            ->addFilter(BannerInterface::POSITION, $blockPosition);
        $bannerList = $this->bannerRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();

        $blockItems = [];
        foreach ($bannerList as $banner) {
            if (count($banner->getSlideIds()) && $this->validator->canShow($banner)) {
                $slideList = $this->getSlideList($banner);
                foreach ($slideList as $slide) {
                    $this->searchCriteriaBuilder
                        ->addFilter('banner_id', $banner->getId())
                        ->addFilter('slide_id', $slide->getId());
                    $statisticList = $this->statisticRepository
                        ->getList($this->searchCriteriaBuilder->create())
                        ->getItems();
                    foreach ($statisticList as $statistic) {
                        $name = 'slide_view_' . $statistic->getSlideBannerId();
                        if (!$this->customerStatisticManager->isSetSlideAction($name)) {
                            $statistic->setViewCount((int)$statistic->getViewCount() + 1);
                            $this->statisticRepository->save($statistic);
                            $this->customerStatisticManager->addSlideAction($name);
                        }
                    }
                }
                $this->customerStatisticManager->save();

                /** @var BlockInterface $blockDataModel */
                $blockDataModel = $this->blockFactory->create();
                $blockDataModel->setBanner($banner);
                $blockDataModel->setSlides($slideList);
                $blockItems[] = $blockDataModel;

                // Break of the loop if we want to display only one block
                if (!$allBlocks) {
                    break;
                }
            }
        }

        /** @var BlockSearchResultsInterface $blockSearchResults */
        $blockSearchResults = $this->searchResultsFactory->create()
            ->setSearchCriteria($this->searchCriteriaBuilder->create());
        $blockSearchResults->setItems($blockItems);
        $blockSearchResults->setTotalCount(count($blockItems));

        return $blockSearchResults;
    }

    /**
     * Retrieve slide list for current conditions
     *
     * @param BannerInterface $banner
     * @return SlideInterface[]
     */
    private function getSlideList($banner)
    {
        $gmtTimestamp = strtotime($this->dateTime->gmtDate()) + $this->dateTime->getGmtOffset();
        $currentDate = date(StdlibDateTime::DATETIME_PHP_FORMAT, $gmtTimestamp);

        $directionSort = $banner->getIsRandomOrderImage()
            ? SortOrder::SORT_DESC // For RAND sorting
            : SortOrder::SORT_ASC;
        $sortOrder = $this->sortOrderBuilder
            ->setField('position')
            ->setDirection($directionSort)
            ->create();

        $this->searchCriteriaBuilder
            ->addFilter(SlideInterface::STORE_IDS, $this->storeManager->getStore()->getId())
            ->addFilter(SlideInterface::CUSTOMER_GROUP_IDS, $this->customerSession->getCustomerGroupId())
            ->addFilter(SlideInterface::STATUS, Status::STATUS_ENABLED)
            ->addFilter('date', $currentDate)
            ->addFilter('banner_id', $banner->getId())
            ->addSortOrder($sortOrder);

        $slideList = $this->slideRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();

        return $slideList;
    }
}
