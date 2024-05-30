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
        $path = $this->moduleDir->getDir('AHT_ImportShopifyDiscount');

        $customerGroups = [];
        foreach($this->customerGroup->toOptionArray() as $item) {
            $customerGroups[] = $item['value'];
        }

        if (($handle = fopen($path . "/discounts_exports.csv", "r")) !== FALSE) {
            $row = 0;
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                $row++;
                if ($row == 1) {
                    continue;
                }

                try {
                    /**
                     * @var Magento\SalesRule\Model\Rule
                     */
                    $shoppingCartPriceRule = $this->_objectManager->create('Magento\SalesRule\Model\Rule');

                    $shoppingCartPriceRule->setName($data[0])
                        ->setDescription('')
                        ->setFromDate($data[14])
                        ->setToDate($data[15])
                        ->setUsesPerCustomer($data[11])
                        //unclear prequisite group in source data
                        ->setCustomerGroupIds($customerGroups)
                        ->setIsActive($data[13] == 'Active' ? 1 : 0)
                        // by_percent, by_fixed, cart_fixed, buy_x_get_y
                        ->setSimpleAction($data[2] == 'fixed_amount' ? 'cart_fixed' : 'by_percent')
                        ->setDiscountAmount(abs((int)$data[1]))
                        ->setDiscountQty(0)
                        /**
                         * 0: No
                         * 1: For matching items only
                         * 2: For shipments with matching items
                         */
                        ->setApplyToShipping(0)
                        ->setTimesUsed($data[10])
                        //current site id wrapped in an array
                        ->setWebsiteIds(['1'])
                        /**
                         * 1: No Coupon
                         * 2: Specific Coupon
                         */
                        ->setCouponType(2)
                        ->setCouponCode($data[0])
                        ->setUsesPerCoupon($data[12]);

                    //Minimum purchase requirement
                    if ($data[5] != null) {
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
                                            'value' => $data[5],
                                            'is_value_processed' => false,
                                        ]
                                ],
                            ]
                        );
                    }
                        
                    $shoppingCartPriceRule->save();
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(__('Error in row ' . $row));

                    $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
                    $logger = new \Zend_Log();
                    $logger->addWriter($writer);
                    $logger->info('Error in row: ' . $row . '. Name: ' . $data[0]);
                    $logger->info('Exception: ' . $e->getMessage());
                    $logger->info('--------------------------------------------------------------------------------------------------');
                }
            }
            fclose($handle);
        }

        $this->messageManager->addSuccessMessage(__('Success'));
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}

