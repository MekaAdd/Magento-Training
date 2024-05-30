<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AHT\ImportShopifyDiscount\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;

class Delete extends \Magento\Backend\App\Action 
{
    protected $collectionFactory;
    protected $resultPageFactory;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->collectionFactory = $collectionFactory;

        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() 
    {
        $om = $this->collectionFactory->create();
        foreach ($om as $item) {
            $item->delete();
        }

        $this->messageManager->addSuccessMessage(__('Success'));
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}