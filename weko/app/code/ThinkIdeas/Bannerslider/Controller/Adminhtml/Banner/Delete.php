<?php
/**
* Copyright 2016 thinkIdeas. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace ThinkIdeas\Bannerslider\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action\Context;
use ThinkIdeas\Bannerslider\Api\BannerRepositoryInterface;

/**
 * Class Delete
 * @package ThinkIdeas\Bannerslider\Controller\Adminhtml\Banner
 */
class Delete extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'ThinkIdeas_Bannerslider::banners';

    /**
     * @var BannerRepositoryInterface
     */
    private $bannerRepository;

    /**
     * @param Context $context
     * @param BannerRepositoryInterface $bannerRepository
     */
    public function __construct(
        Context $context,
        BannerRepositoryInterface $bannerRepository
    ) {
        parent::__construct($context);
        $this->bannerRepository = $bannerRepository;
    }

    /**
     * Delete banner action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->bannerRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('Banner was successfully deleted'));
                return $resultRedirect->setPath('*/*/index');
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
            }
        }
        $this->messageManager->addErrorMessage(__('Banner could not be deleted'));
        return $resultRedirect->setPath('*/*/index');
    }
}
