<?php

/**
 * ScandiPWA_NewsletterGraphQl
 *
 * @category    ScandiPWA
 * @package     ScandiPWA_NewsletterGraphQl
 * @author      Scandesignmedia <info@scandesignmedia.dk>
 * @copyright   Copyright (c) 2019 Scandiweb, Ltd (https://scandiweb.com)
 */

namespace ScandiPWA\NewsletterGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Newsletter\Model\Subscriber;

class subscriberStatus implements ResolverInterface
{
    protected $subscriber;

    /**
     * subscriberStatus constructor.
     * @param Subscriber $subscriber
     */
    public function __construct(
        Subscriber $subscriber
    )
    {
        $this->subscriber = $subscriber;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        $getSubscriber = $this->subscriber->loadByCustomerId($args["customerId"]);

        $status = $getSubscriber->getStatus();

        return [
            "status" => $status
        ];
    }
}