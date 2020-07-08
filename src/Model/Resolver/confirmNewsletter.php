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
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Newsletter\Controller\Subscriber\Confirm;
use Magento\Newsletter\Model\Subscriber;

class confirmNewsletter extends Confirm
{

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        $id = $args["id"];
        $code = $args["code"];

        if ($id && $code) {
            /** @var Subscriber $subscriber */
            $subscriber = $this->_subscriberFactory->create()->load($id);

            if ($subscriber->getId() && $subscriber->getCode()) {
                if ($subscriber->confirm($code)) {
                    $status_text = __('Your subscription has been confirmed.');
                } else {
                    $status_text = __('This is an invalid subscription confirmation code.');
                }
            } else {
                $status_text = __('This is an invalid subscription ID.');
            }

            return [
                'status' => "$status_text"
            ];
        }
    }
}