<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AHT\ImportShopifyDiscount\Controller\Adminhtml\Import;

use Magento\Framework\Module\Dir;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;
use Magento\SalesRule\Api\Data\ConditionInterfaceFactory;
use Magento\SalesRule\Api\Data\ConditionInterface;
use Magento\SalesRule\Model\Rule\Condition\Address;
use Magento\SalesRule\Model\Rule\Condition\Combine;

class Index extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $moduleDir;
    protected $customerGroup;
    protected $conditionInterfaceFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Dir $moduleDir,
        CustomerGroup $customerGroup,
        ConditionInterfaceFactory $conditionInterfaceFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->moduleDir = $moduleDir;
        $this->customerGroup = $customerGroup;
        $this->conditionInterfaceFactory = $conditionInterfaceFactory;

        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // $path = $this->moduleDir->getDir('AHT_ImportShopifyDiscount');
        // if (($handle = fopen($path . "/discounts_exports.csv", "r")) !== FALSE) {
        //     $row = 1;
        //     while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
        //         $num = count($data);
        //         $row++;
        //         for ($i = 0; $i < $num; $i++) {
        //             //insertdb
        //         }
        //     }
        //     fclose($handle);
        // }

        $customerGroups = [];
        foreach($this->customerGroup->toOptionArray() as $item) {
            $customerGroups[] = $item['value'];
        }

        /**
         * @var Magento\SalesRule\Model\Rule
         */
        $shoppingCartPriceRule = $this->_objectManager->create('Magento\SalesRule\Model\Rule');
        $shoppingCartPriceRule->setName("1")
            ->setDescription('')
            ->setFromDate('2024-05-26')
            ->setToDate('')
            ->setUsesPerCustomer(1)
            ->setCustomerGroupIds($customerGroups)
            ->setIsActive(1)
            // by_percent, by_fixed, cart_fixed, buy_x_get_y
            ->setSimpleAction('by_fixed')
            ->setDiscountAmount(5)
            ->setDiscountQty(0)
            /**
             * 0: No
             * 1: For matching items only
             * 2: For shipments with matching items
             */
            ->setApplyToShipping(0)
            ->setTimesUsed(0)
            ->setWebsiteIds(['1'])
            ->setCouponType(2)
            ->setCouponCode("1")
            ->setUsesPerCoupon(1);

        $shoppingCartPriceRule->getConditions()->loadArray(
            [
                'type' => Combine::class,
                'attribute' => null,
                'operator' => null,
                'value' => '1',
                'is_value_processed' => null,
                'aggregator' => 'all',
                'conditions' => [
                        [
                            'type' => Address::class,
                            'attribute' => 'base_subtotal',
                            'operator' => '>=',
                            'value' => 150,
                            'is_value_processed' => false,
                        ]
                ],
            ]
        );
        
        $shoppingCartPriceRule->save();

        $this->messageManager->addSuccessMessage(__('Success'));
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}

